<?php
/**
 * Global Configuration Handler
 * Loads environment variables from .env and defines system constants.
 */

// Load Composer Autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Initialize Environment Variables
try {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
} catch (Exception $e) {
    // In production, you might want to handle this differently
    // if the .env file is missing.
}

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'images_in_bulk');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// OpenAI Configuration
define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY'] ?? '');

// Auth Configuration
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');

// Wompi Configuration
define('WOMPI_PUBLIC_KEY', $_ENV['WOMPI_PUBLIC_KEY'] ?? '');
define('WOMPI_PRIVATE_KEY', $_ENV['WOMPI_PRIVATE_KEY'] ?? '');
define('WOMPI_INTEGRITY_SECRET', $_ENV['WOMPI_INTEGRITY_SECRET'] ?? '');

// Security & URLs
define('RECURRING_CHARGE_SECRET', $_ENV['RECURRING_CHARGE_SECRET'] ?? 'RECURRING_SECRET_123');
define('AUTH_CALLBACK_URL', $_ENV['AUTH_CALLBACK_URL'] ?? 'http://localhost/images-in-bulk.com/auth/callback.php');

// Determinar URL_BASE dinámicamente o desde .env
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$dirName = str_replace('\\', '/', dirname($scriptName));
$base = ($dirName === '/' || $dirName === '\\') ? '' : $dirName;
define('URL_BASE', $_ENV['URL_BASE'] ?? $base);

// Error reporting based on environment
$appEnv = $_ENV['APP_ENV'] ?? 'local';
if ($appEnv === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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