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

/**
 * Rate Limiting Utility
 * Prevents multiple rapid requests to sensitive actions.
 */
class RateLimiter
{
    /**
     * Check if the user is exceeding request limits for a specific action
     * @param string $action Key for the action (e.g. 'delete_card')
     * @param int $secondsCooldown Time to wait between requests
     * @return bool True if allowed, False if throttled
     */
    public static function check($action, $secondsCooldown = 10)
    {
        $key = "throttle_{$action}";
        $currentTime = time();

        if (isset($_SESSION[$key])) {
            $lastRequest = $_SESSION[$key];
            if (($currentTime - $lastRequest) < $secondsCooldown) {
                return false;
            }
        }

        $_SESSION[$key] = $currentTime;
        return true;
    }
}

/**
 * Helper functions for direct usage in views (Backward Compatibility)
 */
function renderCsrfField()
{
    $token = CSRF::getToken();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function verifyCsrfToken($token)
{
    return CSRF::validate($token);
}
