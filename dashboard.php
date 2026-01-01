<?php
require_once 'includes/config.php';
include 'includes/pages-config/dashboard-config.php';

// 1. Seguridad: Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Obtener datos del usuario y suscripción
$db = getDB();
try {
    // Info de Usuario y Suscripción
    $stmt = $db->prepare("
        SELECT u.*, s.plan_type, s.status as sub_status, s.current_period_end 
        FROM users u 
        LEFT JOIN subscriptions s ON u.id = s.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Estadísticas de Uso (Total imágenes generadas)
    // Nota: Si la tabla generations aún no tiene datos, dará 0.
    $stmtStats = $db->prepare("SELECT COUNT(*) as total_images FROM generations WHERE user_id = ?");
    $stmtStats->execute([$_SESSION['user_id']]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error loading dashboard: " . $e->getMessage());
}

// Valores por defecto
$planType = $user['plan_type'] ?? 'free';
$planStatus = $user['sub_status'] ?? 'inactive';
$isPro = ($planType === 'pro' && $planStatus === 'active');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $pageTitle; ?> | Images In Bulk
    </title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .stat-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            text-align: center;
        }

        .stat-value {
            font-size: 3rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 2px solid var(--primary);
            padding: 2px;
            object-fit: cover;
        }

        .profile-info h1 {
            font-size: 2rem;
            margin-bottom: 0.25rem;
        }

        .profile-info p {
            color: var(--text-secondary);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 0.5rem;
            vertical-align: middle;
        }

        .badge-pro {
            background: rgba(74, 222, 128, 0.2);
            color: #4ade80;
            border: 1px solid rgba(74, 222, 128, 0.3);
        }

        .badge-free {
            background: rgba(148, 163, 184, 0.2);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }
    </style>
</head>

<body>
    <!-- Main Header Section -->
    <?php include 'includes/layouts/header.php'; ?>

    <main class="container">

        <!-- Profile Header -->
        <section class="animate-fade glass" style="padding: 2rem; border-radius: 20px;">
            <div class="profile-header">
                <?php if (!empty($user['avatar_url'])): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Profile" class="profile-avatar">
                <?php else: ?>
                    <div class="profile-avatar"
                        style="background: var(--card-bg); display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>

                <div class="profile-info">
                    <h1>
                        <?php echo htmlspecialchars($user['full_name']); ?>
                        <?php if ($isPro): ?>
                            <span class="badge badge-pro">PRO Member</span>
                        <?php else: ?>
                            <span class="badge badge-free">Free Plan</span>
                        <?php endif; ?>
                    </h1>
                    <p>
                        <?php echo htmlspecialchars($user['email']); ?>
                    </p>
                </div>
            </div>
        </section>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid animate-fade">

            <!-- Plan Details -->
            <div class="glass" style="padding: 2rem; border-radius: 20px;">
                <h3 class="section-title" style="font-size: 1.5rem; margin-bottom: 1.5rem;">Current Plan</h3>

                <?php if ($isPro): ?>
                    <p style="margin-bottom: 1rem;">You have access to all premium features.</p>
                    <ul style="list-style: none; padding: 0; margin-bottom: 2rem; color: var(--text-secondary);">
                        <li style="margin-bottom: 0.5rem;">✅ Unlimited Generations</li>
                        <li style="margin-bottom: 0.5rem;">✅ All Resolutions (1:1, 16:9, 9:16)</li>
                        <li style="margin-bottom: 0.5rem;">✅ Priority Support</li>
                    </ul>
                    <button class="btn-auth glass full-width" disabled
                        style="opacity: 0.7; cursor: default;">Active</button>
                <?php else: ?>
                    <p style="margin-bottom: 1rem;">You are currently on the Free plan.</p>
                    <ul style="list-style: none; padding: 0; margin-bottom: 2rem; color: var(--text-secondary);">
                        <li style="margin-bottom: 0.5rem;">❌ Limited Generations</li>
                        <li style="margin-bottom: 0.5rem;">❌ Standard Resolution Only</li>
                    </ul>
                    <a href="pricing.php" class="btn-auth btn-primary full-width">Upgrade to Pro</a>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="glass stat-card" style="border-radius: 20px;">
                <div class="stat-value">
                    <?php echo number_format($stats['total_images']); ?>
                </div>
                <div class="stat-label">Images Generated</div>
            </div>

            <!-- Actions -->
            <div class="glass"
                style="padding: 2rem; border-radius: 20px; display: flex; flex-direction: column; justify-content: center;">
                <h3 class="section-title" style="font-size: 1.5rem; margin-bottom: 1.5rem;">Quick Actions</h3>
                <a href="generator.php" class="btn-auth btn-primary full-width" style="margin-bottom: 1rem;">
                    Go to Generator ✨
                </a>
                <a href="pricing.php" class="btn-auth glass full-width">
                    View Usage Limits
                </a>
            </div>

        </div>
    </main>

    <!-- Main Footer Section -->
    <?php include 'includes/layouts/footer.php'; ?>

    <!-- Modular Script Injection -->
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>