<?php
require_once 'includes/controllers/pricing_controller.php';
?>
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
                    <div class="price-dual">$0</div>
                    <div class="price-billing"><span>month</span></div>
                    <ul class="pricing-features">
                        <li>3 images (One-time trial)</li>
                        <li>DALL-E 3 Model</li>
                        <li>Standard resolution</li>
                        <li>Community Support</li>
                    </ul>
                    <a href="login" class="btn-auth glass full-width">Get Started</a>
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
                    <?php if ($isLoggedIn): ?>
                        <?php if ($isPro): ?>
                            <div class="subscription-status success-glass">
                                <p>✨ You are a PRO member!</p>
                                <a href="generator" class="btn-auth btn-primary full-width">Go to Generator</a>
                            </div>
                        <?php elseif (isset($wompiData) && is_array($wompiData)): ?>
                            <div class="payment-box">
                                <p class="payment-label">Secure Payment by Wompi</p>
                                <form>
                                    <script src="https://checkout.wompi.co/widget.js" data-render="button"
                                        data-public-key="<?php echo htmlspecialchars($wompiData['publicKey']); ?>"
                                        data-currency="<?php echo htmlspecialchars($wompiData['currency']); ?>"
                                        data-amount-in-cents="<?php echo htmlspecialchars($wompiData['amountInCents']); ?>"
                                        data-reference="<?php echo htmlspecialchars($wompiData['reference']); ?>"
                                        data-signature:integrity="<?php echo htmlspecialchars($wompiData['signature']); ?>"></script>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="alert-danger mt-2">
                                <p class="m-0 fs-sm">Unable to load payment system.</p>
                                <p class="m-0 fs-sm opacity-7">Please reload or contact support.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php?mode=signup" class="btn-auth btn-primary full-width">Sign up to buy</a>
                    <?php endif; ?>
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
                    <?php if ($isLoggedIn): ?>
                        <?php if ($isPro && $billingCycle === 'yearly'): ?>
                            <div class="subscription-status success-glass">
                                <p>✨ You are an ANNUAL PRO!</p>
                                <a href="generator" class="btn-auth btn-primary full-width">Go to Generator</a>
                            </div>
                        <?php elseif ($isPro): ?>
                            <div class="subscription-status success-glass opacity-7">
                                <p>Plan: Monthly PRO</p>
                            </div>
                        <?php elseif (isset($wompiDataAnnual) && is_array($wompiDataAnnual)): ?>
                            <div class="payment-box">
                                <p class="payment-label">Pay Annually & Save</p>
                                <form>
                                    <script src="https://checkout.wompi.co/widget.js" data-render="button"
                                        data-public-key="<?php echo htmlspecialchars($wompiDataAnnual['publicKey']); ?>"
                                        data-currency="<?php echo htmlspecialchars($wompiDataAnnual['currency']); ?>"
                                        data-amount-in-cents="<?php echo htmlspecialchars($wompiDataAnnual['amountInCents']); ?>"
                                        data-reference="<?php echo htmlspecialchars($wompiDataAnnual['reference']); ?>"
                                        data-signature:integrity="<?php echo htmlspecialchars($wompiDataAnnual['signature']); ?>"></script>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php?mode=signup" class="btn-auth btn-primary full-width">Sign up to buy</a>
                    <?php endif; ?>
                </div>

                <?php if ($isPro): ?>
                    <!-- Add-on Package for PRO users -->
                    <div class="pricing-card glass">
                        <div class="popular-badge" style="background: var(--accent);">PRO Extra</div>
                        <h3>Extra Credits</h3>
                        <div class="price-dual">$21 USD <span>/ $85.000 COP</span></div>
                        <div class="price-billing">One-time payment</div>
                        <ul class="pricing-features">
                            <li>55,000 Additional Credits</li>
                            <li>No expiration (Rolls over)</li>
                            <li>Immediate activation</li>
                        </ul>
                        <?php if (isset($wompiDataAddon) && is_array($wompiDataAddon)): ?>
                            <div class="payment-box">
                                <p class="payment-label">Buy 55k Credits</p>
                                <form>
                                    <script src="https://checkout.wompi.co/widget.js" data-render="button"
                                        data-public-key="<?php echo htmlspecialchars($wompiDataAddon['publicKey']); ?>"
                                        data-currency="<?php echo htmlspecialchars($wompiDataAddon['currency']); ?>"
                                        data-amount-in-cents="<?php echo htmlspecialchars($wompiDataAddon['amountInCents']); ?>"
                                        data-reference="<?php echo htmlspecialchars($wompiDataAddon['reference']); ?>"
                                        data-signature:integrity="<?php echo htmlspecialchars($wompiDataAddon['signature']); ?>"></script>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Main Footer Section -->
    <?php include 'includes/layouts/footer.php'; ?>

    <!-- Modular Script Injection -->
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>