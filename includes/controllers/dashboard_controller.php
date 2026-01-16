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
    $redirectPrefix = $pathPrefix ?? '';
    header('Location: ' . $redirectPrefix . 'login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$db = getDB();

try {
    // 1. Fetch User and Subscription Info
    $subStatus = getUserSubscriptionStatus($userId);

    $stmtData = $db->prepare("
        SELECT u.*, s.current_period_start, s.current_period_end, s.images_in_period
        FROM users u 
        LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status IN ('active', 'cancelled')
        WHERE u.id = ?
    ");
    $stmtData->execute([$userId]);
    $user = $stmtData->fetch(PDO::FETCH_ASSOC);

    // 2. Statistics
    $stmtStats = $db->prepare("SELECT COUNT(*) as total_images FROM generations WHERE user_id = ?");
    $stmtStats->execute([$userId]);
    $dbStats = $stmtStats->fetch(PDO::FETCH_ASSOC);

    // Use images_in_period for the dashboard display if available, otherwise fallback to total
    $imagesToDisplay = isset($user['images_in_period']) ? (int) $user['images_in_period'] : (int) $dbStats['total_images'];
    $stats = ['total_images' => $imagesToDisplay];

    // 3. Variables
    $isPro = $subStatus['isPro'];
    $credits = $subStatus['credits'];
    $avatarUrl = $user['avatar_url'] ?? '';

    // 4. Pre-render UI Components

    // --- Avatar Component ---
    $avatarHtml = '';
    $avatarExists = false;
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

    if ($avatarExists) {
        $avatarHtml = '<img src="' . htmlspecialchars($avatarUrl) . '" alt="Profile Picture" class="profile-avatar" referrerpolicy="no-referrer">';
    } else {
        $initial = !empty($user['full_name']) ? strtoupper(substr($user['full_name'], 0, 1)) : '?';
        $avatarHtml = '<div class="profile-avatar">' . $initial . '</div>';
    }

    // --- Badge Component ---
    $profileBadgeHtml = $isPro
        ? '<span class="badge badge-pro">PRO Member</span>'
        : '<span class="badge badge-free">Free Plan</span>';

    // --- Plan Details Component ---
    $planDetailsHtml = '';
    if ($isPro) {
        $planDetailsHtml = '<p class="mb-1">You have access to all premium features.</p>
        <ul class="list-none p-0 mb-1 text-secondary text-left w-100">
            <li class="mb-05">✅ All Resolutions (1:1, 16:9, 9:16)</li>
            <li class="mb-05">✅ Priority Support</li>';

        if ($user['current_period_start']) {
            $planDetailsHtml .= '<li class="mt-1 fs-sm text-muted">📅 Paid on: <strong>' . date('d M, Y', strtotime($user['current_period_start'])) . '</strong></li>';
        }
        if ($user['current_period_end']) {
            $planDetailsHtml .= '<li class="fs-sm text-muted">⏳ Expires on: <strong>' . date('d M, Y', strtotime($user['current_period_end'])) . '</strong></li>';
        }
        $planDetailsHtml .= '</ul>';
    } else {
        $planDetailsHtml = '<p class="mb-1">You are currently on the Free plan.</p>
        <ul class="list-none p-0 mb-1 text-secondary text-left w-100">
            <li class="mb-05">❌ Limited Generations</li>
            <li class="mb-05">❌ Standard Resolution Only</li>
            <li class="mb-05 opacity-0">Spacer</li>
        </ul>';
    }

    // --- Plan Action Component ---
    $planActionHtml = $isPro
        ? '<button class="btn-auth glass full-width opacity-7 cursor-default" disabled>Active</button>'
        : '<a href="pricing" class="btn-auth btn-primary full-width">Upgrade to Pro</a>';

    // --- Credits Tip Component ---
    $creditsTipHtml = $isPro
        ? 'Your monthly balance for high-quality images.'
        : 'Upgrade to get 50,000 monthly credits!';



} catch (Exception $e) {
    error_log("Dashboard Controller Error: " . $e->getMessage());
    die("A system error occurred while loading your profile.");
}