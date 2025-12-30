<?php
require_once 'includes/config.php';

try {
    $db = getDB();
    echo "✅ Connection to database established successfully.\n\n";

    $tables = ['users', 'subscriptions', 'usage_log', 'generations'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists.\n";
        } else {
            echo "❌ Table '$table' is MISSING.\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
