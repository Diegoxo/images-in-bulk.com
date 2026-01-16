<?php
/**
 * API for Adding/Replacing Payment Method without Payment
 */
require_once '../includes/config.php';
require_once '../includes/wompi-helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// CSRF Validation
require_once '../includes/utils/security.php';
$clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!CSRF::validate($clientToken)) {
    echo json_encode(['success' => false, 'error' => 'Security validation failed (CSRF mismatch)']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$cardToken = $data['token'] ?? null;

if (!$cardToken) {
    echo json_encode(['success' => false, 'error' => 'Card token missing']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = getDB();
    $wompi = new WompiHelper();

    // 1. Get user email
    $stmtUser = $db->prepare("SELECT email FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $email = $stmtUser->fetchColumn();

    // 2. Create Payment Source in Wompi
    $paymentSourceId = $wompi->createPaymentSource($cardToken, $email);

    if (!$paymentSourceId) {
        throw new Exception("Could not create payment source in Wompi");
    }

    // 3. Update subscription record
    // We check if subscription exists, if not create a free/inactive one
    $stmtCheck = $db->prepare("SELECT id FROM subscriptions WHERE user_id = ?");
    $stmtCheck->execute([$userId]);
    $subExists = $stmtCheck->fetchColumn();

    if ($subExists) {
        $stmt = $db->prepare("UPDATE subscriptions SET 
            wompi_payment_source_id = ?, 
            wompi_customer_email = ?,
            updated_at = NOW()
            WHERE user_id = ?");
        $stmt->execute([$paymentSourceId, $email, $userId]);
    } else {
        $stmt = $db->prepare("INSERT INTO subscriptions (user_id, plan_type, status, wompi_payment_source_id, wompi_customer_email) 
            VALUES (?, 'free', 'inactive', ?, ?)");
        $stmt->execute([$userId, $paymentSourceId, $email]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
