<?php
// Configuration file for images-in-bulk.com

define('DB_HOST', 'localhost');
define('DB_NAME', 'images_in_bulk');
define('DB_USER', 'root');
define('DB_PASS', '');

define('OPENAI_API_KEY', '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');

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Composer Autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Auth Configuration (Placeholders for USER to fill)
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');

define('MICROSOFT_CLIENT_ID', 'YOUR_MICROSOFT_CLIENT_ID');
define('MICROSOFT_CLIENT_SECRET', 'YOUR_MICROSOFT_CLIENT_SECRET');

// Redirect URLs
define('AUTH_CALLBACK_URL', 'http://localhost/images-in-bulk.com/auth/callback.php');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Temporary login hack for development
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Diego';

/**
 * Database connection helper using PDO
 */
function getDB()
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}
?>