<?php
/**
 * Forgot Password Handler
 */
require_once 'includes/config.php';
require_once 'includes/utils/csrf.php';
require_once 'includes/utils/email_helper.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die("Security Error: Invalid token.");
    }

    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    if ($email) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ? AND auth_provider = 'local'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $db->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);
            $stmtInsert = $db->prepare("INSERT INTO password_resets (user_id, reset_token, expires_at) VALUES (?, ?, ?)");
            $stmtInsert->execute([$user['id'], $token, $expires]);

            if (EmailHelper::sendPasswordReset($email, $user['full_name'], $token)) {
                $message = "Recovery email sent! Check your inbox.";
            } else {
                $error = "Could not send email. Please try again later.";
            }
        } else {
            // Standard security practice: Don't reveal if email exists
            $message = "If that email is registered, you will receive a reset link shortly.";
        }
    } else {
        $error = "Please enter a valid email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Images In Bulk</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php
    include 'includes/layouts/header.php';
    ?>
    <main class="container auth-page-main">
        <section class="glass animate-fade section-card auth-card">
            <h1 class="section-title">Reset Password</h1>
            <p class="subtitle">Enter your email and we'll send you a link to get back into your account.</p>

            <?php if ($message): ?>
                <div class="auth-success-alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="auth-error-alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <?php renderCsrfField(); ?>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="name@example.com" required
                        class="auth-input">
                </div>
                <button type="submit" class="btn-auth btn-primary full-width">Send recovery link</button>
            </form>
            <p class="auth-footer">
                Wait, I remember! <a href="login"><strong>Back to Login</strong></a>
            </p>
        </section>
    </main>
    <?php include 'includes/layouts/footer.php'; ?>
</body>

</html>