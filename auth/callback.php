<?php
/**
 * HybridAuth Callback & Auth Handler
 */
require_once '../includes/config.php';

use Hybridauth\Hybridauth;
use Hybridauth\HttpClient;

// Detectar proveedor: Si viene por GET, es el inicio. Si no, recuperar de sesión (retorno).
if (isset($_GET['provider'])) {
    $provider = $_GET['provider'];
    $_SESSION['auth_provider_flow'] = $provider;
} else {
    $provider = isset($_SESSION['auth_provider_flow']) ? $_SESSION['auth_provider_flow'] : 'Google';
}

// Configuration for HybridAuth
$config = [
    // EXACT URL registered in Google (without ?provider=...)
    'callback' => URL_BASE . '/auth/callback.php',
    'providers' => [
        'Google' => [
            'enabled' => true,
            'keys' => [
                'id' => GOOGLE_CLIENT_ID,
                'secret' => GOOGLE_CLIENT_SECRET,
            ],
        ],
    ],
];

try {
    $hybridauth = new Hybridauth($config);
    $adapter = $hybridauth->authenticate($provider);
    $userProfile = $adapter->getUserProfile();
    $adapter->disconnect();

    if ($userProfile && $userProfile->identifier) {
        $db = getDB();

        // 1. Check if user exists
        $stmt = $db->prepare("SELECT id, full_name, email, avatar_url FROM users WHERE provider_id = ? AND auth_provider = ?");
        $stmt->execute([$userProfile->identifier, $provider]);
        $user = $stmt->fetch();

        if (!$user) {
            // Register new user
            $storeAvatar = $userProfile->photoURL;
            $stmt = $db->prepare("INSERT INTO users (email, full_name, auth_provider, provider_id, avatar_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $userProfile->email,
                $userProfile->displayName,
                $provider,
                $userProfile->identifier,
                $storeAvatar
            ]);
            $userId = $db->lastInsertId();
            $userName = $userProfile->displayName;
            $userAvatar = $storeAvatar;
        } else {
            // Update existing user with fresh info from provider
            $userId = $user['id'];
            $userName = $userProfile->displayName ?: $user['full_name'];
            $userAvatar = $userProfile->photoURL ?: $user['avatar_url'];

            $stmt = $db->prepare("UPDATE users SET full_name = ?, avatar_url = ? WHERE id = ?");
            $stmt->execute([$userName, $userAvatar, $userId]);
        }

        // 2. Set session
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_avatar'] = $userAvatar;

        // 3. Redirect back to home or generator
        header('Location: ../generator.php?login=success');
        exit;
    }

} catch (\Exception $e) {
    echo "¡Error de autenticación!: " . $e->getMessage();
}