<?php
/**
 * API for Deleting Payment Method
 */
require_once '../includes/config.php';

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
require_once '../includes/utils/security.php';
$clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!CSRF::validate($clientToken)) {
    echo json_encode(['success' => false, 'error' => 'Security validation failed (CSRF mismatch)']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = getDB();

    // We update the subscription to remove the payment source ID
    // Note: We don't cancel the plan immediately, just prevent renewal
    $stmt = $db->prepare("UPDATE subscriptions SET 
        wompi_payment_source_id = NULL 
        WHERE user_id = ?");

    $stmt->execute([$userId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
