<?php
require_once __DIR__ . '/includes/config.php';

try {
    $db = getDB();
    
    // Add Wompi specific columns to subscriptions table
    $sql = "ALTER TABLE subscriptions 
            ADD COLUMN IF NOT EXISTS wompi_payment_source_id VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS wompi_customer_email VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS last_charge_status VARCHAR(50) NULL;";
    
    $db->exec($sql);
    
    echo "Database updated successfully for Wompi Recurring Payments.";
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage();
}
