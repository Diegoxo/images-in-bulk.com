<?php
require_once '../includes/config.php';

use Hybridauth\Hybridauth;

if (isset($_GET['provider'])) {
    $provider = $_GET['provider'];

    $config = [
        'callback' => AUTH_CALLBACK_URL . '?provider=' . $provider,
        'providers' => [
            'Google' => [
                'enabled' => true,
                'keys' => [
                    'id' => GOOGLE_CLIENT_ID,
                    'secret' => GOOGLE_CLIENT_SECRET,
                ],
            ],
            'MicrosoftGraph' => [
                'enabled' => true,
                'keys' => [
                    'id' => MICROSOFT_CLIENT_ID,
                    'secret' => MICROSOFT_CLIENT_SECRET,
                ],
            ],
        ],
    ];

    try {
        $hybridauth = new Hybridauth($config);
        $adapter = $hybridauth->authenticate($provider);

        $userProfile = $adapter->getUserProfile();

        // Database logic to find or create user
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$userProfile->email]);
        $user = $stmt->fetch();

        if (!$user) {
            $stmt = $db->prepare("INSERT INTO users (email, full_name, auth_provider, provider_id, avatar_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $userProfile->email,
                $userProfile->displayName,
                $provider,
                $userProfile->identifier,
                $userProfile->photoURL
            ]);
            $userId = $db->lastInsertId();
        } else {
            $userId = $user['id'];
        }

        // Store user in session
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $userProfile->email;
        $_SESSION['user_name'] = $userProfile->displayName;

        $adapter->disconnect();

        header('Location: ../generator.php');
        exit;

    } catch (\Exception $e) {
        die("Auth Error: " . $e->getMessage());
    }
}
?>