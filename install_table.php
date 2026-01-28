<?php
/**
 * Database Table Installer
 * RUN THIS ONCE to create the necessary table for email changes.
 */
require_once 'includes/config.php';

echo "<h1>Database Setup</h1>";

try {
    $db = getDB();

    // Get current DB name
    $stmt = $db->query("SELECT DATABASE()");
    $dbName = $stmt->fetchColumn();
    echo "<p>Connected to database: <strong>$dbName</strong></p>";

    $sql = "
    CREATE TABLE IF NOT EXISTS email_change_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        new_email VARCHAR(255) NOT NULL,
        token VARCHAR(100) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $db->exec($sql);
    echo "<p style='color: green;'>✅ Table 'email_change_requests' created or checked successfully.</p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>