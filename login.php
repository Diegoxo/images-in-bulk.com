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

            <a href="auth/callback.php?provider=Google" class="btn-auth btn-google">
                <img src="https://www.google.com/favicon.ico" alt="Google" width="18">
                <?php echo $isSignUp ? "Sign up with Google" : "Sign in with Google"; ?>
            </a>

            <div class="dropdown-divider"
                style="margin: 1.5rem 0; width: 100%; position: relative; text-align: center;">
                <span
                    style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #0f172a; padding: 0 10px; color: var(--text-muted); font-size: 0.8rem;">OR</span>
            </div>

            <form action="auth/manual_login.php" method="POST"
                style="width: 100%; display: flex; flex-direction: column; gap: 1rem;">
                <input type="hidden" name="mode" value="<?php echo $isSignUp ? 'signup' : 'login'; ?>">

                <?php if (isset($_GET['error'])): ?>
                    <div
                        style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #fca5a5; padding: 0.5rem; border-radius: 8px; font-size: 0.9rem;">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($isSignUp): ?>
                    <div class="form-group" style="text-align: left;">
                        <label for="full_name" style="font-size: 0.9rem;">Full Name</label>
                        <input type="text" id="full_name" name="full_name" placeholder="John Doe" required
                            style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-main);">
                    </div>
                <?php endif; ?>

                <div class="form-group" style="text-align: left;">
                    <label for="email" style="font-size: 0.9rem;">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="name@example.com" required
                        style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-main);">
                </div>

                <div class="form-group" style="text-align: left;">
                    <label for="password" style="font-size: 0.9rem;">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required
                        style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; color: var(--text-main);">
                </div>

                <button type="submit" class="btn-auth btn-primary full-width" style="margin-top: 0.5rem;">
                    <?php echo $isSignUp ? "Create Account" : "Sign In"; ?>
                </button>
            </form>
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