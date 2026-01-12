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

// Execute Business Logic
if ($isLoggedIn) {
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];

        // Check Subscription Status
        $subStatus = getUserSubscriptionStatus($userId);
        $isPro = $subStatus['isPro'];
        $credits = $subStatus['credits'];

        // If not PRO, prepare Payment Data
        if (!$isPro) {
            // Amount: 85.000 COP (~21 USD)
            $wompiData = generateWompiSignature($userId, 8500000, 'COP');
        }
    }
}
?>