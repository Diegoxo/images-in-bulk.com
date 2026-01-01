<?php
/**
 * Manual Login/Signup Handler
 */
require_once '../includes/config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login');
    exit;
}

$mode = $_POST['mode'] ?? 'login';
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

// Basic Validation
if (!$email || !$password) {
    header('Location: ../login?mode=' . $mode . '&error=Please fill all fields');
    exit;
}

try {
    $db = getDB();

    if ($mode === 'signup') {
        // --- SIGN UP LOGIC ---
        $fullName = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
        if (!$fullName) {
            header('Location: ../login?mode=signup&error=Name is required');
            exit;
        }

        // 1. Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            header('Location: ../login?mode=signup&error=Email already registered');
            exit;
        }

        // 2. Hash Password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // 3. Create User
        $stmt = $db->prepare("INSERT INTO users (email, full_name, password_hash, auth_provider) VALUES (?, ?, ?, 'local')");
        $stmt->execute([$email, $fullName, $passwordHash]);
        $userId = $db->lastInsertId();

        // 4. Auto Login
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $fullName;

        // Redirect to Generator
        header('Location: ../generator');
        exit;

    } else {
        // --- LOGIN LOGIC ---

        // 1. Find User
        $stmt = $db->prepare("SELECT id, full_name, password_hash, auth_provider FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header('Location: ../login?error=Invalid email or password');
            exit;
        }

        // 2. Check Password
        // If user registered via Google, they won't have a password hash
        if (empty($user['password_hash'])) {
            header('Location: ../login?error=Please login with ' . ucfirst($user['auth_provider']));
            exit;
        }

        if (password_verify($password, $user['password_hash'])) {
            // Success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            header('Location: ../generator');
            exit;
        } else {
            header('Location: ../login?error=Invalid email or password');
            exit;
        }
    }

} catch (Exception $e) {
    // Log error securely in production, show simple msg here
    header('Location: ../login?mode=' . $mode . '&error=System error. Please try again.');
    exit;
}
