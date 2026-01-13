<?php
/**
 * Pricing Page Controller
 * Handles all business logic for the pricing page before loading the view.
 */

// Use __DIR__ to define paths relative to this controller file
// This ensures paths work correctly regardless of where the script is included from
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/security_headers.php';
require_once __DIR__ . '/../utils/subscription_helper.php';
require_once __DIR__ . '/../utils/payment_helper.php';
include __DIR__ . '/../pages-config/pricing-config.php';

// Initialize View Variables
$isLoggedIn = isset($_SESSION['user_id']);
$isPro = false;
$credits = 0;
$wompiData = null;
$wompiDataAddon = null;

// Execute Business Logic
if ($isLoggedIn) {
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $db = getDB();

        // Check Subscription Status
        $subStatus = getUserSubscriptionStatus($userId);
        $isPro = $subStatus['isPro'];
        $credits = $subStatus['credits'];

        // Detailed check for cycle
        $stmtCycle = $db->prepare("SELECT billing_cycle FROM subscriptions WHERE user_id = ? AND status = 'active'");
        $stmtCycle->execute([$userId]);
        $billingCycle = $stmtCycle->fetchColumn() ?: 'monthly';

        // If not PRO, prepare Payment Data for Plans
        if (!$isPro) {
            // Monthly: 85.000 COP (~21 USD)
            $wompiData = generateWompiSignature($userId, 8500000, 'COP', 'BULK');

            // Annual: 850.000 COP (~210 USD)
            $wompiDataAnnual = generateWompiSignature($userId, 85000000, 'COP', 'ANNUAL');
        } else {
            // If PRO, prepare Payment Data for Add-on (55,000 credits)
            // Amount: 85.000 COP (~21 USD)
            $wompiDataAddon = generateWompiSignature($userId, 8500000, 'COP', 'ADDON');
        }
    }
}
$wompiDataAnnual = $wompiDataAnnual ?? null;
$billingCycle = $billingCycle ?? 'monthly';
?>