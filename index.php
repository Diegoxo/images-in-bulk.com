<?php
require_once 'includes/config.php';
include 'includes/pages-config/index-config.php';
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

    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container hero-container">
                <div class="hero-content animate-fade-up">
                    <span class="badge">Powered by OpenAI</span>
                    <h1>Generate AI Images <span class="gradient-text">In Bulk</span> Effortlessly</h1>
                    <p class="subtitle">Stop generating one by one. Enter your prompts list and get dozens of stunning
                        images in seconds. Perfect for content creators and marketers.</p>
                    <div class="hero-actions">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="generator" class="btn-auth btn-primary btn-large">Start Creating Now ✨</a>
                            <a href="dashboard" class="btn-auth glass btn-large">Go to Dashboard</a>
                        <?php else: ?>
                            <a href="login" class="btn-auth btn-primary btn-large">Start Creating Now</a>
                            <a href="#features" class="btn-auth glass btn-large">How it works</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hero-image animate-fade-up" style="animation-delay: 0.2s;">
                    <div class="glass image-container">
                        <img src="assets/img/landing_hero_preview.webp" alt="Bulk Generation Preview"
                            class="rounded-image">
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="features-section container">
            <h2 class="section-title text-center">Why choose <span class="gradient-text">Images In Bulk</span>?</h2>
            <div class="features-grid">
                <div class="feature-card glass">
                    <div class="feature-icon">🚀</div>
                    <h3>Massive Generation</h3>
                    <p>Enter hundreds of prompts at once. Our system processes them sequentially using the latest AI
                        models.</p>
                </div>
                <div class="feature-card glass">
                    <div class="feature-icon">📂</div>
                    <h3>One-Click Download</h3>
                    <p>Export all your generated images in a single ZIP file, perfectly named and organized for your
                        projects.</p>
                </div>
                <div class="feature-card glass">
                    <div class="feature-icon">🎨</div>
                    <h3>Custom Styles</h3>
                    <p>Apply specific modifiers or styles to your entire batch to maintain visual consistency across all
                        images.</p>
                </div>
                <div class="feature-card glass">
                    <div class="feature-icon">🛠️</div>
                    <h3>Total Control</h3>
                    <p>Select your preferred model (DALL-E 3), format (PNG/JPG), and resolution (1:1, 16:9, 9:16).</p>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="glass cta-container animate-fade">
                    <h2>Ready to scale your creativity?</h2>
                    <p>Join hundreds of creators who are already saving hours every week.</p>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="generator" class="btn-auth btn-primary btn-large">Open Generator ✨</a>
                    <?php else: ?>
                        <a href="login" class="btn-auth btn-primary btn-large">Get Started for Free</a>
                    <?php endif; ?>
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