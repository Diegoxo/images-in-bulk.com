<?php
/**
 * API for Adding Payment Method (Updated for Multi-Card)
 */
require_once '../includes/config.php';
require_once '../includes/wompi-helper.php';
require_once '../includes/utils/security.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!CSRF::validate($clientToken)) {
    echo json_encode(['success' => false, 'error' => 'Security validation failed']);
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

    // 3. Get Card details from Wompi to store locally
    $sourceRes = $wompi->getPaymentSource($paymentSourceId);
    $brand = 'Card';
    $last4 = '****';
    $exp_month = null;
    $exp_year = null;

    if (isset($sourceRes['data']['public_data'])) {
        $pd = $sourceRes['data']['public_data'];
        $brand = $pd['brand'] ?? 'Card';
        $last4 = $pd['last_four'] ?? '****';
        $exp_month = $pd['exp_month'] ?? null;
        $exp_year = $pd['exp_year'] ?? null;
    }

    $db->beginTransaction();

    // 4. Check if user has cards to decide default
    $stmtCheck = $db->prepare("SELECT id FROM payment_methods WHERE user_id = ?");
    $stmtCheck->execute([$userId]);
    $isDefault = ($stmtCheck->rowCount() === 0);

    // 5. Insert Card
    $stmtIns = $db->prepare("INSERT INTO payment_methods (user_id, wompi_payment_source_id, brand, last4, exp_month, exp_year, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtIns->execute([$userId, $paymentSourceId, $brand, $last4, $exp_month, $exp_year, $isDefault]);

    // 6. If it's the only card, update subscription record
    if ($isDefault) {
        $db->prepare("UPDATE subscriptions SET wompi_payment_source_id = ? WHERE user_id = ? AND status = 'active'")
            ->execute([$paymentSourceId, $userId]);
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($db))
        $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
