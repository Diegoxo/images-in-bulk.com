<?php
/**
 * Email Verification Processor
 * Handles the user clicking the link in their email.
 */
require_once 'includes/config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    $error = "Invalid verification link.";
} else {
    try {
        $db = getDB();

        // 1. Verify token exists and hasn't expired
        $stmt = $db->prepare("SELECT user_id, id FROM email_verifications WHERE verification_token = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $verif = $stmt->fetch();

        if (!$verif) {
            $error = "The verification link is invalid or has expired. Please try signing up again or contact support.";
        } else {
            $userId = $verif['user_id'];
            $verifId = $verif['id'];

            // 2. Mark user as verified
            $db->beginTransaction();

            $stmtUser = $db->prepare("UPDATE users SET email_verified = TRUE WHERE id = ?");
            $stmtUser->execute([$userId]);

            // 3. Delete the used token
            $stmtDel = $db->prepare("DELETE FROM email_verifications WHERE id = ?");
            $stmtDel->execute([$verifId]);

            $db->commit();

            // 4. Fetch user details for auto-login
            $stmtDetails = $db->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmtDetails->execute([$userId]);
            $user = $stmtDetails->fetch();

            session_start();
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $user['full_name'];

            $success = "Email verified successfully! Welcome to Images in Bulk.";
        }
    } catch (Exception $e) {
        if (isset($db))
            $db->rollBack();
        error_log("Verification Error: " . $e->getMessage());
        $error = "A system error occurred. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | Images In Bulks</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="auth-page-main d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="glass section-card auth-card text-center" style="max-width: 450px;">
            <h1 class="section-title">Account Verification</h1>

            <?php if ($success): ?>
                <div class="success-glass p-1 mb-2">
                    <p class="m-0">✅
                        <?php echo $success; ?>
                    </p>
                </div>
                <p class="subtitle">Redirecting you to the generator in 3 seconds...</p>
                <script>
                    setTimeout(() => { window.location.href = 'generator'; }, 3000);
                </script>
            <?php else: ?>
                <div class="alert-danger p-1 mb-2">
                    <p class="m-0">❌
                        <?php echo $error; ?>
                    </p>
                </div>
                <a href="login" class="btn-auth btn-primary full-width">Back to Login</a>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>