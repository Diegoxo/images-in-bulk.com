<?php
/**
 * HybridAuth Callback & Auth Handler
 */
require_once '../includes/config.php';

use Hybridauth\Hybridauth;
use Hybridauth\HttpClient;

// Detect provider: If set via GET, it's the start. Otherwise, retrieve from session (return).
if (isset($_GET['provider'])) {
    $provider = $_GET['provider'];
    $_SESSION['auth_provider_flow'] = $provider;
} else {
    $provider = isset($_SESSION['auth_provider_flow']) ? $_SESSION['auth_provider_flow'] : 'Google';
}

// Configuration for HybridAuth
$config = [
    // EXACT URL registered in Google (must match exactly)
    'callback' => AUTH_CALLBACK_URL,
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

        // Detect if this is a re-authentication for deletion
        $action = isset($_SESSION['auth_action']) ? $_SESSION['auth_action'] : 'login';

        if ($action === 'delete_account') {
            $currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

            // Re-verify that the authenticated social ID belongs to the current user
            if ($currentUserId && (int) $userId === (int) $currentUserId) {
                try {
                    // PERFORM DELETION
                    // The 'users' table has ON DELETE CASCADE for all related tables 
                    // (generations, payment_methods, subscriptions, etc.) so we only need to delete the user.
                    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$currentUserId]);

                    // Cleanup
                    unset($_SESSION['auth_action']);
                    session_destroy();
                    header('Location: ../?delete=success');
                    exit;
                } catch (\Exception $e) {
                    echo "Deletion Error: " . $e->getMessage();
                    exit;
                }
            } else {
                echo "Verification failed: You authenticated with a different account or session lost.";
                exit;
            }
        }

        // 2. Set session for normal login
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $userName;
        $_SESSION['user_avatar'] = $userAvatar;
        unset($_SESSION['auth_action']); // Ensure action is cleared

        // 3. Redirect back to home or generator
        header('Location: ../generator?login=success');
        exit;
    }

} catch (\Exception $e) {
    echo "Authentication Error: " . $e->getMessage();
}