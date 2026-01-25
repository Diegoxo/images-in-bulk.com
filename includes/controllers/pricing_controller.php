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
    $billingCycle = $subStatus['billing_cycle'];

    if (!$isPro) {
        $wompiData = generateWompiSignature($userId, 8500000, 'COP', 'BULK');
        $wompiDataAnnual = generateWompiSignature($userId, 85000000, 'COP', 'ANNUAL');
    } else {
        $wompiDataAddon = generateWompiSignature($userId, 8500000, 'COP', 'ADDON');
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

// --- Dynamic Redirect URL for Wompi (Fixed Concatenation) ---
$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];
$basePath = '/' . trim(URL_BASE, '/') . '/';
$basePath = str_replace('//', '/', $basePath); // Ensure single slash
$redirectUrl = $proto . '://' . $host . $basePath . 'api/wompi-callback.php';

// --- Pro Plan Action (Monthly) ---
if (!$isLoggedIn) {
    $proPlanAction = '<a href="login.php?mode=signup" class="btn-auth btn-primary full-width">Sign up to buy</a>';
} elseif ($isPro && $billingCycle === 'yearly') {
    $proPlanAction = '
        <div class="subscription-status success-glass opacity-7">
            <p>Included in your Annual plan</p>
        </div>';
} elseif ($isPro && $billingCycle === 'monthly') {
    $proPlanAction = '
        <div class="subscription-status success-glass">
            <p>✨ You are a Monthly PRO member!</p>
            <a href="generator" class="btn-auth btn-primary full-width">Go to Generator</a>
        </div>';
} elseif ($wompiData) {
    $proPlanAction = renderWompiButton($wompiData, 'Secure Payment by Wompi', $redirectUrl);
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
} elseif ($isPro && $billingCycle === 'monthly' && $wompiDataAnnual) {
    $proAnnualAction = renderWompiButton($wompiDataAnnual, 'Upgrade to Annual & Save 🚀', $redirectUrl);
} elseif ($wompiDataAnnual) {
    $proAnnualAction = renderWompiButton($wompiDataAnnual, 'Pay Annually & Save', $redirectUrl);
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
        </ul>' . renderWompiButton($wompiDataAddon, 'Buy 55k Credits', $redirectUrl) . '
    </div>';
}

// --- DIAGNOSTIC LOG READER ---
$debugHtml = '';
$webhookLog = __DIR__ . '/../../wompi_last_webhook.json';
$callbackLog = __DIR__ . '/../../wompi_last_callback.json';

if (file_exists($webhookLog)) {
    $debugHtml .= '<div class="mt-2 glass p-2" style="max-width:800px;margin:2rem auto;"><h4>Last Webhook Payload</h4><pre style="white-space:pre-wrap;word-break:break-all;font-size:11px;color:#a3e635;background:rgba(0,0,0,0.5);padding:1rem;">' . htmlspecialchars(file_get_contents($webhookLog)) . '</pre></div>';
}
if (file_exists($callbackLog)) {
    $debugHtml .= '<div class="mt-2 glass p-2" style="max-width:800px;margin:2rem auto;"><h4>Last Callback Params</h4><pre style="white-space:pre-wrap;word-break:break-all;font-size:11px;color:#60a5fa;background:rgba(0,0,0,0.5);padding:1rem;">' . htmlspecialchars(file_get_contents($callbackLog)) . '</pre></div>';
}


/**
 * Helper to render Wompi Widget HTML
 */
function renderWompiButton($data, $label, $redirectUrl)
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
                data-redirect-url="' . htmlspecialchars($redirectUrl) . '"
                data-signature:integrity="' . htmlspecialchars($data['signature']) . '"></script>
        </form>
    </div>';
}