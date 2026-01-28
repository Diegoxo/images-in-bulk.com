<?php
require_once 'includes/config.php';
echo "DB_NAME: " . DB_NAME . "\n";
echo "Resolved DB_NAME from ENV: " . ($_ENV['DB_NAME'] ?? 'NOT SET') . "\n";
?>