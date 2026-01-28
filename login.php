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

            <?php echo $renderMainContent; ?>

            <?php echo $renderFooterHtml; ?>

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