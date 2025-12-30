<?php
/**
 * Authentication check helper
 * Redirects to login if user is not authenticated
 */
require_once 'config.php';

function checkAuth()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
?>