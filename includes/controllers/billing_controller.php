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
    header('Location: ' . $redirectPrefix . 'login');
    exit;
}

$userId = $_SESSION['user_id'];
$db = getDB();
$pageTitle = "Billing & Payments";

$hasCard = false;
$cardDetailsHtml = '';
$paymentMethodActionHtml = '';

try {
    // 2. Fetch Subscription Info for status
    $stmtSub = $db->prepare("SELECT status, billing_cycle, updated_at FROM subscriptions WHERE user_id = ? AND status IN ('active', 'cancelled')");
    $stmtSub->execute([$userId]);
    $subscription = $stmtSub->fetch(PDO::FETCH_ASSOC);
    $subStatusText = $subscription['status'] ?? 'inactive';
    $lastChargeDate = isset($subscription['updated_at']) ? date('M j, Y', strtotime($subscription['updated_at'])) : 'N/A';

    // 2.1 Fetch Credits Info
    $stmtCredits = $db->prepare("SELECT credits, extra_credits FROM users WHERE id = ?");
    $stmtCredits->execute([$userId]);
    $userCreditData = $stmtCredits->fetch(PDO::FETCH_ASSOC);
    $usersCreditsPlan = $userCreditData['credits'] ?? 0;
    $usersCreditsExtra = $userCreditData['extra_credits'] ?? 0;
    $usersCreditsTotal = $usersCreditsPlan + $usersCreditsExtra;

    // 3. Fetch All Registered Cards
    $stmtCards = $db->prepare("SELECT * FROM payment_methods WHERE user_id = ? ORDER BY created_at DESC");
    $stmtCards->execute([$userId]);
    $cards = $stmtCards->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($cards)) {
        $hasCard = true;
        foreach ($cards as $row) {
            $brand = htmlspecialchars($row['brand'] ?: '');
            $last4 = htmlspecialchars($row['last4'] ?: '');
            $isDefault = $row['is_default'];

            // SELF-HEALING: If info is missing, fetch it from Wompi once
            if (empty($brand) || empty($last4)) {
                try {
                    $wompi = new WompiHelper();
                    $sourceData = $wompi->getPaymentSource($row['wompi_payment_source_id']);
                    if (isset($sourceData['data']['public_data'])) {
                        $brand = $sourceData['data']['public_data']['brand'] ?? 'Card';
                        $last4 = $sourceData['data']['public_data']['last_four'] ?? '****';
                        // Update DB for next time
                        $db->prepare("UPDATE payment_methods SET brand = ?, last4 = ? WHERE id = ?")
                            ->execute([$brand, $last4, $row['id']]);

                        $brand = htmlspecialchars($brand);
                        $last4 = htmlspecialchars($last4);
                    }
                } catch (Exception $e) {
                    $brand = 'Primary Card';
                    $last4 = '****';
                }
            }

            if (empty($brand))
                $brand = 'Card';
            if (empty($last4))
                $last4 = '****';

            $cardDetailsHtml .= '
                <div class="payment-method-card ' . ($isDefault ? 'default-card' : '') . '" data-id="' . $row['id'] . '">
                    <div class="card-details">
                        <span class="card-icon">💳</span>
                        <div>
                            <p class="card-brand-name">' . $brand . ' ending in **** ' . $last4 . '</p>
                            ' . ($isDefault ? '<p class="card-usage-tip success-text">Primary for renewals</p>' : '') . '
                        </div>
                    </div>
                    <div class="card-actions">
                        ' . (!$isDefault ? '<button onclick="setDefaultCard(' . $row['id'] . ')" class="btn-text-action">Set as Primary</button>' : '') . '
                        <button onclick="deleteCard(' . $row['id'] . ')" class="cancel-link fs-sm" style="color:var(--text-secondary);">Remove</button>
                    </div>
                </div>';
        }

        $paymentMethodActionHtml = '
            <section class="animate-fade replace-card-section">
                <button onclick="toggleAddCard()" class="btn-auth btn-primary">
                    Add New Payment Method
                </button>
            </section>';
    } else {
        $cardDetailsHtml = '
            <div class="billing-empty-state">
                <p class="empty-text">You don\'t have any registered payment methods yet.</p>
                <button onclick="toggleAddCard()" class="btn-auth btn-primary">
                    Add My First Card
                </button>
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
