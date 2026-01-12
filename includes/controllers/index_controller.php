<?php
/**
 * Index (Home) Page Controller
 * Handles basic configuration and view data for the landing page.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/security_headers.php';
include __DIR__ . '/../pages-config/index-config.php';

// Auth State (for CTA logic)
$isLoggedIn = isset($_SESSION['user_id']);
?>