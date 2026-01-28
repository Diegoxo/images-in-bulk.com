<?php
/**
 * AJAX Endpoint to check if an email is already verified.
 * Used for real-time redirection after email verification.
 */
require_once '../includes/config.php';

header('Content-Type: application/json');

$email = $_GET['email'] ?? '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['verified' => false]);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT email_verified FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && $user['email_verified']) {
        echo json_encode(['verified' => true]);
    } else {
        echo json_encode(['verified' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['verified' => false, 'error' => $e->getMessage()]);
}
