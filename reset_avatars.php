<?php
require_once 'includes/config.php';

try {
    $db = getDB();

    // 1. Limpiar todos los avatar_url de la tabla users
    $stmt = $db->prepare("UPDATE users SET avatar_url = NULL");
    $stmt->execute();
    $count = $stmt->rowCount();

    echo "<h3>Success!</h3>";
    echo "<p>All manual profile pictures have been cleared from the database.</p>";
    echo "<p>Total users updated: <strong>$count</strong></p>";
    echo "<p><a href='dashboard'>Go back to Dashboard</a></p>";

} catch (Exception $e) {
    echo "<h3>Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>