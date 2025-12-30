<?php include 'includes/pages-config/app-config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | Images-In-Bulk</title>
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
                </div>

                <div class="form-group">
                    <div class="label-with-counter">
                        <label for="filenames">Image Names (Optional)</label>
                        <span id="filenames-count" class="line-counter">0 Names</span>
                    </div>
                    <textarea id="filenames" placeholder="e.g.: cat_01..."></textarea>
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
                    <button type="submit" id="generate-btn" class="btn-auth btn-primary generate-main-btn">
                        Start Generation
                    </button>
                    <button type="button" id="stop-btn" class="btn-auth glass btn-stop">
                        Stop
                    </button>
                </div>
            </form>
        </section>

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
    </main>

    <!-- Main Footer Section -->
    <?php include 'includes/layouts/footer.php'; ?>

    <!-- Modular Script Injection -->
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>