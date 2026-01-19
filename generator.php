<?php
require_once 'includes/controllers/generator_controller.php';
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
                            <option value="gpt-image-1.5" <?php echo $proDisabledAttr; ?>>
                                GPT Image 1.5 <?php echo $proLabelSuffix; ?>
                            </option>
                            <option value="gpt-image-1-mini" <?php echo $proDisabledAttr; ?>>
                                GPT Image 1.0 (Mini) <?php echo $proLabelSuffix; ?>
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
                            <option value="16:9" <?php echo $proDisabledAttr; ?>>
                                16:9 (Horizontal) <?php echo $proLabelSuffix; ?>
                            </option>
                            <option value="9:16" <?php echo $proDisabledAttr; ?>>
                                9:16 (Vertical) <?php echo $proLabelSuffix; ?>
                            </option>
                        </select>
                    </div>
                </div>

                <div class="btn-group-vertical">
                    <?php echo $renderButtonsHtml; ?>
                </div>
            </form>
        </section>

        <?php echo $resultsSectionHtml; ?>
    </main>

    <!-- Main Footer Section -->
    <?php include 'includes/layouts/footer.php'; ?>

    <!-- Modular Script Injection -->
    <script>
        const CURRENT_USER_ID = <?php echo $currentUserIdJs; ?>;
        window.CSRF_TOKEN = '<?php echo $csrfToken; ?>';
        window.FREE_LIMIT = <?php echo $freeLimitJs; ?>;
        window.CURRENT_FREE_COUNT = <?php echo $freeCountJs; ?>;
    </script>
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>