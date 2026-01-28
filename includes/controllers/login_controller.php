<?php
/**
 * Login Page Controller
 * Manages the state and text content for the login/signup view.
 */
require_once __DIR__ . '/../pages-config/login-config.php';
require_once __DIR__ . '/../utils/security_headers.php';
require_once __DIR__ . '/../utils/csrf.php';

// Ensure session is started for CSRF generation
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Determine Auth Mode
$isSignUp = isset($_GET['mode']) && $_GET['mode'] === 'signup';
$isVerifyState = isset($_GET['verify_email']);
$modeValue = $isSignUp ? 'signup' : 'login';

// 2. Prepare View Strings
$authTitle = $isSignUp ? "Create your account" : "Welcome back";
$authSubtitle = $isSignUp ? "Join us and start generating images in bulk." : "Sign in to start creating magic with AI.";

$googleActionText = $isSignUp ? "Sign up with Google" : "Sign in with Google";
$submitButtonText = $isSignUp ? "Create Account" : "Sign In";

$footerText = $isSignUp ? "Already have an account?" : "Don't have an account?";
$footerLink = $isSignUp ? "login" : "login?mode=signup";
$footerAction = $isSignUp ? "Login here" : "Sign up here";

$pageTitle = $isSignUp ? "Sign Up" : "Sign In";

if ($isVerifyState) {
    $pageTitle = "Verify Email";
    $authTitle = "Check your email";
    $authSubtitle = "We have sent a verification link to <br><strong>" . htmlspecialchars($_GET['verify_email']) . "</strong>";
}

// 3. Handle Messages (Pre-render HTML)
$errorHtml = '';
if (isset($_GET['error'])) {
    $errorHtml = '<div class="auth-error-alert">' . htmlspecialchars($_GET['error']) . '</div>';
} elseif (isset($_GET['success'])) {
    $errorHtml = '<div class="auth-success-alert">' . htmlspecialchars($_GET['success']) . '</div>';
} elseif (isset($_GET['warning'])) {
    $errorHtml = '<div class="auth-warning-alert">' . htmlspecialchars($_GET['warning']) . '</div>';
}

// 4. Pre-render Main Content Section
ob_start();
if ($isVerifyState): ?>
    <div class="verify-waiting-container text-center mt-2">
        <div style="font-size: 3rem; margin-bottom: 1rem;">✉️</div>
        <p class="fs-sm opacity-75 mb-2">
            Please click the link in the email to activate your account. If you don't see it, check your spam folder.
        </p>
        <a href="login" class="btn-auth btn-primary full-width">Back to Login</a>
    </div>

    <script>
        // Polling logic: Check every 3 seconds if the user is verified
        (function () {
            const email = "<?php echo addslashes($_GET['verify_email']); ?>";
            const checkStatus = setInterval(async () => {
                try {
                    const response = await fetch(`api/check-verification-status.php?email=${encodeURIComponent(email)}`);
                    const data = await response.json();

                    if (data.verified) {
                        clearInterval(checkStatus);
                        // Redirect to login with success message so they can auto-login or see the status
                        window.location.href = "login?success=Email verified! You can now sign in.";
                    }
                } catch (error) {
                    console.error("Verification check failed", error);
                }
            }, 3000);
        })();
    </script>
<?php else: ?>
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

        <?php if ($isSignUp): ?>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" placeholder="John Doe" required class="auth-input">
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="name@example.com" required class="auth-input">
        </div>

        <div class="form-group">
            <div class="label-group">
                <label for="password">Password</label>
                <?php if (!$isSignUp): ?>
                    <a href="forgot-password" class="forgot-link">Forgot Password?</a>
                <?php endif; ?>
            </div>
            <input type="password" id="password" name="password" placeholder="••••••••" required class="auth-input">
        </div>

        <?php if ($isSignUp): ?>
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
    <?php
endif;
$renderMainContent = ob_get_clean();

// 5. Pre-render Footer
$renderFooterHtml = '';
if (!$isVerifyState) {
    ob_start();
    ?>
    <p class="auth-footer">
        <?php echo $footerText; ?> <a href="<?php echo $footerLink; ?>"><strong><?php echo $footerAction; ?></strong></a>
    </p>
    <?php
    $renderFooterHtml = ob_get_clean();
}
