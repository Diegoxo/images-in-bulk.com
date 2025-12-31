<?php include 'includes/pages-config/login-config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Images In Bulk</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Main Header Section -->
    <?php include 'includes/layouts/header.php'; ?>

    <main class="container auth-page-main">
        <?php
        $isSignUp = isset($_GET['mode']) && $_GET['mode'] === 'signup';
        $title = $isSignUp ? "Create your account" : "Welcome back";
        $subtitle = $isSignUp ? "Join us and start generating images in bulk." : "Sign in to start creating magic with AI.";
        $footerText = $isSignUp ? "Already have an account?" : "Don't have an account?";
        $footerLink = $isSignUp ? "login.php" : "login.php?mode=signup";
        $footerAction = $isSignUp ? "Login here" : "Sign up here";
        ?>
        <section class="glass animate-fade section-card auth-card">
            <h1 class="section-title"><?php echo $title; ?></h1>
            <p class="subtitle"><?php echo $subtitle; ?></p>

            <div class="auth-options">
                <a href="auth/callback.php?provider=Google" class="btn-auth btn-google">
                    <img src="https://www.google.com/favicon.ico" alt="Google" width="18">
                    Sign in with Google
                </a>

                <a href="auth/callback.php?provider=MicrosoftGraph" class="btn-auth btn-microsoft">
                    <img src="https://www.microsoft.com/favicon.ico" alt="Microsoft" width="18">
                    Sign in with Microsoft
                </a>
            </div>

            <p class="auth-footer">
                <?php echo $footerText; ?> <a
                    href="<?php echo $footerLink; ?>"><strong><?php echo $footerAction; ?></strong></a>
            </p>

            <p class="auth-footer" style="margin-top: 1rem; opacity: 0.7;">
                By continuing, you agree to our <a href="terms.php">Terms</a> and <a href="privacy.php">Privacy
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