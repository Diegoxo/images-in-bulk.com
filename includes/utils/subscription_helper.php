<?php
/**
 * Subscription Status Helper
 * Handles logic for checking user subscription status and usage limits.
 */

function getUserSubscriptionStatus($userId)
{
    $result = [
        'isPro' => false,
        'freeImagesCount' => 0,
        'freeLimit' => 3
    ];

    if (!$userId) {
        return $result;
    }

    try {
        $db = getDB();

        // Check Subscription
        $stmt = $db->prepare("SELECT plan_type, status FROM subscriptions WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$userId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sub && $sub['plan_type'] === 'pro') {
            $result['isPro'] = true;
        }

        // Count Generated Images (for free users only, to save resources)
        if (!$result['isPro']) {
            $stmtCount = $db->prepare("SELECT COUNT(*) FROM generations WHERE user_id = ?");
            $stmtCount->execute([$userId]);
            $result['freeImagesCount'] = (int) $stmtCount->fetchColumn();
        }

    } catch (Exception $e) {
        // Silently log error to avoid breaking the UI, revert to free status defaults
        error_log("Subscription Check Error: " . $e->getMessage());
    }

    return $result;
}
?>