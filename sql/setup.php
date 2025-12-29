<?php
// Setup script to initialize the database

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Connect without DB first
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents(__DIR__ . '/schema.sql');

    // Execute the SQL file (multi-query)
    $pdo->exec($sql);

    echo "SUCCESS: Database 'images_in_bulk' and tables initialized successfully.\n";
} catch (PDOException $e) {
    echo "ERROR: Could not initialize database. " . $e->getMessage() . "\n";
    exit(1);
}
?>