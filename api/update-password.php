<?php
/**
 * API: Update Password
 * Handles secure password updates for logged-in users.
 */
// Start output buffering immediately to catch any BOMs or unwanted text included files might emit
ob_start();

require_once '../includes/config.php';

// Prepare header but don't send output yet
header('Content-Type: application/json');

// 1. Auth Check
if (!isset($_SESSION['user_id'])) {
    ob_clean(); // Discard any prior output
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // 2. Input Parsing
    $input = json_decode(file_get_contents('php://input'), true);

    $currentPassword = $input['current_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';
    $userId = $_SESSION['user_id'];

    // 3. Backend Validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        throw new Exception('All fields are required.');
    }

    if ($newPassword !== $confirmPassword) {
        throw new Exception('New passwords do not match.');
    }

    if (strlen($newPassword) < 8) {
        throw new Exception('New password must be at least 8 characters long.');
    }

    $db = getDB();

    // 4. Verify Current Password
    // IMPORTANT: Fetch password_hash, not plain password
    $stmtUser = $db->prepare("SELECT password_hash, auth_provider FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    if (!$user) {
        throw new Exception('User not found.');
    }

    // Security verify: Only allow local auth users
    if ($user['auth_provider'] !== 'local') {
        throw new Exception('You cannot change password for this account type.');
    }

    if (!password_verify($currentPassword, $user['password_hash'])) {
        throw new Exception('Incorrect current password.');
    }

    // 5. Update Password
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmtUpdate = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmtUpdate->execute([$newHash, $userId]);

    // SUCCESS: Clean the buffer of any previous noise (BOMs, warnings) and output pure JSON
    ob_clean();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // ERROR: Clean buffer and output error JSON
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
