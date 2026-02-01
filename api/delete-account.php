<?php
/**
 * API: Delete Account
 * Handles permanent account deletion for logged-in users.
 */
ob_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

// 1. Auth Guard
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$db = getDB();

try {
    // 2. Parse Input
    $input = json_decode(file_get_contents('php://input'), true);
    $confirmText = isset($input['confirm_text']) ? trim($input['confirm_text']) : '';
    $password = isset($input['password']) ? $input['password'] : '';
    $passwordRepeat = isset($input['password_repeat']) ? $input['password_repeat'] : '';

    // 3. Common Validation
    if (strtoupper($confirmText) !== 'DELETE') {
        throw new Exception('Please type DELETE to confirm.');
    }

    // 4. Provider Specific Logic
    $stmtUser = $db->prepare("SELECT auth_provider, password_hash FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    if (!$user) {
        throw new Exception('User not found.');
    }

    if ($user['auth_provider'] === 'local') {
        // --- LOCAL USER VALIDATION ---
        if (empty($password) || empty($passwordRepeat)) {
            throw new Exception('Please enter and confirm your password.');
        }

        if ($password !== $passwordRepeat) {
            throw new Exception('Passwords do not match.');
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception('Incorrect password.');
        }

        // If all good, proceed to delete
        performDeletion($db, $userId);
        echo json_encode(['success' => true, 'redirect' => URL_BASE . '/']);

    } else {
        // --- GOOGLE USER LOGIC ---
        // For Google users, we initiate a re-auth flow with a specific action
        echo json_encode([
            'success' => true,
            'reauth' => true,
            'auth_url' => URL_BASE . '/auth/redirect.php?action=delete_account'
        ]);
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Helper to perform the actual DB deletion and cleanup.
 * Note: Database schema uses ON DELETE CASCADE for all related tables:
 * (email_verifications, credit_bundles, payment_methods, subscriptions, 
 * password_resets, usage_log, generations, email_change_requests).
 */
function performDeletion($db, $userId)
{
    try {
        // Delete the user record. Thanks to "ON DELETE CASCADE", 
        // all related data in other tables will be deleted automatically by MariaDB.
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        // Cleanup session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

    } catch (Exception $e) {
        throw new Exception("Error during account deletion: " . $e->getMessage());
    }
}
