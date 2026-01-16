<?php
/**
 * Billing Page Controller
 * Handles user authentication, card data fetching from Wompi, and pre-renders UI components.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/security_headers.php';
require_once __DIR__ . '/../wompi-helper.php';
require_once __DIR__ . '/../utils/security.php';

// Generate/Get CSRF Token
$csrfToken = CSRF::generate();
if (!isset($_SESSION['user_id'])) {
    $redirectPrefix = $pathPrefix ?? '';
    header('Location: ' . $redirectPrefix . 'login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$db = getDB();
$pageTitle = "Billing & Payments";

$hasCard = false;
$cardDetailsHtml = '';
$paymentMethodActionHtml = '';

try {
    // 2. Fetch Subscription Info
    $stmt = $db->prepare("SELECT wompi_payment_source_id, status FROM subscriptions WHERE user_id = ? AND status IN ('active', 'cancelled')");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    $paymentSourceId = $subscription['wompi_payment_source_id'] ?? null;
    $subStatusText = $subscription['status'] ?? 'inactive';
    $hasCard = !empty($paymentSourceId);

    if ($hasCard) {
        $wompi = new WompiHelper();
        $sourceData = $wompi->getPaymentSource($paymentSourceId);

        if (isset($sourceData['data']['public_data'])) {
            $cardInfo = $sourceData['data']['public_data'];
            $brand = htmlspecialchars($cardInfo['brand'] ?? 'Primary Card');
            $lastFour = htmlspecialchars($cardInfo['last_four'] ?? '');

            $noteText = ($subStatusText === 'cancelled')
                ? 'Your subscription is cancelled and will not renew.'
                : 'Used for your PRO subscription renewals.';

            $cardDetailsHtml = '
                <div class="payment-method-card">
                    <div class="card-details">
                        <span class="card-icon">💳</span>
                        <div>
                            <p class="card-brand-name">' . $brand . ' ending in **** ' . $lastFour . '</p>
                            <p class="card-usage-tip">' . $noteText . '</p>
                        </div>
                    </div>
                    <button onclick="deleteCard()" class="btn-auth btn-danger card-remove-btn">
                        Remove Card
                    </button>
                </div>';

            if ($subStatusText !== 'cancelled') {
                $cardDetailsHtml .= '
                <div class="card-removal-note">
                    <p>
                        💡 <strong>Note:</strong> If you remove this card, your PRO subscription will not renew next month.
                    </p>
                </div>';

                $paymentMethodActionHtml = '
                    <section class="animate-fade replace-card-section">
                        <p>Want to change your card?</p>
                        <button onclick="toggleAddCard()" class="btn-auth glass" id="toggle-btn">
                            Replace Primary Card
                        </button>
                    </section>';
            }
        }
    } else {
        $cardDetailsHtml = '
            <div class="billing-empty-state">
                <p class="empty-text">You don\'t have any registered payment methods yet.</p>
                <button onclick="toggleAddCard()" class="btn-auth btn-primary">
                    Add New Card
                </button>
                <p class="footer-tip">
                    * This will securely save your card for future PRO renewals.
                </p>
            </div>';
    }

} catch (Exception $e) {
    error_log("Billing Controller Error: " . $e->getMessage());
    $cardDetailsHtml = '<div class="alert-danger"><p>Error loading billing info. Please try again later.</p></div>';
}

// 4. Cancellation Layout Component (PRO users only)
$cancelActionHtml = '';

require_once __DIR__ . '/../utils/subscription_helper.php';
$subStatus = getUserSubscriptionStatus($userId);

// Only show cancellation button if they are PRO AND currently 'active' (not already cancelled)
if ($subStatus['isPro'] && ($subscription['status'] ?? '') === 'active') {
    $cancelActionHtml = '
    <div class="cancel-subscription-row">
        <button id="cancel-subscription-btn" class="cancel-link">Cancel subscription</button>
    </div>';
}

// 5. Environmental Variables for JS
$wompiPubKey = WOMPI_PUBLIC_KEY;
$wompiApiUrl = (strpos($wompiPubKey, 'pub_test') !== false)
    ? 'https://sandbox.wompi.co/v1'
    : 'https://production.wompi.co/v1';
