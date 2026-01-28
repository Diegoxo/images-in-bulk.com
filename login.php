<?php
require_once 'includes/controllers/login_controller.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Images In Bulks</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Main Header Section -->
    <?php include 'includes/layouts/header.php'; ?>

    <main class="container auth-page-main">
        <section class="glass animate-fade section-card auth-card">
            <h1 class="section-title"><?php echo $authTitle; ?></h1>
            <p class="subtitle"><?php echo $authSubtitle; ?></p>

            <a href="auth/callback.php?provider=Google" class="btn-auth btn-google">
                <img src="https://www.google.com/favicon.ico" alt="Google" width="18">
                <?php echo $googleActionText; ?>
            </a>

            <div class="dropdown-divider auth-divider">
                <span class="auth-divider-span">OR</span>
            </div>

            <form action="auth/manual_login.php" method="POST" class="auth-form">
                <input type="hidden" name="mode" value="<?php echo $modeValue; ?>">
                <?php renderCsrfField(); ?>

                <?php echo $errorHtml; ?>

                <?php if ($showNameField): ?>
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" placeholder="John Doe" required
                            class="auth-input">
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="name@example.com" required
                        class="auth-input">
                </div>

                <div class="form-group">
                    <div class="label-group">
                        <label for="password">Password</label>
                            <?php if (!$showNameField): ?>
                            <a href="forgot-password" class="forgot-link">Forgot Password?</a>
                            <?php endif; ?>
                    </div>
                    <input type="password" id="password" name="password" placeholder="••••••••" required
                        class="auth-input">
                </div>

                <?php if ($showNameField): ?>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required
                            class="auth-input">
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn-auth btn-primary full-width mt-05">
                    <?php echo $submitButtonText; ?>
                </button>
            </form>
            </div>

            <p class="auth-footer">
                <?php echo $footerText; ?> <a
                    href="<?php echo $footerLink; ?>"><strong><?php echo $footerAction; ?></strong></a>
            </p>

            <p class="auth-footer auth-footer-disclaimer">
                By continuing, you agree to our <a href="terms">Terms</a> and <a href="privacy">Privacy
                    Policy</a>.
            </p>
        </section>
    </main>

    <!-- Main Footer Section -->
    <?php include 'includes/layouts/footer.php'; ?>

    <!-- Modular Script Injection -->
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>