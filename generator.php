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
                            <option value="gpt-image-1.5" <?php echo !$isPro ? 'disabled' : ''; ?>>
                                GPT Image 1.5 <?php echo !$isPro ? '(PRO)' : ''; ?>
                            </option>
                            <option value="gpt-image-1-mini" <?php echo !$isPro ? 'disabled' : ''; ?>>
                                GPT Image 1.0 (Mini) <?php echo !$isPro ? '(PRO)' : ''; ?>
                            </option>
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
                            <option value="1:1" selected>1:1 (Square)</option>
                            <option value="16:9" <?php echo !$isPro ? 'disabled' : ''; ?>>
                                16:9 (Horizontal) <?php echo !$isPro ? '(PRO)' : ''; ?>
                            </option>
                            <option value="9:16" <?php echo !$isPro ? 'disabled' : ''; ?>>
                                9:16 (Vertical) <?php echo !$isPro ? '(PRO)' : ''; ?>
                            </option>
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
                            <div class="mb-1 text-center text-secondary fs-sm">
                                Free Trial: <strong><?php echo $freeImagesCount; ?>/<?php echo $freeLimit; ?></strong> images
                                used
                                <div class="progress-small">
                                    <div class="progress-small-fill <?php echo ($freeImagesCount >= $freeLimit - 1) ? 'bg-danger' : 'bg-primary'; ?>"
                                        style="width: <?php echo ($freeImagesCount / $freeLimit) * 100; ?>%;">
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
                            <div class="alert-danger mb-1">
                                <p class="mb-05 fs-sm">🔒 Free Limit Reached (3/3)</p>
                                <a href="pricing" class="btn-auth btn-primary full-width">Upgrade for Unlimited</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Usuario No Logueado -->
                        <a href="login" class="btn-auth btn-primary generate-main-btn no-decor">
                            Start Generation 🚀
                        </a>
                        <button type="button" class="btn-auth glass btn-stop not-allowed opacity-7" disabled>
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
                            <h2 class="card-title m-0">Results</h2>
                            <span id="generation-counter" class="counter-badge hidden-btn">0 / 0</span>
                        </div>
                        <div class="header-right">
                            <button id="clear-gallery" class="btn-auth glass btn-clear">Clear History</button>
                        </div>
                    </div>

                    <div id="generation-warning-text" class="alert-warning hidden-btn">
                        <p class="m-0 fs-sm">
                            ⚠️ <strong>Generation in progress.</strong> Please don't close this tab or navigate away.
                        </p>
                    </div>

                    <div id="progress-bar-container" class="progress-container hidden-btn">
                        <div id="progress-bar" class="progress-fill"></div>
                    </div>

                    <div id="generation-spinner" class="spinner-container hidden-btn">
                        <div class="spinner"></div>
                        <p class="mt-3 fs-sm opacity-75">Generating your images...</p>
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
                        <h2 class="card-title m-0">Previous Generations</h2>
                        <button id="clear-history" class="btn-auth glass btn-clear">Clear All History</button>
                    </div>
                    <div id="history-grid" class="image-grid">
                        <!-- Past images will be moved here -->
                    </div>
                    <div class="btn-group download-area">
                        <button id="download-zip-history" class="btn-auth btn-primary hidden-btn">
                            Download All (.zip)
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