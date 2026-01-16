<?php
require_once 'includes/config.php';
include 'includes/pages-config/privacy-config.php';
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

    <main class="container legal-page">
        <section class="glass animate-fade section-card">
            <h1>Privacy Policy</h1>
            <p class="last-updated">Last Updated: December 2025</p>

            <h3>1. Data we collect</h3>
            <p>We respect your privacy. We only collect the necessary information to provide our services, such as your
                email address and name through Google authentication.</p>

            <h3>2. How we use your data</h3>
            <p>Your information is used only to manage your subscription, keep your generation history (locally), and
                contact you for support if needed.</p>

            <h3>3. Cookies</h3>
            <p>We use session cookies to keep you logged in and improve your experience.</p>
        </section>
    </main>

    <!-- Main Footer Section -->
    <?php include 'includes/layouts/footer.php'; ?>

    <!-- Modular Script Injection -->
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>