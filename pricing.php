<?php include 'includes/pages-config/pricing-config.php'; ?>
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

    <main class="container">
        <section class="pricing-section animate-fade">
            <h1 class="section-title text-center">Simple and <span class="gradient-text">Transparent</span> Pricing</h1>
            <p class="subtitle text-center">Choose the plan that fits your needs.</p>

            <div class="pricing-grid">
                <!-- Free Plan -->
                <div class="pricing-card glass">
                    <h3>Free</h3>
                    <div class="price">$0<span>/month</span></div>
                    <ul class="pricing-features">
                        <li>5 images per month</li>
                        <li>DALL-E 3 Model</li>
                        <li>Standard resolution</li>
                        <li>Community Support</li>
                    </ul>
                    <a href="login.php" class="btn-auth glass full-width">Get Started</a>
                </div>

                <!-- Pro Plan -->
                <div class="pricing-card glass popular">
                    <div class="popular-badge">Most Popular</div>
                    <h3>Pro Plan</h3>
                    <div class="price">$5<span>/month</span></div>
                    <ul class="pricing-features">
                        <li><strong>Unlimited</strong> batch generation</li>
                        <li>Priority Queue</li>
                        <li>All resolutions (1:1, 16:9, 9:16)</li>
                        <li>Premium Support</li>
                        <li>Advanced Style Modifiers</li>
                    </ul>
                    <a href="login.php" class="btn-auth btn-primary full-width">Subscribe Now</a>
                </div>
            </div>
        </section>
    </main>

    <!-- Main Footer Section -->
    <?php include 'includes/layouts/footer.php'; ?>

    <!-- Modular Script Injection -->
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>