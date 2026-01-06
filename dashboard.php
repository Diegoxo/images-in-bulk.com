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
        SELECT u.*, s.plan_type, s.status as sub_status, s.current_period_start, s.current_period_end 
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
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-top: 2rem;
        }

        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .dash-card {
            padding: 2.5rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            /* Space content and buttons */
            align-items: center;
            text-align: center;
            border-radius: 20px;
            transition: var(--transition-base);
            position: relative;
            overflow: hidden;
            min-height: 420px;
            /* Fixed minimum height to keep them equal */
            height: 100%;
        }

        .card-content {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
        }

        .card-footer {
            width: 100%;
            margin-top: 2rem;
        }

        .dash-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .dash-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-primary);
            opacity: 0.6;
        }

        .stat-value {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--primary);
            background: var(--gradient-primary);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
            margin-bottom: 0.5rem;
            filter: drop-shadow(0 2px 10px rgba(var(--primary-rgb), 0.3));
        }

        .stat-label {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            color: var(--text-muted);
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
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            line-height: 1.2;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            background: var(--bg-card);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
        }

        .profile-info p {
            color: var(--text-secondary);
            word-break: break-all;
            /* Break long emails on mobile */
            font-size: 0.95rem;
            overflow-wrap: anywhere;
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
                max-width: 100%;
            }

            .dashboard-grid {
                gap: 1rem;
            }

            .container {
                padding: 0 1rem;
                gap: 1rem;
            }

            .profile-section,
            .gallery-section {
                padding: 1.5rem 1rem !important;
            }
        }

        .profile-section,
        .gallery-section {
            padding: 2rem;
            border-radius: 20px;
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
        <section class="animate-fade glass profile-section">
            <div class="profile-header">
                <?php
                $avatarExists = false;
                $avatarUrl = $user['avatar_url'] ?? '';

                if (!empty($avatarUrl)) {
                    if (strpos($avatarUrl, 'http') === 0) {
                        $avatarExists = true; // URL externa de Google/Microsoft
                    } elseif (file_exists($avatarUrl)) {
                        $avatarExists = true; // Archivo local que sí existe
                    } else {
                        // El archivo local no existe (lo borramos), limpiamos DB silenciosamente
                        $db->prepare("UPDATE users SET avatar_url = NULL WHERE id = ?")->execute([$user['id']]);
                        $avatarUrl = '';
                    }
                }
                ?>

                <?php if ($avatarExists): ?>
                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Google Profile Picture"
                        class="profile-avatar" referrerpolicy="no-referrer">
                <?php else: ?>
                    <div class="profile-avatar">
                        <?php echo !empty($user['full_name']) ? strtoupper(substr($user['full_name'], 0, 1)) : '?'; ?>
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
            <div class="glass dash-card">
                <div class="card-content">
                    <h3 class="section-title" style="font-size: 1.5rem; margin-bottom: 1.5rem;">Current Plan</h3>

                    <?php if ($isPro): ?>
                        <p style="margin-bottom: 1rem;">You have access to all premium features.</p>
                        <ul
                            style="list-style: none; padding: 0; margin-bottom: 1rem; color: var(--text-secondary); text-align: left; width: 100%;">
                            <li style="margin-bottom: 0.5rem;">✅ All Resolutions (1:1, 16:9, 9:16)</li>
                            <li style="margin-bottom: 0.5rem;">✅ Priority Support</li>
                            <?php if ($user['current_period_start']): ?>
                                <li style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-muted);">
                                    📅 Paid on:
                                    <strong>
                                        <?php echo date('d M, Y', strtotime($user['current_period_start'])); ?></strong>
                                </li>
                            <?php endif; ?>
                            <?php if ($user['current_period_end']): ?>
                                <li style="font-size: 0.85rem; color: var(--text-muted);">
                                    ⏳ Expires on:
                                    <strong><?php echo date('d M, Y', strtotime($user['current_period_end'])); ?></strong>
                                </li>
                            <?php endif; ?>
                        </ul>
                    <?php else: ?>
                        <p style="margin-bottom: 1rem;">You are currently on the Free plan.</p>
                        <ul
                            style="list-style: none; padding: 0; margin-bottom: 1rem; color: var(--text-secondary); text-align: left; width: 100%;">
                            <li style="margin-bottom: 0.5rem;">❌ Limited Generations</li>
                            <li style="margin-bottom: 0.5rem;">❌ Standard Resolution Only</li>
                            <li style="margin-bottom: 0.5rem; opacity: 0;">Spacer</li>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="card-footer">
                    <?php if ($isPro): ?>
                        <button class="btn-auth glass full-width" disabled
                            style="opacity: 0.7; cursor: default;">Active</button>
                    <?php else: ?>
                        <a href="pricing" class="btn-auth btn-primary full-width">Upgrade to Pro</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats -->
            <div class="glass dash-card">
                <div class="card-content">
                    <div class="stat-value">
                        <?php echo number_format($stats['total_images']); ?>
                    </div>
                    <div class="stat-label">Total Images Generated</div>
                    <p style="margin-top: 2rem; font-size: 0.85rem; color: var(--text-muted);">
                        Keep creating to grow your collection!
                    </p>
                </div>
                <div class="card-footer">
                    <a href="generator" class="btn-auth glass full-width">View Generator</a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="glass dash-card">
                <div class="card-content">
                    <h3 class="section-title" style="font-size: 1.5rem; margin-bottom: 1.5rem;">User Actions</h3>
                    <div style="width: 100%; display: flex; flex-direction: column; gap: 1rem;">
                        <a href="pricing" class="btn-auth glass full-width">
                            View Pricing
                        </a>
                        <a href="billing" class="btn-auth glass full-width">
                            Billing & Payments 💳
                        </a>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="generator" class="btn-auth btn-primary full-width">
                        Go to Generator ✨
                    </a>
                </div>
            </div>

        </div>

        <!-- Gallery Section -->
        <section class="glass animate-fade gallery-section" style="margin-top: 2rem;">
            <div style="margin-bottom: 1.5rem;">
                <h2 class="section-title" style="margin: 0;">Your Gallery</h2>
            </div>

            <div id="dashboard-gallery-grid" class="dashboard-image-grid">
                <!-- Images will be loaded here via JS -->
                <p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 2rem;">Loading your
                    images...</p>
            </div>

            <div class="btn-group" style="margin-top: 2rem; justify-content: center;">
                <button id="download-all-btn" class="btn-auth btn-primary" style="display: none; min-width: 250px;">
                    Download All (.zip)
                </button>
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

            // --- Gallery Logic ---
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