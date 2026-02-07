<?php
require_once 'includes/config.php';
include 'includes/pages-config/terms-config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Images In Bulks</title>
    <?php include 'includes/layouts/meta-head.php'; ?>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Main Header Section -->
    <?php include 'includes/layouts/header.php'; ?>

    <main class="container legal-page">
        <section class="glass animate-fade section-card">
            <h1>Terms of Service</h1>
            <p class="last-updated">Last Updated: December 2025</p>

            <h3>1. Acceptence of terms</h3>
            <p>By using Images In Bulks, you agree to these terms. If you do not agree, please do not use our services.
            </p>

            <h3>2. Use of AI</h3>
            <p>Our service uses OpenAI's API. You are responsible for the prompts you enter and the use of the generated
                images, ensuring they comply with OpenAI's usage policies.</p>

            <h3>3. Payments and Refunds</h3>
            <p>Subscriptions are billed monthly. Due to the nature of digital credits used for image generation, we
                generally do not offer refunds once credits are consumed.</p>
        </section>
    </main>

    <!-- Main Footer Section -->
    <?php include 'includes/layouts/footer.php'; ?>

    <!-- Modular Script Injection -->
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>