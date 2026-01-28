<?php
/**
 * API: Request Email Change
 * Validates inputs, verifies password, and sends a verification link to the new email.
 */
require_once '../includes/config.php';
require_once '../includes/utils/email_helper.php';

header('Content-Type: application/json');

// 1. Session Auth Guard
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$userId = $_SESSION['user_id'];
$db = getDB();

// 2. Parse JSON Input
$input = json_decode(file_get_contents('php://input'), true);
$newEmail = isset($input['new_email']) ? trim($input['new_email']) : '';
$confirmEmail = isset($input['confirm_email']) ? trim($input['confirm_email']) : '';
$password = isset($input['current_password']) ? $input['current_password'] : '';

// 3. Backend Validations (As requested)
if (empty($newEmail) || empty($confirmEmail) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// 3.1 Check if they match
if ($newEmail !== $confirmEmail) {
    echo json_encode(['success' => false, 'message' => 'New email addresses do not match.']);
    exit;
}

// 3.2 Logic Validation (Valid format)
if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
    exit;
}

try {
    // 4. Verify Identity (Current Password)
    $stmtUser = $db->prepare("SELECT email, password_hash, full_name, auth_provider FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    // Security check: Only local accounts can change email this way
    if ($user['auth_provider'] !== 'local') {
        echo json_encode(['success' => false, 'message' => 'SSO accounts cannot change email manually.']);
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect current password.']);
        exit;
    }

    // Check if new email is the same as current
    if ($newEmail === $user['email']) {
        echo json_encode(['success' => false, 'message' => 'The new email is the same as your current one.']);
        exit;
    }

    // 5. Check if New Email is Taken
    $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmtCheck->execute([$newEmail]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'This email is already registered.']);
        exit;
    }

    // 6. Generate Token and Save Request
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Remove any previous pending requests for this user
    $db->prepare("DELETE FROM email_change_requests WHERE user_id = ?")->execute([$userId]);

    $stmtInsert = $db->prepare("INSERT INTO email_change_requests (user_id, new_email, token, expires_at) VALUES (?, ?, ?, ?)");
    $stmtInsert->execute([$userId, $newEmail, $token, $expiresAt]);

    // 7. Send Verification Email
    $sent = EmailHelper::sendEmailChangeVerification($newEmail, $user['full_name'], $token);

    if ($sent) {
        echo json_encode(['success' => true, 'message' => 'A verification link has been sent to your new email. Please check your inbox.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not send verification email. Please try again later.']);
    }

} catch (Exception $e) {
    error_log("API Email Change Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A system error occurred.']);
}
