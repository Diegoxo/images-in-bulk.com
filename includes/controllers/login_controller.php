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
$modeValue = $isSignUp ? 'signup' : 'login';

// 2. Prepare View Data
$authTitle = $isSignUp ? "Create your account" : "Welcome back";
$authSubtitle = $isSignUp ? "Join us and start generating images in bulk." : "Sign in to start creating magic with AI.";

$googleActionText = $isSignUp ? "Sign up with Google" : "Sign in with Google";
$submitButtonText = $isSignUp ? "Create Account" : "Sign In";

$footerText = $isSignUp ? "Already have an account?" : "Don't have an account?";
$footerLink = $isSignUp ? "login" : "login?mode=signup";
$footerAction = $isSignUp ? "Login here" : "Sign up here";

// 3. Handle Errors (Pre-render HTML to keep view clean)
$errorHtml = '';
if (isset($_GET['error'])) {
    $errorHtml = '<div class="auth-error-alert">' . htmlspecialchars($_GET['error']) . '</div>';
}

// 4. View Flags
$showNameField = $isSignUp;
$pageTitle = $isSignUp ? "Sign Up" : "Sign In";
