<?php
/**
 * Auth Guard
 * Protects routes by checking login and email verification status.
 */

class AuthGuard
{
    /**
     * Ensures the user is logged in and verified.
     */
    public static function requireVerified()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $db = getDB();

        // 1. Check Verification Status
        $stmt = $db->prepare("SELECT email_verified, auth_provider FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            session_destroy();
            header('Location: login');
            exit;
        }

        // 2. Block if not verified AND not a social login
        // (Social logins like Google are considered pre-verified)
        if ($user['auth_provider'] === 'local' && !$user['email_verified']) {
            header('Location: login?error=Please verify your email address before continuing.');
            exit;
        }
    }
}
