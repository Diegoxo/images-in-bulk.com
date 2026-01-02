<?php
require_once 'includes/config.php';
include 'includes/pages-config/generator-config.php';

// Verificamos el estado del usuario (sin bloquear acceso)
$isPro = false;
$freeImagesCount = 0;
$freeLimit = 3;

if (isset($_SESSION['user_id'])) {
    $db = getDB();

    // Check Subscription
    $stmt = $db->prepare("SELECT plan_type, status FROM subscriptions WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$_SESSION['user_id']]);
    $sub = $stmt->fetch();
    if ($sub && $sub['plan_type'] === 'pro') {
        $isPro = true;
    }

    // Count Generated Images (for free users)
    if (!$isPro) {
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM generations WHERE user_id = ?");
        $stmtCount->execute([$_SESSION['user_id']]);
        $freeImagesCount = $stmtCount->fetchColumn();
    }
}
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
        <!-- Input Section -->
        <section class="glass animate-fade section-card">
            <h1 class="section-title">Bulk image generator</h1>
            <p class="subtitle">Enter your prompts and let AI do the magic.</p>

            <form id="generator-form">
                <div class="form-group">
                    <div class="label-with-counter">
                        <label for="prompts">Prompts List*</label>
                        <span id="prompts-count" class="line-counter">0 Prompts</span>
                    </div>
                    <textarea id="prompts" placeholder="e.g.: A space cat with a neon helmet..." required></textarea>
                    <div class="field-actions">
                        <button type="button" id="clear-prompts" class="btn-text-action">Clear Prompts</button>
                    </div>
                </div>

                <div class="form-group">
                    <div class="label-with-counter">
                        <label for="filenames">Image Names (Optional)</label>
                        <span id="filenames-count" class="line-counter">0 Names</span>
                    </div>
                    <textarea id="filenames" placeholder="e.g.: cat_01..."></textarea>
                    <div class="field-actions">
                        <button type="button" id="clear-filenames" class="btn-text-action">Clear Names</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="custom_style">Custom Style / Modifiers</label>
                    <textarea id="custom_style"
                        placeholder="e.g.: Cyberpunk style, hyperrealistic, 8k, bokeh effect"></textarea>
                </div>

                <div class="config-grid">
                    <div class="form-group">
                        <label>Model</label>
                        <select id="model">
                            <option value="dall-e-3" selected>DALL-E 3</option>
                            <option value="gpt-image-1.5">GPT Image 1.5</option>
                            <option value="gpt-image-1-mini">GPT Image 1.0 (Mini)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Format</label>
                        <select id="format">
                            <option value="png">PNG</option>
                            <option value="jpg">JPG</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Resolution</label>
                        <select id="resolution">
                            <option value="1:1">1:1 (Square)</option>
                            <option value="16:9">16:9 (Horizontal)</option>
                            <option value="9:16">9:16 (Vertical)</option>
                        </select>
                    </div>
                </div>

                <div class="btn-group-vertical">
                    <?php if ($isPro): ?>
                        <button type="submit" id="generate-btn" class="btn-auth btn-primary generate-main-btn">
                            Start Generation 🚀
                        </button>
                        <button type="button" id="stop-btn" class="btn-auth glass btn-stop">
                            Stop
                        </button>
                    <?php elseif (isset($_SESSION['user_id'])): ?>
                        <!-- Usuario Logueado (Free) -->
                        <?php if ($freeImagesCount < $freeLimit): ?>
                            <div class="free-limit-info"
                                style="margin-bottom: 1rem; text-align: center; color: var(--text-secondary); font-size: 0.85rem;">
                                Free Trial: <strong><?php echo $freeImagesCount; ?>/<?php echo $freeLimit; ?></strong> images
                                used
                                <div
                                    style="width: 100%; background: rgba(255,255,255,0.1); height: 4px; border-radius: 2px; margin-top: 6px; overflow: hidden;">
                                    <div
                                        style="width: <?php echo ($freeImagesCount / $freeLimit) * 100; ?>%; background: <?php echo ($freeImagesCount >= $freeLimit - 1) ? '#ef4444' : 'var(--primary)'; ?>; height: 100%; border-radius: 2px; transition: width 0.3s;">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" id="generate-btn" class="btn-auth btn-primary generate-main-btn">
                                Generate (Free) 🎨
                            </button>
                            <button type="button" id="stop-btn" class="btn-auth glass btn-stop">
                                Stop
                            </button>
                        <?php else: ?>
                            <!-- Limite Alcanzado -->
                            <div class="locked-feature glass"
                                style="padding: 1rem; text-align: center; border: 1px solid #ef4444; border-radius: 12px; background: rgba(239, 68, 68, 0.1);">
                                <p style="margin-bottom: 0.5rem; font-size: 0.9rem; color: #fca5a5;">🔒 Free Limit Reached (3/3)
                                </p>
                                <a href="pricing" class="btn-auth btn-primary full-width">Upgrade for Unlimited</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Usuario No Logueado (Apariencia Normal pero Redirige) -->
                        <a href="login" class="btn-auth btn-primary generate-main-btn" style="text-decoration: none;">
                            Start Generation 🚀
                        </a>
                        <button type="button" class="btn-auth glass btn-stop" disabled
                            style="opacity: 0.5; cursor: not-allowed;">
                            Stop
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Preview Section -->
            <section class="preview-area">
                <div class="glass animate-fade section-card">
                    <div class="results-header">
                        <div class="header-left">
                            <h2 style="font-size: 1.5rem;">Results</h2>
                            <span id="generation-counter" class="counter-badge">0 / 0</span>
                        </div>
                        <div class="header-right">
                            <button id="clear-gallery" class="btn-auth glass btn-clear">Clear History</button>
                        </div>
                    </div>

                    <div id="generation-warning-text" style="display: none; text-align: center; padding: 0.75rem; margin-bottom: 1rem; background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 8px;">
                        <p style="margin: 0; color: #fbbf24; font-size: 0.9rem;">
                            ⚠️ <strong>Generation in progress.</strong> Please don't close this tab or navigate away.
                        </p>
                    </div>

                    <div id="progress-bar-container" class="progress-container">
                        <div id="progress-bar" class="progress-fill"></div>
                    </div>

                    <div id="image-grid" class="image-grid">
                        <!-- Images will appear here -->
                        <div class="empty-state">
                            Your generated images will appear here.
                        </div>
                    </div>

                    <div class="btn-group download-area">
                        <button id="download-zip" class="btn-auth btn-primary hidden-btn">
                            Download Full Batch (ZIP)
                        </button>
                    </div>
                </div>
            </section>

            <!-- History Section -->
            <section id="history-section" class="preview-area hidden-btn">
                <div class="glass animate-fade section-card">
                    <div class="results-header">
                        <h2 style="font-size: 1.5rem;">Previous Generations</h2>
                    </div>
                    <div id="history-grid" class="image-grid">
                        <!-- Past images will be moved here -->
                    </div>
                    <div class="btn-group download-area">
                        <button id="download-zip-history" class="btn-auth btn-primary hidden-btn">
                            Download Complete History (ZIP)
                        </button>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- Main Footer Section -->
    <?php include 'includes/layouts/footer.php'; ?>

    <!-- Modular Script Injection -->
    <script>
        const CURRENT_USER_ID = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "'guest'"; ?>;
    </script>
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>