<?php
/**
 * API: Cancel Subscription
 * Disables recurring billing by setting status to 'inactive'
 */
require_once '../includes/config.php';

header('Content-Type: application/json');

session_start();
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    $db = getDB();

    // We set status to 'inactive'. This stops the CRON from charging again.
    // The SubscriptionHelper already handles 'current_period_end' vs NOW() 
    // but the status='active' is a prerequisite in the SubscriptionHelper's check 
    // for the PRO benefits. 
    // To allow them to keep using it until the end of the month, we'd need a 'cancelled' status.
    // However, to keep it simple and safe for business, cancelling usually means 
    // "I want out now". 
    // But a friendlier way is to just set it so Cron doesn't pick it up.

    // Let's mark it as 'inactive'. 
    // Note: If we mark it inactive, they lose benefits immediately based on our Helper.
    // If the user wants them to keep it until the end of the month, we need a 
    // status like 'active_no_renew'.

    // For now, let's follow the simple 'inactive' approach which is safer and 
    // avoids complex state management.

    $stmt = $db->prepare("UPDATE subscriptions SET status = 'inactive' WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$userId]);

    echo json_encode(['success' => true, 'message' => 'Subscription cancelled successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
