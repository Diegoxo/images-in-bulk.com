<?php
/**
 * API for Setting Default Payment Method
 */
require_once '../includes/config.php';
require_once '../includes/utils/security.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cardId = $input['id'] ?? null;

if (!$cardId) {
    echo json_encode(['success' => false, 'error' => 'Invalid Request']);
    exit;
}

// CSRF Validation
$clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!CSRF::validate($clientToken)) {
    echo json_encode(['success' => false, 'error' => 'Security validation failed']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = getDB();
    $db->beginTransaction();

    // 1. Verify card belongs to user
    $check = $db->prepare("SELECT wompi_payment_source_id FROM payment_methods WHERE id = ? AND user_id = ?");
    $check->execute([$cardId, $userId]);
    $card = $check->fetch();

    if (!$card) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => 'Card not found']);
        exit;
    }

    // 2. Unset others
    $db->prepare("UPDATE payment_methods SET is_default = FALSE WHERE user_id = ?")->execute([$userId]);

    // 3. Set new default
    $db->prepare("UPDATE payment_methods SET is_default = TRUE WHERE id = ?")->execute([$cardId]);

    // 4. Update core subscription record for Wompi recurring bills
    // Note: We only update if they have an active subscription
    $db->prepare("UPDATE subscriptions SET wompi_payment_source_id = ? WHERE user_id = ? AND status IN ('active', 'cancelled')")
        ->execute([$card['wompi_payment_source_id'], $userId]);

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if (isset($db))
        $db->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
