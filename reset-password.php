<?php
/**
 * Reset Password Processor
 */
require_once 'includes/config.php';
require_once 'includes/utils/csrf.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$success = false;

if (empty($token)) {
    header('Location: login');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT user_id FROM password_resets WHERE reset_token = ? AND expires_at > NOW() LIMIT 1");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = "The reset link is invalid or has expired.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die("Security Error.");
    }

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $reset['user_id']]);
            $db->prepare("DELETE FROM password_resets WHERE reset_token = ?")->execute([$token]);
            $db->commit();
            $success = true;
        } catch (Exception $e) {
            $db->rollBack();
            $error = "System error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Password | Images In Bulk</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php
    include 'includes/layouts/header.php';
    ?>
    <main class="container auth-page-main">
        <section class="glass animate-fade section-card auth-card">
            <h1 class="section-title">New Password</h1>

            <?php if ($success): ?>
                <div class="auth-success-alert">Password updated successfully! You can now log in.</div>
                <a href="login" class="btn-auth btn-primary full-width">Go to Login</a>
            <?php else: ?>
                <p class="subtitle">Set a strong password for your account.</p>
                <?php if ($error): ?>
                    <div class="auth-error-alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <?php renderCsrfField(); ?>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" placeholder="••••••••" required
                            class="auth-input">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required
                            class="auth-input">
                    </div>
                    <button type="submit" class="btn-auth btn-primary full-width">Update Password</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
    <?php include 'includes/layouts/footer.php'; ?>
</body>

</html>