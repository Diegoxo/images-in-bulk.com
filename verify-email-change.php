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
    <title>Verify Email Change | Images In Bulks</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .verify-page {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .verify-card {
            max-width: 500px;
            width: 100%;
            text-align: center;
            padding: 3rem;
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }

        .icon-success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 2px solid rgba(16, 185, 129, 0.2);
        }

        .icon-error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 2px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>

<body>
    <?php include 'includes/layouts/header.php'; ?>

    <main class="verify-page">
        <div class="glass verify-card animate-fade">
            <?php if ($success): ?>
                <div class="icon-circle icon-success">✓</div>
                <h1 class="gradient-text mb-1">Email Verified!</h1>
                <p class="mb-2 text-secondary">
                    <?php echo $success; ?>
                </p>
                <a href="dashboard/" class="btn-auth btn-primary full-width">Go to Dashboard</a>
            <?php else: ?>
                <div class="icon-circle icon-error">✕</div>
                <h1 class="text-accent mb-1">Verification Failed</h1>
                <p class="mb-2 text-secondary">
                    <?php echo $error; ?>
                </p>
                <a href="dashboard/" class="btn-auth glass full-width">Back to Dashboard</a>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/layouts/footer.php'; ?>
</body>

</html>