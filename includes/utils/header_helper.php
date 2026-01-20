<?php
/**
 * Header Helper
 * Manages UI logic for the main header (session healing, avatar paths, naming)
 */

$prefix = $pathPrefix ?? '';

if (isset($_SESSION['user_id'])) {
    // 1. Self-healing session: If avatar is missing but user is logged in, fetch it.
    if (!isset($_SESSION['user_avatar']) || empty($_SESSION['user_avatar'])) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT avatar_url FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $u = $stmt->fetch();
            $_SESSION['user_avatar'] = $u['avatar_url'] ?? '';
        } catch (Exception $e) {
            error_log("Header Helper Error (Avatar fetch): " . $e->getMessage());
        }
    }

    $displayAvatar = $_SESSION['user_avatar'];

    // 2. Path Robustness: Check if local avatar file exists
    if (!empty($displayAvatar) && strpos($displayAvatar, 'http') !== 0) {
        // Resolve absolute path for file_exists check
        $absPath = __DIR__ . '/../../' . $displayAvatar;
        if (!file_exists($absPath)) {
            $displayAvatar = null;
            $_SESSION['user_avatar'] = ''; // Clear corrupted session value
        }
    }

    // 3. Prepare Display Variables
    $avatarSrc = '';
    if (!empty($displayAvatar)) {
        $avatarSrc = (strpos($displayAvatar, 'http') === 0) ? $displayAvatar : $prefix . $displayAvatar;
    }

    $rawName = $_SESSION['user_name'] ?? 'User';
    $displayName = explode(' ', $rawName)[0];

    // 4. Pre-render Auth Section (To keep header.php logic-free)
    ob_start();
    ?>
    <div class="user-dropdown-container">
        <div class="user-menu-trigger btn-auth glass">
            <?php if ($avatarSrc): ?>
                <img src="<?php echo htmlspecialchars($avatarSrc); ?>" alt="User" class="user-avatar-img"
                    referrerpolicy="no-referrer">
            <?php else: ?>
                <div class="user-avatar-fallback">
                    <?php echo substr($displayName, 0, 1); ?>
                </div>
            <?php endif; ?>
            <span class="user-greeting">Hi,
                <strong><?php echo $displayName; ?></strong> <span class="user-arrow">▼</span></span>
        </div>

        <div id="userDropdown" class="user-dropdown-menu">
            <div class="dropdown-header-info">
                <div class="dropdown-user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
            </div>
            <a href="<?php echo $prefix; ?>dashboard" class="dropdown-item">
                <span>📊</span> Dashboard
            </a>
            <a href="<?php echo $prefix; ?>pricing" class="dropdown-item">
                <span>💎</span> My Plan
            </a>
            <div class="dropdown-divider"></div>
            <a href="<?php echo $prefix; ?>logout" class="dropdown-item text-danger">
                <span>🚪</span> Logout
            </a>
        </div>
    </div>
    <?php
    $authSectionHtml = ob_get_clean();
} else {
    // Guest Buttons
    $authSectionHtml = '
        <a href="' . $prefix . 'login" class="btn-auth glass">Login</a>
        <a href="' . $prefix . 'login?mode=signup" class="btn-auth btn-primary">Sign up</a>
    ';
}
