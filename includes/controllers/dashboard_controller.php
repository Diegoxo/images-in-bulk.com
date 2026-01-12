<?php
/**
 * Dashboard Page Controller
 * Handles user authentication, data fetching, and business logic for the dashboard view.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/security_headers.php';
require_once __DIR__ . '/../utils/subscription_helper.php';
include __DIR__ . '/../pages-config/dashboard-config.php';

// Auth Guard
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$db = getDB();

try {
    // 1. Fetch User and Subscription Info
    $stmt = $db->prepare("
        SELECT u.*, s.plan_type, s.status as sub_status, s.current_period_start, s.current_period_end
        FROM users u
        LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status = 'active'
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Statistics
    $stmtStats = $db->prepare("SELECT COUNT(*) as total_images FROM generations WHERE user_id = ?");
    $stmtStats->execute([$userId]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

    // 3. User State Variables
    $planType = $user['plan_type'] ?? 'free';
    $planStatus = $user['sub_status'] ?? 'inactive';
    $isPro = ($planType === 'pro' && $planStatus === 'active');
    $credits = $user['credits'] ?? 0;

    // 4. Avatar Logic
    $avatarExists = false;
    $avatarUrl = $user['avatar_url'] ?? '';

    if (!empty($avatarUrl)) {
        if (strpos($avatarUrl, 'http') === 0) {
            $avatarExists = true;
        } elseif (file_exists($avatarUrl)) {
            $avatarExists = true;
        } else {
            // Cleanup broken local avatars
            $db->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?")->execute([$userId]);
            $avatarUrl = '';
        }
    }

} catch (Exception $e) {
    error_log("Dashboard Controller Error: " . $e->getMessage());
    die("A system error occurred while loading your profile.");
}
?>