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

    // 5. Pre-render UI Components (To keep view logic-free)
    $cancelActionHtml = '';
    $renderCancelButtonHtml = ''; // This will now contain the script

    if ($isPro) {
        // Determine HTML
        $cancelActionHtml = '<div class="cancel-link-container">
        <button id="cancel-subscription-btn" class="cancel-link">
            Cancel subscription
        </button>
    </div>';

        // Determine Script
        ob_start();
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const btn = document.getElementById('cancel-subscription-btn');
                if (!btn) return;
                btn.addEventListener('click', async () => {
                    if (!confirm('Are you sure you want to cancel your PRO subscription? You will lose access to PRO features immediately.')) return;

                    btn.disabled = true;
                    btn.innerText = 'Cancelling...';

                    try {
                        const res = await fetch('api/cancel-subscription.php', { method: 'POST' });
                        const data = await res.json();
                        if (data.success) {
                            alert('Subscription cancelled successfully.');
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.error);
                            btn.disabled = false;
                            btn.innerText = 'Cancel subscription';
                        }
                    } catch (e) {
                        alert('Network error. Please try again.');
                        btn.disabled = false;
                        btn.innerText = 'Cancel subscription';
                    }
                });
            });
        </script>
        <?php
        $renderCancelButtonHtml = ob_get_clean();
    }

} catch (Exception $e) {
    error_log("Dashboard Controller Error: " . $e->getMessage());
    die("A system error occurred while loading your profile.");
}
?>