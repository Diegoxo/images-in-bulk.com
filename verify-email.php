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

        .status-header {
            margin-bottom: 2rem;
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
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }

        .icon-success {
            color: #10b981;
            border-color: rgba(16, 185, 129, 0.3);
            background: rgba(16, 185, 129, 0.05);
            animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .icon-error {
            color: #ef4444;
            border-color: rgba(239, 68, 68, 0.3);
            background: rgba(239, 68, 68, 0.05);
            animation: shake 0.5s ease-in-out;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-5px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(5px);
            }
        }

        .redirect-timer {
            margin-top: 2rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .timer-dot {
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.5);
                opacity: 0.5;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .card-bg-glow {
            position: absolute;
            top: -20%;
            left: -20%;
            width: 140%;
            height: 140%;
            background: radial-gradient(circle, rgba(147, 51, 234, 0.08) 0%, transparent 60%);
            z-index: -1;
            pointer-events: none;
        }
    </style>
</head>

<body>
    <div class="verify-container">
        <section class="glass animate-fade verify-card">
            <div class="card-bg-glow"></div>

            <?php if ($success): ?>
                <div class="status-header">
                    <div class="icon-circle icon-success">✓</div>
                    <h1 class="section-title mb-1">Account Verified</h1>
                    <p class="subtitle"><?php echo $success; ?></p>
                </div>

                <div class="alert-success-glass mb-2">
                    Success! You can now start generating high-quality images.
                </div>

                <p class="fs-sm opacity-75">You will be redirected to your dashboard in a few seconds.</p>

                <div class="redirect-timer">
                    <span class="timer-dot"></span>
                    <span>Redirecting to Generator...</span>
                </div>

                <script>
                        setTimeout(() => { window.location.href = 'generator'; }, 4000);
                </script>

            <?php else: ?>
                <div class="status-header">
                    <div class="icon-circle icon-error">✕</div>
                    <h1 class="section-title mb-1">Verification Failed</h1>
                    <p class="subtitle"><?php echo $error; ?></p>
                </div>

                <div class="alert-danger-glass mb-3">
                    Don't worry, you can try requesting a new link from your login page.
                </div>

                <a href="login" class="btn-auth btn-primary full-width">Back to Login</a>
            <?php endif; ?>
        </section>
    </div>
</body>

</html>