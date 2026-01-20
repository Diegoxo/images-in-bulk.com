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
}
