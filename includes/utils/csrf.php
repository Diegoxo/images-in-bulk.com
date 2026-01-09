<?php
/**
 * CSRF Protection Utility
 */

function generateCsrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token)
{
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function renderCsrfField()
{
    $token = generateCsrfToken();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?>