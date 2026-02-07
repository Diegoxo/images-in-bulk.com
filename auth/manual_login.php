<?php
/**
 * Manual Login/Signup Handler
 */
require_once '../includes/config.php';

require_once '../includes/utils/csrf.php';
require_once '../includes/utils/security.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login');
    exit;
}

// Rate Limiting (Prevent Brute Force)
if (!RateLimiter::check('login_attempt', 2)) {
    header('Location: ../login?error=Too many requests. Please wait a moment.');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    die("Security Error: Invalid or missing CSRF token.");
}

$mode = $_POST['mode'] ?? 'login';
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
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
        $initialName = $_POST['full_name'] ?? '';
        // Clean name to allow only letters and spaces
        $fullName = preg_replace("/[^a-zA-Z\s]/", "", $initialName);

        if (!$fullName) {
            header('Location: ../login?mode=signup&error=Name is required');
            exit;
        }

        // --- NEW: Password Confirmation Validation ---
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            header('Location: ../login?mode=signup&error=Password must be at least 8 characters long');
            exit;
        }

        if ($password !== $confirmPassword) {
            header('Location: ../login?mode=signup&error=Passwords do not match');
            exit;
        }
        // --- END ---

        // 1. Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            header('Location: ../login?mode=signup&error=Email already registered');
            exit;
        }

        // 2. Hash Password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // 3. Create User (unverified)
        $stmt = $db->prepare("INSERT INTO users (email, full_name, password_hash, auth_provider, email_verified) VALUES (?, ?, ?, 'local', FALSE)");
        $stmt->execute([$email, $fullName, $passwordHash]);
        $userId = $db->lastInsertId();

        // 4. Verification Token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmtToken = $db->prepare("INSERT INTO email_verifications (user_id, verification_token, expires_at) VALUES (?, ?, ?)");
        $stmtToken->execute([$userId, $token, $expiresAt]);

        // 5. Send Verification Email
        require_once '../includes/utils/email_helper.php';
        $sent = EmailHelper::sendVerification($email, $fullName, $token);

        if ($sent) {
            header('Location: ../login?success=Account created! Please check your email to verify your account.&verify_email=' . urlencode($email));
        } else {
            header('Location: ../login?warning=Account created but we could not send the verification email. Please contact support.');
        }
        exit;

    } else {
        // --- LOGIN LOGIC ---

        // 1. Find User
        $stmt = $db->prepare("SELECT id, full_name, password_hash, auth_provider, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header('Location: ../login?error=Invalid email or password');
            exit;
        }

        // 2. Check Password
        if (empty($user['password_hash'])) {
            header('Location: ../login?error=Please login with ' . ucfirst($user['auth_provider']));
            exit;
        }

        if (password_verify($password, $user['password_hash'])) {
            // Check Verification
            if (!$user['email_verified']) {
                header('Location: ../login?error=Please verify your email address first. Check your inbox.');
                exit;
            }

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
    error_log("Manual Auth Error: " . $e->getMessage());
    header('Location: ../login?mode=' . $mode . '&error=System error. Please try again.');
    exit;
}
