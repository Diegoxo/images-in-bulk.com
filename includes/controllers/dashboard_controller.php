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
    $extraCredits = $subStatus['extra_credits'];
    $totalCredits = $subStatus['total_credits'];
    $avatarUrl = $user['avatar_url'] ?? '';

    // 3.1 Fetch nearest expiring bundle for the UI tip
    $stmtExpiry = $db->prepare("SELECT MIN(expires_at) FROM credit_bundles WHERE user_id = ? AND expires_at > NOW() AND amount_remaining > 0");
    $stmtExpiry->execute([$userId]);
    $nextExpiry = $stmtExpiry->fetchColumn();

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

    // --- Profile Info Component ---
    ob_start(); ?>
    <div id="name-display-container">
        <h1>
            <span id="current-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
            <?php echo $profileBadgeHtml; ?>
            <button id="edit-name-trigger" class="edit-btn-icon" title="Edit Full Name">✏️</button>
        </h1>
    </div>

    <!-- Name Edit Form -->
    <div id="name-edit-container" class="name-edit-form d-none">
        <input type="text" id="new-name-input" class="edit-input-field"
            value="<?php echo htmlspecialchars($user['full_name']); ?>" maxlength="50">
        <div class="edit-actions">
            <button id="save-name-btn" class="btn-icon-action save" title="Save Changes">✓</button>
            <button id="cancel-name-btn" class="btn-icon-action cancel" title="Cancel">✕</button>
        </div>
    </div>

    <div class="d-flex align-items-center gap-1">
        <p class="m-0"><?php echo htmlspecialchars($user['email']); ?></p>
        <?php if ($user['auth_provider'] === 'local'): ?>
            <button id="edit-email-trigger" class="edit-btn-icon" title="Change Email">✏️</button>
        <?php endif; ?>
    </div>
    <?php
    $profileInfoHtml = ob_get_clean();

    // --- Email Change Modal Component ---
    ob_start(); ?>
    <div id="email-change-modal" class="custom-modal hidden">
        <div class="modal-overlay"></div>
        <div class="modal-content animate-pop">
            <div class="modal-header">
                <h2 class="section-title fs-15">Change Email Address</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body modal-body-left">
                <p class="fs-sm text-secondary mb-15">
                    To change your email, please enter your new address and your current password for security.
                </p>
                <div class="form-group mb-1">
                    <label class="fs-xs">Current Email</label>
                    <input type="text" class="auth-input opacity-7" readonly
                        value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>
                <div class="form-group mb-1">
                    <label class="fs-xs">New Email Address</label>
                    <input type="email" id="modal-new-email" class="auth-input" placeholder="new-email@example.com"
                        required>
                </div>
                <div class="form-group mb-1">
                    <label class="fs-xs">Confirm New Email</label>
                    <input type="email" id="modal-confirm-email" class="auth-input" placeholder="repeat-email@example.com"
                        required>
                </div>
                <div class="form-group">
                    <label class="fs-xs">Current Password</label>
                    <input type="password" id="modal-current-password" class="auth-input" placeholder="••••••••" required
                        autocomplete="current-password">
                </div>
            </div>
            <div class="modal-footer d-flex gap-1">
                <button id="confirm-email-change-btn" class="btn-auth btn-primary flex-1">Update Email</button>
                <button type="button" id="cancel-email-change-btn" class="btn-auth glass flex-1">Cancel</button>
            </div>
        </div>
    </div>
    <?php
    $emailChangeModalHtml = ob_get_clean();

    // --- Credits Tip Component ---
    $creditsTipHtml = $isPro
        ? 'Your monthly balance for high-quality images.'
        : 'Upgrade to get 50,000 monthly credits!';



} catch (Exception $e) {
    error_log("Dashboard Controller Error: " . $e->getMessage());
    die("A system error occurred while loading your profile.");
}