<?php
/**
 * Subscription Status Helper
 * Handles logic for checking user subscription status and usage limits.
 */

function getUserSubscriptionStatus($userId)
{
    $result = [
        'isPro' => false,
        'credits' => 0,
        'extra_credits' => 0,
        'total_credits' => 0,
        'freeImagesCount' => 0,
        'freeLimit' => 3,
        'billing_cycle' => 'monthly'
    ];

    if (!$userId) {
        return $result;
    }

    try {
        $db = getDB();

        // 1. Fetch Subscription and Regular Credits
        $stmt = $db->prepare("
            SELECT s.id as sub_id, s.plan_type, s.status, s.current_period_end, s.billing_cycle, u.credits 
            FROM users u
            LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status IN ('active', 'cancelled')
            WHERE u.id = ?
            ORDER BY s.id DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Fetch Active Credit Bundles (Not expired, with balance)
        $stmtBundles = $db->prepare("SELECT SUM(amount_remaining) FROM credit_bundles WHERE user_id = ? AND expires_at > NOW() AND amount_remaining > 0");
        $stmtBundles->execute([$userId]);
        $extraCreditsSum = (int) $stmtBundles->fetchColumn();

        if ($data) {
            $result['credits'] = (int) ($data['credits'] ?? 0);
            $result['extra_credits'] = $extraCreditsSum;

            // Sync extra_credits column if needed
            $db->prepare("UPDATE users SET extra_credits = ? WHERE id = ?")->execute([$extraCreditsSum, $userId]);

            $result['total_credits'] = $result['credits'] + $result['extra_credits'];
            $result['billing_cycle'] = $data['billing_cycle'] ?? 'monthly';

            // Check for expiration (only if they are pro)
            $isExpired = false;
            if ($data['plan_type'] === 'pro' && !empty($data['current_period_end'])) {
                if (strtotime($data['current_period_end']) < time()) {
                    $isExpired = true;
                }
            }

            // User loses PRO access if:
            // 1. Period is met (Expired)
            // 2. Total credits run out (<= 0)
            if ($isExpired || $result['total_credits'] <= 0) {
                if ($data['plan_type'] === 'pro') {
                    if ($isExpired) {
                        $db->prepare("UPDATE subscriptions SET status = 'inactive' WHERE id = ?")->execute([$data['sub_id']]);
                        $db->prepare("UPDATE users SET credits = 0 WHERE id = ?")->execute([$userId]);
                        $result['credits'] = 0;
                        $result['total_credits'] = $result['extra_credits'];
                    }
                }
                $result['isPro'] = ($result['total_credits'] > 0);
            } else {
                if ($data['plan_type'] === 'pro') {
                    $result['isPro'] = true;
                }
            }
        }

        // Count Generated Images (for free users only)
        if (!$result['isPro']) {
            $stmtCount = $db->prepare("SELECT COUNT(*) FROM generations WHERE user_id = ?");
            $stmtCount->execute([$userId]);
            $result['freeImagesCount'] = (int) $stmtCount->fetchColumn();
        }

    } catch (Exception $e) {
        error_log("Subscription Check Error: " . $e->getMessage());
    }

    return $result;
}

/**
 * Calculate credit cost based on model and resolution
 */
function calculateImageCost($model, $resolution)
{
    $isSquare = ($resolution === '1:1' || $resolution === '1024x1024');

    if (strpos($model, 'gpt-image-1.5') !== false) {
        return $isSquare ? 170 : 250;
    }

    if (strpos($model, 'gpt-image-1-mini') !== false) {
        return $isSquare ? 55 : 75;
    }

    if (strpos($model, 'dall-e-3') !== false) {
        return $isSquare ? 200 : 400;
    }

    return 200; // Default cost
}

/**
 * Deduct credits from user (Intelligent Consumption: Monthly -> Bundle expiring soon)
 */
function deductCredits($userId, $amount)
{
    try {
        $db = getDB();
        $db->beginTransaction();

        // 1. Get Regular credits
        $stmt = $db->prepare("SELECT credits FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            $db->rollBack();
            return false;
        }

        $regular = (int) $user['credits'];
        $remaining = $amount;

        // 2. Deduct from regular first
        if ($regular > 0) {
            $diff = min($regular, $remaining);
            $regular -= $diff;
            $remaining -= $diff;
            $db->prepare("UPDATE users SET credits = ? WHERE id = ?")->execute([$regular, $userId]);
        }

        // 3. Deduct from Bundles (Earliest expiry first)
        if ($remaining > 0) {
            $stmtBundles = $db->prepare("SELECT id, amount_remaining FROM credit_bundles WHERE user_id = ? AND expires_at > NOW() AND amount_remaining > 0 ORDER BY expires_at ASC FOR UPDATE");
            $stmtBundles->execute([$userId]);
            $bundles = $stmtBundles->fetchAll();

            foreach ($bundles as $bundle) {
                if ($remaining <= 0)
                    break;

                $bAmount = (int) $bundle['amount_remaining'];
                $bDiff = min($bAmount, $remaining);

                $newBAmount = $bAmount - $bDiff;
                $remaining -= $bDiff;

                $db->prepare("UPDATE credit_bundles SET amount_remaining = ? WHERE id = ?")->execute([$newBAmount, $bundle['id']]);
            }

            // 4. Update extra_credits cache in users table
            $stmtSync = $db->prepare("UPDATE users SET extra_credits = (SELECT SUM(amount_remaining) FROM credit_bundles WHERE user_id = ? AND expires_at > NOW() AND amount_remaining > 0) WHERE id = ?");
            $stmtSync->execute([$userId, $userId]);
        }

        if ($remaining > 0) {
            $db->rollBack();
            return false;
        }

        $db->commit();
        return true;
    } catch (Exception $e) {
        if (isset($db))
            $db->rollBack();
        error_log("Deduct Credits Error: " . $e->getMessage());
        return false;
    }
}
/**
 * Centralized access validation
 * Returns ['success' => true] or ['success' => false, 'error' => 'Reason']
 */
function validateUserAccess($userId, $model, $resolution)
{
    $status = getUserSubscriptionStatus($userId);
    $isPro = $status['isPro'];

    // PRO model check: Free users ONLY allowed to use GPT Mini
    if (!$isPro && $model !== 'gpt-image-1-mini') {
        return ['success' => false, 'error' => 'This model is only available for PRO users.'];
    }

    // PRO resolution check
    if (!$isPro && $resolution !== '1:1') {
        return ['success' => false, 'error' => 'Custom resolutions are only available for PRO users.'];
    }

    // PRO credits check
    if ($isPro) {
        $cost = calculateImageCost($model, $resolution);
        if ($status['total_credits'] < $cost) {
            return ['success' => false, 'error' => 'Insufficient credits. Balance: ' . $status['total_credits']];
        }
    } else {
        // Free tier limits
        if (($status['freeImagesCount'] + 1) > $status['freeLimit']) {
            return ['success' => false, 'error' => 'Free limit reached. Please upgrade to PRO.'];
        }
    }

    return ['success' => true, 'data' => $status];
}

/**
 * Validate batch size and costs
 */
function validateBatchAccess($userId, $model, $resolution, $batchSize)
{
    $status = getUserSubscriptionStatus($userId);
    $isPro = $status['isPro'];

    // 1. Basic type validation (can they even use this model?)
    $basic = validateUserAccess($userId, $model, $resolution);
    if (!$basic['success'])
        return $basic;

    // 2. Quantity validation
    if ($isPro) {
        $costPerImage = calculateImageCost($model, $resolution);
        $totalCost = $costPerImage * $batchSize;
        if ($status['total_credits'] < $totalCost) {
            return [
                'success' => false,
                'error' => "Insufficient credits for the full batch. Required: $totalCost, Balance: " . $status['total_credits']
            ];
        }
    } else {
        // Free tier capacity check
        if (($status['freeImagesCount'] + $batchSize) > $status['freeLimit']) {
            $remaining = max(0, $status['freeLimit'] - $status['freeImagesCount']);
            return [
                'success' => false,
                'error' => "Batch exceeds free limit. You can only generate $remaining more images."
            ];
        }
    }

    return ['success' => true, 'data' => $status];
}
?>