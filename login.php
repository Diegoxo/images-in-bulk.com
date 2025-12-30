<?php
$pageTitle = "Login";
include 'includes/layouts/header.php';
?>

<main class="container">
    <section class="glass animate-fade section-card auth-card">
        <h1 class="section-title">Welcome back</h1>
        <p class="subtitle">Sign in to start creating magic with AI.</p>

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
            By signing in, you agree to our <a href="terms.php">Terms</a> and <a href="privacy.php">Privacy Policy</a>.
        </p>
    </section>
</main>

<style>
    .auth-card {
        max-width: 450px !important;
        margin: 4rem auto;
        text-align: center;
    }

    .auth-options {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        margin: 2rem 0;
    }

    .btn-google,
    .btn-microsoft {
        background: white !important;
        color: #1e293b !important;
        border: 1px solid #e2e8f0 !important;
        justify-content: center;
        gap: 12px;
        padding: 0.8rem !important;
        font-size: 1rem !important;
    }

    .btn-google:hover,
    .btn-microsoft:hover {
        background: #f8fafc !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .auth-footer {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-top: 1.5rem;
    }

    .auth-footer a {
        color: var(--primary);
        text-decoration: none;
    }
</style>

<?php include 'includes/layouts/footer.php'; ?>