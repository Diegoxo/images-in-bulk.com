<header class="main-header">
    <nav>
        <a href="index.php" class="logo">
            <img src="assets/img/bulk-image-generator-logo.avif" alt="bulk-image-generator-logo" height="32">
            <span>images in bulk</span>
        </a>
        <div class="nav-links">
            <a href="index.php" class="btn-auth glass">Home</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="user-greeting">Hi,
                    <strong><?php echo explode(' ', $_SESSION['user_name'])[0]; ?></strong></span>
                <a href="generator.php" class="btn-auth btn-primary">Generator</a>
                <a href="logout.php" class="btn-auth glass">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-auth glass">Login</a>
                <a href="login.php?mode=signup" class="btn-auth btn-primary">Sign up</a>
            <?php endif; ?>
        </div>
    </nav>
</header>