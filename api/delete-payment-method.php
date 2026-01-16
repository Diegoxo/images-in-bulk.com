<?php
/**
 * API for Deleting Payment Method
 */
require_once '../includes/config.php';
require_once '../includes/utils/security.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// CSRF Validation
$clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!CSRF::validate($clientToken)) {
    echo json_encode(['success' => false, 'error' => 'Security validation failed (CSRF mismatch)']);
    exit;
}

// Rate Limiting
if (!RateLimiter::check('delete_card', 15)) {
    echo json_encode(['success' => false, 'error' => 'Please wait a moment before trying again.']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = getDB();

    // Verification: Does the user even have a card to delete?
    $checkStmt = $db->prepare("SELECT wompi_payment_source_id FROM subscriptions WHERE user_id = ?");
    $checkStmt->execute([$userId]);
    $sub = $checkStmt->fetch();

    if (!$sub || empty($sub['wompi_payment_source_id'])) {
        echo json_encode(['success' => false, 'error' => 'No payment method found to remove.']);
        exit;
    }

    // We update the subscription to remove the payment source ID
    // Note: We don't cancel the plan immediately, just prevent renewal
    $stmt = $db->prepare("UPDATE subscriptions SET 
        wompi_payment_source_id = NULL 
        WHERE user_id = ?");

    $stmt->execute([$userId]);

    echo json_encode(['success' => true, 'message' => 'Payment method removed successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
