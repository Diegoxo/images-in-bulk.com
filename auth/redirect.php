<?php
/**
 * Auth Redirector
 * Initiates social login flow and sets optional actions.
 */
require_once '../includes/config.php';

$provider = isset($_GET['provider']) ? $_GET['provider'] : 'Google';
$action = isset($_GET['action']) ? $_GET['action'] : 'login';

// Security: If action is delete_account, ensure a session exists
if ($action === 'delete_account' && !isset($_SESSION['user_id'])) {
    header('Location: ../login');
    exit;
}

// 1. Set the action flag in session for the callback
$_SESSION['auth_action'] = $action;
$_SESSION['auth_provider_flow'] = $provider;

// 2. Redirect to callback.php which will trigger HybridAuth
header('Location: callback.php?provider=' . $provider);
exit;
