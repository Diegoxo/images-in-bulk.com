<?php
/**
 * Billing Page Controller
 * Handles user authentication, card data fetching from Wompi, and pre-renders UI components.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/security_headers.php';
require_once __DIR__ . '/../wompi-helper.php';

// 1. Auth Guard
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
    $stmt = $db->prepare("SELECT wompi_payment_source_id FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    $paymentSourceId = $subscription['wompi_payment_source_id'] ?? null;
    $hasCard = !empty($paymentSourceId);

    if ($hasCard) {
        // 3. Fetch Card Info from Wompi
        $wompi = new WompiHelper();
        $sourceData = $wompi->getPaymentSource($paymentSourceId);

        if (isset($sourceData['data']['public_data'])) {
            $cardInfo = $sourceData['data']['public_data'];
            $brand = htmlspecialchars($cardInfo['brand'] ?? 'Primary Card');
            $lastFour = htmlspecialchars($cardInfo['last_four'] ?? '');

            // 4. Pre-render Card View
            $cardDetailsHtml = '
                <div class="payment-method-card">
                    <div class="card-details">
                        <span class="card-icon">💳</span>
                        <div>
                            <p class="card-brand-name">' . $brand . ' ending in **** ' . $lastFour . '</p>
                            <p class="card-usage-tip">Used for your PRO subscription renewals.</p>
                        </div>
                    </div>
                    <button onclick="deleteCard()" class="btn-auth btn-danger card-remove-btn">
                        Remove Card
                    </button>
                </div>
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
    } else {
        // Empty State
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
$renderCancelButtonHtml = '';

require_once __DIR__ . '/../utils/subscription_helper.php';
$subStatus = getUserSubscriptionStatus($userId);

if ($subStatus['isPro']) {
    $cancelActionHtml = '
    <div class="cancel-subscription-row">
        <button id="cancel-subscription-btn" class="cancel-link">Cancel subscription</button>
    </div>';

    ob_start();
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('cancel-subscription-btn');
            if (!btn) return;
            btn.addEventListener('click', async () => {
                if (!confirm('Are you sure you want to cancel your PRO subscription? You will lose access to PRO features immediately.')) return;
                btn.disabled = true;
                btn.innerText = 'Cancelling...';
                try {
                    const prefix = window.API_PREFIX || '';
                    const res = await fetch(prefix + 'api/cancel-subscription.php', { method: 'POST' });
                    const data = await res.json();
                    if (data.success) {
                        alert('Subscription cancelled successfully.');
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.error);
                        btn.disabled = false;
                        btn.innerText = 'Cancel subscription';
                    }
                } catch (e) {
                    alert('Network error. Please try again.');
                    btn.disabled = false;
                    btn.innerText = 'Cancel subscription';
                }
            });
        });
    </script>
    <?php
    $renderCancelButtonHtml = ob_get_clean();
}

// 5. Environmental Variables for JS
$wompiPubKey = WOMPI_PUBLIC_KEY;
$wompiApiUrl = (strpos($wompiPubKey, 'pub_test') !== false)
    ? 'https://sandbox.wompi.co/v1'
    : 'https://production.wompi.co/v1';
