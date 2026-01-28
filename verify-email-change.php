<?php
/**
 * Verify Email Change
 * Finalizes the email change process after user clicks the link.
 */
require_once 'includes/config.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$error = '';
$success = '';

if (empty($token)) {
    $error = "Invalid or expired verification link.";
} else {
    $db = getDB();

    try {
        // 1. Fetch Request
        $stmt = $db->prepare("SELECT * FROM email_change_requests WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $request = $stmt->fetch();

        if ($request) {
            $userId = $request['user_id'];
            $newEmail = $request['new_email'];

            // 2. Final Security Check: Ensure email is still available
            $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmtCheck->execute([$newEmail, $userId]);
            if ($stmtCheck->fetch()) {
                $error = "Sorry, this email address is now being used by another account.";
            } else {
                // 3. Update User Email
                $stmtUpdate = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmtUpdate->execute([$newEmail, $userId]);

                // 4. Delete Request
                $db->prepare("DELETE FROM email_change_requests WHERE user_id = ?")->execute([$userId]);

                $success = "Your email has been updated successfully! You can now use <strong>$newEmail</strong> to log in.";

                // 5. Update session if user is logged in
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                    $_SESSION['user_email'] = $newEmail;
                }
            }
        } else {
            $error = "This verification link is invalid or has expired. Please request a new email change from your dashboard.";
        }
    } catch (Exception $e) {
        error_log("Verify Email Change Error: " . $e->getMessage());
        $error = "A system error occurred. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Change Verified | Images In Bulks</title>
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

        .alert-success-glass {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #a7f3d0;
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body>
    <div class="verify-container">
        <section class="glass animate-fade verify-card">
            <div class="verify-branding mb-2">
                <img src="assets/img/bulk-image-generator-logo.avif" alt="Images In Bulks Logo"
                    style="width: 60px; height: auto; margin-bottom: 0.5rem; filter: drop-shadow(0 0 10px rgba(147, 51, 234, 0.3));">
                <div
                    style="font-weight: 700; letter-spacing: 1px; color: var(--text-primary); font-size: 1.1rem; text-transform: uppercase;">
                    Images In Bulks</div>
            </div>

            <?php if ($success): ?>
                <div class="icon-circle icon-success">✓</div>
                <h1 class="section-title mb-1">Email Verified!</h1>
                <p class="subtitle mb-2"><?php echo $success; ?></p>

                <div class="alert-success-glass mb-2">
                    Process complete. You can now close this tab and return to your dashboard, or click below to jump
                    straight in.
                </div>

                <button id="btn-goto-generator" class="btn-verify-action">
                    Go to Generator & Close this Tab
                </button>

                <script>
                    document.getElementById('btn-goto-generator').addEventListener('click', function () {
                        // 1. Notify other tabs (optional, good practice)
                        if ('BroadcastChannel' in window) {
                            const authChannel = new BroadcastChannel('auth_verification');
                            authChannel.postMessage({ status: 'success' });
                        }

                        // 2. Redirect fallback + Close attempt
                        setTimeout(() => {
                            window.location.href = 'dashboard/';
                            window.close();
                        }, 300);
                    });
                </script>

            <?php else: ?>
                <div class="icon-circle icon-error">✕</div>
                <h1 class="section-title mb-1">Verification Failed</h1>
                <p class="subtitle mb-3"><?php echo $error; ?></p>
                <a href="dashboard/" class="btn-auth glass full-width">Back to Dashboard</a>
            <?php endif; ?>
        </section>
    </div>
</body>

</html>