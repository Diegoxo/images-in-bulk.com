<?php
/**
 * Generator Page Controller
 * Handles business logic, session checks, and subscription status for the generator view.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/security_headers.php';
require_once __DIR__ . '/../utils/subscription_helper.php';
require_once __DIR__ . '/../utils/security.php';
include __DIR__ . '/../pages-config/generator-config.php';

// Generate/Get CSRF Token
$csrfToken = CSRF::generate();

// Auth Check
$userId = $_SESSION['user_id'] ?? null;

// Get Subscription/Usage Data
$userStatus = getUserSubscriptionStatus($userId);

// Extract variables for the view
$isPro = $userStatus['isPro'];
$credits = $userStatus['credits'] ?? 0;
$freeImagesCount = $userStatus['freeImagesCount'];
$freeLimit = $userStatus['freeLimit'];

// 1. Determine User State for UI
if (!$userId) {
    $generatorState = 'GUEST';
} elseif ($isPro) {
    $generatorState = 'PRO';
} elseif ($freeImagesCount < $freeLimit) {
    $generatorState = 'FREE_ACTIVE';
} else {
    $generatorState = 'FREE_REACHED';
}

// 2. Common UI Helper Flags
$proDisabledAttr = !$isPro ? 'disabled' : '';
$proLabelSuffix = !$isPro ? ' (PRO)' : '';
$freeTrialProgress = ($freeLimit > 0) ? ($freeImagesCount / $freeLimit) * 100 : 0;
$freeTrialColorClass = ($freeImagesCount >= $freeLimit - 1) ? 'bg-danger' : 'bg-primary';

// 3. Section Visibility Flags
$showResultsSection = (bool) $userId;
$showHistorySection = (bool) $userId;

// 4. Page Metadata
$viewTitle = $pageTitle ?? 'AI Image Generator';

// 5. Client-side Config
$currentUserIdJs = $userId ? $userId : "'guest'";
$isUserLoggedIn = (bool) $userId;
$freeLimitJs = (int) $freeLimit;
$freeCountJs = (int) $freeImagesCount;

// 6. Pre-render Button Group HTML (To keep view logic-free)
ob_start();

// Show Free Trial counter to ALL non-pro logged in users
if ($userId && !$isPro) {
    ?>
    <div class="mb-1 text-center text-secondary fs-sm">
        Free Trial: <strong id="free-trial-counter-text"><?php echo $freeImagesCount; ?>/<?php echo $freeLimit; ?></strong>
        used
        <div class="progress-small">
            <div id="free-trial-progress-bar" class="progress-small-fill <?php echo $freeTrialColorClass; ?>"
                style="width: <?php echo $freeTrialProgress; ?>%;"></div>
        </div>
    </div>
    <?php
}

if ($generatorState === 'PRO') {
    ?>
    <button type="submit" id="generate-btn" class="btn-auth btn-primary generate-main-btn">Start Generation 🚀</button>
    <button type="button" id="stop-btn" class="btn-auth glass btn-stop">Stop</button>
    <?php
} elseif ($generatorState === 'FREE_ACTIVE') {
    ?>
    <button type="submit" id="generate-btn" class="btn-auth btn-primary generate-main-btn">Generate (Free) 🎨</button>
    <button type="button" id="stop-btn" class="btn-auth glass btn-stop">Stop</button>
    <?php
} elseif ($generatorState === 'FREE_REACHED') {
    ?>
    <div class="alert-danger mb-1">
        <p class="mb-05 fs-sm">🔒 Free Limit Reached</p>
        <a href="pricing" class="btn-auth btn-primary full-width">Upgrade for Unlimited</a>
    </div>
    <?php
} else {
    ?>
    <a href="login" class="btn-auth btn-primary generate-main-btn no-decor">Start Generation 🚀</a>
    <button type="button" class="btn-auth glass btn-stop not-allowed opacity-7" disabled>Stop</button>
    <?php
}
$renderButtonsHtml = ob_get_clean();

// 7. Pre-render Sections
$resultsSectionHtml = '';
$historySectionHtml = '';

if ($showResultsSection) {
    ob_start();
    ?>
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
                    <strong>Generation in progress. </strong>Don't close this tab or navigate away.
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
                <div class="empty-state">Your generated images will appear here.</div>
            </div>

            <div class="btn-group download-area">
                <button id="download-zip" class="btn-auth btn-primary hidden-btn">Download Full Batch (ZIP)</button>
            </div>
        </div>
    </section>

    <section id="history-section" class="preview-area hidden-btn">
        <div class="glass animate-fade section-card">
            <div class="results-header">
                <h2 class="card-title m-0">Previous Generations</h2>
                <button id="clear-history" class="btn-auth glass btn-clear">Clear All History</button>
            </div>
            <div id="history-grid" class="image-grid"></div>
            <div class="btn-group download-area">
                <button id="download-zip-history" class="btn-auth btn-primary hidden-btn">Download All (.zip)</button>
            </div>
        </div>
    </section>
    <?php
    $resultsSectionHtml = ob_get_clean();
}
?>