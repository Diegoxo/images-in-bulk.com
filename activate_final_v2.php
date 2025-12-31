<?php
require_once 'includes/config.php';
try {
    $db = getDB();

    // Asegurar columna plan_type
    $stmt = $db->query("SHOW COLUMNS FROM subscriptions LIKE 'plan_type'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE subscriptions ADD COLUMN plan_type VARCHAR(20) DEFAULT 'free' AFTER user_id");
    }

    // Activar al usuario 1 (Diego)
    $db->exec("INSERT INTO subscriptions (user_id, plan_type, status, current_period_end) 
               VALUES (1, 'pro', 'active', DATE_ADD(NOW(), INTERVAL 1 MONTH)) 
               ON DUPLICATE KEY UPDATE plan_type='pro', status='active', current_period_end = DATE_ADD(NOW(), INTERVAL 1 MONTH)");

    echo "SUCCESS: PRO activated";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
