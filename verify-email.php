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
    $error = "Invalid verification link. Please request a new one.";
} else {
    try {
        $db = getDB();

        // 1. Verify token exists and hasn't expired
        $stmt = $db->prepare("SELECT user_id, id FROM email_verifications WHERE verification_token = ? AND expires_at > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $verif = $stmt->fetch();

        if (!$verif) {
            $error = "The verification link is invalid or has expired.";
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

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $user['full_name'];

            $success = "Welcome, " . explode(' ', $user['full_name'])[0] . "! Your account is now active.";
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
    <title>Account Verified | Images In Bulk</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .verify-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: radial-gradient(circle at top right, rgba(147, 51, 234, 0.05), transparent),
                radial-gradient(circle at bottom left, rgba(79, 70, 229, 0.05), transparent);
        }

        .verify-card {
            max-width: 480px;
            width: 100%;
            text-align: center;
            padding: 3rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .icon-success {
            color: #10b981;
            border-color: rgba(16, 185, 129, 0.3);
            background: rgba(16, 185, 129, 0.05);
        }

        .icon-error {
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.3);
            background: rgba(239, 68, 68, 0.05);
        }

        .btn-verify-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            background: var(--primary);
            color: white;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-verify-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(147, 51, 234, 0.4);
        }
    </style>
</head>

<body>
    <div class="verify-container">
        <section class="glass animate-fade verify-card">
            <div class="verify-branding mb-2">
                <img src="assets/img/bulk-image-generator-logo.avif" alt="Images In Bulks Logo" style="width: 60px; height: auto; margin-bottom: 0.5rem; filter: drop-shadow(0 0 10px rgba(147, 51, 234, 0.3));">
                <div style="font-weight: 700; letter-spacing: 1px; color: var(--text-primary); font-size: 1.1rem; text-transform: uppercase;">Images In Bulks</div>
            </div>

            <?php if ($success): ?>
                <div class="icon-circle icon-success">✓</div>
                <h1 class="section-title mb-1">Email Verified!</h1>
                <p class="subtitle mb-2"><?php echo $success; ?></p>

                <div class="alert-success-glass mb-2">
                    System ready. You can now close this tab and return to your original page, or click below to jump
                    straight in.
                </div>

                <button id="btn-goto-generator" class="btn-verify-action">
                    Go to Generator & Close this Tab
                </button>

                <script>
                    document.getElementById('btn-goto-generator').addEventListener('click', function () {
                        // 1. Notify the original tab (Tab A)
                        if ('BroadcastChannel' in window) {
                            const authChannel = new BroadcastChannel('auth_verification');
                            authChannel.postMessage({ status: 'success' });
                        }

                        // 2. Try to redirect this tab too as a fallback
                        setTimeout(() => {
                            window.location.href = 'generator';
                            // 3. Try to close (Browsers might block this, but we try)
                            window.close();
                        }, 500);
                    });
                </script>

            <?php else: ?>
                <div class="icon-circle icon-error">✕</div>
                <h1 class="section-title mb-1">Verification Error</h1>
                <p class="subtitle mb-3"><?php echo $error; ?></p>
                <a href="login" class="btn-auth btn-primary full-width">Back to Login</a>
            <?php endif; ?>
        </section>
    </div>
</body>

</html>