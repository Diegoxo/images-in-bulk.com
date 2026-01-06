<?php
require_once __DIR__ . '/includes/config.php';

try {
    $db = getDB();

    // Remove Stripe specific columns from subscriptions table
    // We check if they exist before dropping to avoid errors
    $sql = "ALTER TABLE subscriptions 
            DROP COLUMN IF EXISTS stripe_customer_id,
            DROP COLUMN IF EXISTS stripe_subscription_id;";

    $db->exec($sql);

    echo "Stripe columns removed successfully. Database is now cleaned up.";
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
