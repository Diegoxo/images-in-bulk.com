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

// CSRF Validation
require_once '../includes/utils/security.php';
$clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!CSRF::validate($clientToken)) {
    echo json_encode(['success' => false, 'error' => 'Security validation failed (CSRF mismatch)']);
    exit;
}

try {
    $db = getDB();

    // We set status to 'cancelled'. This stops the CRON from charging again.
    // The SubscriptionHelper will allow them to keep PRO benefits 
    // until 'current_period_end' is reached or credits run out.
    $stmt = $db->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$userId]);

    echo json_encode(['success' => true, 'message' => 'Subscription cancelled successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
