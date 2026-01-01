<?php
require_once 'includes/config.php';

echo "<h1>Updating Database for Manual Login...</h1>";

try {
    $db = getDB();

    // 1. Add password_hash column if it doesn't exist
    try {
        $db->query("SELECT password_hash FROM users LIMIT 1");
        echo "<p>✅ 'password_hash' column already exists.</p>";
    } catch (Exception $e) {
        $db->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL AFTER email");
        echo "<p>✨ Added 'password_hash' column.</p>";
    }

    // 2. Modify auth_provider to allow defaults/be less strict
    // Note: In MariaDB/MySQL, modifying a column definition
    $db->exec("ALTER TABLE users MODIFY auth_provider VARCHAR(50) DEFAULT 'local'");
    echo "<p>✅ Updated 'auth_provider' to allow 'local'.</p>";

    // 3. Modify provider_id to be nullable
    $db->exec("ALTER TABLE users MODIFY provider_id VARCHAR(255) NULL");
    echo "<p>✅ Updated 'provider_id' to be NULLABLE.</p>";

    echo "<h3>🎉 Database Updated Successfully!</h3>";
    echo "<p>You can now use manual login.</p>";
    echo "<a href='index.php'>Go Home</a>";

} catch (PDOException $e) {
    echo "<h2>❌ Error Updating Database:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}

// Self-destruct logic (optional, but good practice per previous conversation)
// unlink(__FILE__); 
?>