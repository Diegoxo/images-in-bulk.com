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
            flex-wrap: wrap;
            /* Allow wrapping */
        }

        .profile-info {
            flex: 1;
            /* Take remaining space */
            min-width: 0;
            /* Crucial for text-overflow to work in flex children */
            display: flex;
            /* Para mejor control */
            flex-direction: column;
            justify-content: center;
        }

        .profile-info h1 {
            font-size: 2rem;
            margin-bottom: 0.25rem;
            word-wrap: break-word;
            /* Wrap long names */
            line-height: 1.2;
        }

        .profile-info p {
            color: var(--text-secondary);
            word-break: break-all;
            /* Break long emails on mobile */
            font-size: 0.95rem;
        }

        @media (max-width: 600px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .profile-info {
                width: 100%;
                align-items: center;
                /* Center content in column mode */
            }

            .profile-info h1 {
                font-size: 1.5rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
            }

            .profile-info p {
                font-size: 0.8rem !important;
                /* Smaller email on mobile */
                opacity: 0.8;
            }

            .badge {
                margin-left: 0;
                /* Remove left margin in stack mode */
            }
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

            <!-- Stats (Compact) -->
            <div class="glass stat-card" style="border-radius: 20px;">
                <div class="stat-value">
                    <?php echo number_format($stats['total_images']); ?>
                </div>
                <div class="stat-label">Total Images Generated</div>
            </div>

            <!-- Quick Actions -->
            <div class="glass"
                style="padding: 2rem; border-radius: 20px; display: flex; flex-direction: column; justify-content: center;">
                <h3 class="section-title" style="font-size: 1.5rem; margin-bottom: 1.5rem;">Quick Actions</h3>
                <a href="generator" class="btn-auth btn-primary full-width" style="margin-bottom: 1rem;">
                    Go to Generator ✨
                </a>
                <a href="pricing" class="btn-auth glass full-width">
                    View Pricing
                </a>
            </div>

        </div>

        <!-- Gallery Section -->
        <section class="glass animate-fade" style="margin-top: 2rem; padding: 2rem; border-radius: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 class="section-title" style="margin: 0;">Your Gallery</h2>
                <button id="download-all-btn" class="btn-auth glass">
                    Download All (.zip) 📥
                </button>
            </div>

            <div id="dashboard-gallery-grid"
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">
                <!-- Images will be loaded here via JS -->
                <p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 2rem;">Loading your
                    images...</p>
            </div>
        </section>

    </main>

    <!-- Scripts for Gallery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script>
        const CURRENT_USER_ID = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "'guest'"; ?>;
    </script>
    <script src="assets/js/storage.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const galleryGrid = document.getElementById('dashboard-gallery-grid');
            const downloadBtn = document.getElementById('download-all-btn');

            try {
                // Initialize Storage
                const images = await ImageStorage.getAllImages();

                // Note: ImageStorage.getAllImages() already filters by CURRENT_USER_ID if updated properly,
                // otherwise strictly filter here just in case:
                const myImages = images.filter(img => img.userId == CURRENT_USER_ID);

                galleryGrid.innerHTML = '';

                if (myImages.length === 0) {
                    galleryGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 2rem;">No images found in this browser.</p>';
                    downloadBtn.style.display = 'none';
                    return;
                }

                downloadBtn.style.display = 'block';

                // Render Images
                myImages.forEach(img => {
                    const url = URL.createObjectURL(img.blob);

                    const div = document.createElement('div');
                    div.style.cssText = 'position: relative; aspect-ratio: 1; border-radius: 12px; overflow: hidden; border: 1px solid var(--glass-border);';

                    const imageEl = document.createElement('img');
                    imageEl.src = url;
                    imageEl.style.cssText = 'width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;';
                    imageEl.onmouseover = () => imageEl.style.transform = 'scale(1.1)';
                    imageEl.onmouseout = () => imageEl.style.transform = 'scale(1)';

                    div.appendChild(imageEl);
                    galleryGrid.appendChild(div);
                });

                // Handle Download All
                downloadBtn.addEventListener('click', async () => {
                    const originalText = downloadBtn.innerText;
                    downloadBtn.innerText = 'Zipping... ⏳';
                    downloadBtn.disabled = true;

                    try {
                        const zip = new JSZip();
                        const folder = zip.folder("my_images_bulk");

                        myImages.forEach((img, index) => {
                            const name = img.fileName || `image_${index + 1}.png`;
                            folder.file(name, img.blob);
                        });

                        const content = await zip.generateAsync({ type: "blob" });

                        // Trigger Download
                        const a = document.createElement("a");
                        a.href = URL.createObjectURL(content);
                        a.download = "my_images_bulk.zip";
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);

                    } catch (err) {
                        alert('Error creating zip: ' + err);
                    } finally {
                        downloadBtn.innerText = originalText;
                        downloadBtn.disabled = false;
                    }
                });

            } catch (err) {
                console.error(err);
                galleryGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #ef4444;">Error loading gallery.</p>';
            }
        });
    </script>

    </div>
    </main>

    <!-- Main Footer Section -->
    <?php include 'includes/layouts/footer.php'; ?>

    <!-- Modular Script Injection -->
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>