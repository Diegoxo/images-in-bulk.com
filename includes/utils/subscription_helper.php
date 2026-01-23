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
        'freeImagesCount' => 0,
        'freeLimit' => 3,
        'billing_cycle' => 'monthly'
    ];

    if (!$userId) {
        return $result;
    }

    try {
        $db = getDB();

        // Check Subscription and credits
        $stmt = $db->prepare("
            SELECT s.id as sub_id, s.plan_type, s.status, s.current_period_end, s.billing_cycle, u.credits 
            FROM users u
            LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status IN ('active', 'cancelled')
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $result['credits'] = (int) ($data['credits'] ?? 0);
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
            // 2. Credits run out (<= 0)
            if ($isExpired || $result['credits'] <= 0) {
                // AUTO-CLEANUP: If expired or no credits, deactivate sub if it was pro
                if ($data['plan_type'] === 'pro') {
                    $db->prepare("UPDATE subscriptions SET status = 'inactive' WHERE id = ?")->execute([$data['sub_id']]);
                    if ($isExpired) {
                        $db->prepare("UPDATE users SET credits = 0 WHERE id = ?")->execute([$userId]);
                        $result['credits'] = 0;
                    }
                }
                $result['isPro'] = false;
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
 * Deduct credits from user
 */
function deductCredits($userId, $amount)
{
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET credits = GREATEST(0, credits - ?) WHERE id = ?");
        return $stmt->execute([$amount, $userId]);
    } catch (Exception $e) {
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
        if ($status['credits'] < $cost) {
            return ['success' => false, 'error' => 'Insufficient credits. Balance: ' . $status['credits']];
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
        if ($status['credits'] < $totalCost) {
            return [
                'success' => false,
                'error' => "Insufficient credits for the full batch. Required: $totalCost, Balance: " . $status['credits']
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