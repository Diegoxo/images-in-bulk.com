<?php
/**
 * API for Deleting Payment Method
 * Ensures atomic deletion with proper validation and logging
 */
require_once '../includes/config.php';
require_once '../includes/utils/security.php';

header('Content-Type: application/json');

// Session validation
if (!isset($_SESSION['user_id'])) {
    error_log("Delete Payment Method: Unauthorized access attempt");
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Parse input
$input = json_decode(file_get_contents('php://input'), true);
$cardId = $input['id'] ?? null;

if (!$cardId || !is_numeric($cardId)) {
    error_log("Delete Payment Method: Invalid card ID provided");
    echo json_encode(['success' => false, 'error' => 'Invalid card ID']);
    exit;
}

// CSRF Validation
$clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!CSRF::validate($clientToken)) {
    error_log("Delete Payment Method: CSRF validation failed");
    echo json_encode(['success' => false, 'error' => 'Security validation failed']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $db = getDB();

    // Start transaction
    $db->beginTransaction();

    // 1. Verify card exists and belongs to user
    $stmt = $db->prepare("SELECT id, is_default, wompi_payment_source_id, brand, last4 FROM payment_methods WHERE id = ? AND user_id = ?");
    $stmt->execute([$cardId, $userId]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$card) {
        $db->rollBack();
        error_log("Delete Payment Method: Card ID $cardId not found for user $userId");
        echo json_encode(['success' => false, 'error' => 'Card not found or does not belong to you']);
        exit;
    }

    // 2. Delete the card record
    $stmtDel = $db->prepare("DELETE FROM payment_methods WHERE id = ? AND user_id = ?");
    $deleted = $stmtDel->execute([$cardId, $userId]);

    if (!$deleted || $stmtDel->rowCount() === 0) {
        $db->rollBack();
        error_log("Delete Payment Method: Failed to delete card ID $cardId for user $userId");
        echo json_encode(['success' => false, 'error' => 'Failed to delete card']);
        exit;
    }

    // 3. If it was the default card, remove it from active subscriptions
    if ($card['is_default']) {
        $stmtSub = $db->prepare("UPDATE subscriptions SET wompi_payment_source_id = NULL WHERE user_id = ? AND wompi_payment_source_id = ?");
        $stmtSub->execute([$userId, $card['wompi_payment_source_id']]);
        error_log("Delete Payment Method: Removed default card from subscription for user $userId");
    }

    // Commit transaction
    $db->commit();

    error_log("Delete Payment Method: Successfully deleted card ID $cardId ({$card['brand']} {$card['last4']}) for user $userId");
    echo json_encode(['success' => true, 'message' => 'Payment method removed successfully']);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Delete Payment Method: Database error - " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred while removing the payment method']);
}
