<?php
/**
 * Pricing Page Controller
 * Handles all business logic for the pricing page before loading the view.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/security_headers.php';
require_once __DIR__ . '/../utils/subscription_helper.php';
require_once __DIR__ . '/../utils/payment_helper.php';
include __DIR__ . '/../pages-config/pricing-config.php';

// 1. Initialize State
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;
$isPro = false;
$billingCycle = 'monthly';
$wompiData = null;
$wompiDataAnnual = null;
$wompiDataAddon = null;

// 2. Fetch User Status if Logged In
if ($isLoggedIn) {
    $db = getDB();
    $subStatus = getUserSubscriptionStatus($userId);
    $isPro = $subStatus['isPro'];

    $stmtCycle = $db->prepare("SELECT billing_cycle FROM subscriptions WHERE user_id = ? AND status = 'active'");
    $stmtCycle->execute([$userId]);
    $billingCycle = $stmtCycle->fetchColumn() ?: 'monthly';

    if (!$isPro) {
        $wompiData = generateWompiSignature($userId, 8500000, 'COP', 'BULK');
        $wompiDataAnnual = generateWompiSignature($userId, 85000000, 'COP', 'ANNUAL');
    } else {
        $wompiDataAddon = generateWompiSignature($userId, 8500000, 'COP', 'ADDON');
        // If they are PRO but NOT yearly, allow them to upgrade to Annual
        if ($billingCycle !== 'yearly') {
            $wompiDataAnnual = generateWompiSignature($userId, 85000000, 'COP', 'ANNUAL');
        }
    }
}

// 3. Prepare View Actions (HTML)
// --- Free Plan Action ---
if ($isLoggedIn) {
    $freePlanAction = '<a href="generator" class="btn-auth glass full-width">Go to Generator</a>';
} else {
    $freePlanAction = '<a href="login" class="btn-auth glass full-width">Get Started</a>';
}

// --- Pro Plan Action ---
if (!$isLoggedIn) {
    $proPlanAction = '<a href="login.php?mode=signup" class="btn-auth btn-primary full-width">Sign up to buy</a>';
} elseif ($isPro) {
    $proPlanAction = '
        <div class="subscription-status success-glass">
            <p>✨ You are a PRO member!</p>
            <a href="generator" class="btn-auth btn-primary full-width">Go to Generator</a>
        </div>';
} elseif ($wompiData) {
    $proPlanAction = renderWompiButton($wompiData, 'Secure Payment by Wompi');
} else {
    $proPlanAction = '<div class="alert-danger mt-1"><p class="m-0 fs-sm">Payment system unavailable.</p></div>';
}

// --- Pro Annual Action ---
if (!$isLoggedIn) {
    $proAnnualAction = '<a href="login.php?mode=signup" class="btn-auth btn-primary full-width">Sign up to buy</a>';
} elseif ($isPro && $billingCycle === 'yearly') {
    $proAnnualAction = '
        <div class="subscription-status success-glass">
            <p>✨ You are an ANNUAL PRO!</p>
            <a href="generator" class="btn-auth btn-primary full-width">Go to Generator</a>
        </div>';
} elseif ($isPro && $billingCycle !== 'yearly' && $wompiDataAnnual) {
    // Upgrade path for Monthly PRO to Annual PRO
    $proAnnualAction = renderWompiButton($wompiDataAnnual, 'Upgrade to Annual & Save 🚀');
} elseif ($wompiDataAnnual) {
    $proAnnualAction = renderWompiButton($wompiDataAnnual, 'Pay Annually & Save');
} else {
    $proAnnualAction = '';
}

// --- Add-on Package Section ---
$addonPackageHtml = '';
if ($isPro && $wompiDataAddon) {
    $addonPackageHtml = '
    <div class="pricing-card glass">
        <div class="popular-badge extra-badge">PRO Extra</div>
        <h3>Extra Credits</h3>
        <div class="price-dual">$21 USD <span>/ $85.000 COP</span></div>
        <div class="price-billing">One-time payment</div>
        <ul class="pricing-features">
            <li>55,000 Additional Credits</li>
            <li>No expiration (Rolls over)</li>
            <li>Immediate activation</li>
        </ul>' . renderWompiButton($wompiDataAddon, 'Buy 55k Credits') . '
    </div>';
}

/**
 * Helper to render Wompi Widget HTML
 */
function renderWompiButton($data, $label)
{
    return '
    <div class="payment-box">
        <p class="payment-label">' . htmlspecialchars($label) . '</p>
        <form>
            <script src="https://checkout.wompi.co/widget.js" data-render="button"
                data-public-key="' . htmlspecialchars($data['publicKey']) . '"
                data-currency="' . htmlspecialchars($data['currency']) . '"
                data-amount-in-cents="' . htmlspecialchars($data['amountInCents']) . '"
                data-reference="' . htmlspecialchars($data['reference']) . '"
                data-signature:integrity="' . htmlspecialchars($data['signature']) . '"></script>
        </form>
    </div>';
}