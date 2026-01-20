<?php
include 'includes/pages-config/login-config.php';
require_once 'includes/utils/security_headers.php';
require_once 'includes/utils/csrf.php';
// Ensure session is started for CSRF generation
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

            <a href="auth/callback.php?provider=Google" class="btn-auth btn-google">
                <img src="https://www.google.com/favicon.ico" alt="Google" width="18">
                <?php echo $isSignUp ? "Sign up with Google" : "Sign in with Google"; ?>
            </a>

            <div class="dropdown-divider auth-divider">
                <span class="auth-divider-span">OR</span>
            </div>

            <form action="auth/manual_login.php" method="POST" class="auth-form">
                <input type="hidden" name="mode" value="<?php echo $isSignUp ? 'signup' : 'login'; ?>">
                <?php renderCsrfField(); ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="auth-error-alert">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($isSignUp): ?>
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
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required
                        class="auth-input">
                </div>

                <button type="submit" class="btn-auth btn-primary full-width mt-05">
                    <?php echo $isSignUp ? "Create Account" : "Sign In"; ?>
                </button>
            </form>
            </div>

            <p class="auth-footer">
                <?php echo $footerText; ?> <a
                    href="<?php echo $footerLink; ?>"><strong><?php echo $footerAction; ?></strong></a>
            </p>

            <p class="auth-footer auth-footer-disclaimer">
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