<?php
require_once 'includes/controllers/pricing_controller.php';
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

    <main class="container">
        <section class="pricing-section animate-fade">
            <h1 class="section-title text-center">Simple and <span class="gradient-text">Transparent</span> Pricing</h1>
            <p class="subtitle text-center">Choose the plan that fits your needs.</p>

            <div class="pricing-grid">
                <!-- Free Plan -->
                <div class="pricing-card glass">
                    <h3>Free</h3>
                    <div class="price-dual">$0</div>
                    <div class="price-billing"><span>month</span></div>
                    <ul class="pricing-features">
                        <li>3 images (One-time trial)</li>
                        <li>DALL-E 3 Model</li>
                        <li>Standard resolution</li>
                        <li>Community Support</li>
                    </ul>
                    <?php echo $freePlanAction; ?>
                </div>

                <!-- Pro Plan -->
                <div class="pricing-card glass popular">
                    <div class="popular-badge">Most Popular</div>
                    <h3>Pro Plan</h3>
                    <div class="price-dual">$21 USD <span>/ $85.000 COP</span></div>
                    <div class="price-billing">month</div>
                    <ul class="pricing-features">
                        <li>50,000 Credits / month</li>
                        <li>All resolutions (1:1, 16:9, 9:16)</li>
                        <li>Premium Support</li>
                    </ul>
                    <?php echo $proPlanAction; ?>
                </div>

                <!-- Pro Annual Plan -->
                <div class="pricing-card glass">
                    <div class="popular-badge" style="background: var(--primary);">Best Value</div>
                    <h3>Pro Annual</h3>
                    <div class="price-dual">$210 USD <span>/ $850.000 COP</span></div>
                    <div class="price-billing">year</div>
                    <ul class="pricing-features">
                        <li>50,000 Credits <strong>Monthly</strong></li>
                        <li>Save 2 months!</li>
                        <li>Priority Support</li>
                    </ul>
                    <?php echo $proAnnualAction; ?>
                </div>

                <!-- Conditional Add-on renders here -->
                <?php echo $addonPackageHtml; ?>
            </div>
        </section>
    </main>

    <!-- Main Footer Section -->
    <?php include 'includes/layouts/footer.php'; ?>

    <!-- Modular Script Injection -->
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>