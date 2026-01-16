<?php
/**
 * CSRF Protection Utility
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class CSRF
{
    /**
     * Generate a CSRF token and store it in the session
     */
    public static function generate()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate the provided CSRF token against the one in the session
     */
    public static function validate($token)
    {
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get the current token
     */
    public static function getToken()
    {
        return $_SESSION['csrf_token'] ?? self::generate();
    }
}
