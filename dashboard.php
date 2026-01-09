<?php
require_once 'includes/config.php';
require_once 'includes/utils/security_headers.php';
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
                    <h3 class="section-title mb-15 fs-15">Current Plan</h3>

                    <?php if ($isPro): ?>
                        <p class="mb-1">You have access to all premium features.</p>
                        <ul class="list-none p-0 mb-1 text-secondary text-left w-100">
                            <li class="mb-05">✅ All Resolutions (1:1, 16:9, 9:16)</li>
                            <li class="mb-05">✅ Priority Support</li>
                            <?php if ($user['current_period_start']): ?>
                                <li class="mt-1 fs-sm text-muted">
                                    📅 Paid on:
                                    <strong>
                                        <?php echo date('d M, Y', strtotime($user['current_period_start'])); ?></strong>
                                </li>
                            <?php endif; ?>
                            <?php if ($user['current_period_end']): ?>
                                <li class="fs-sm text-muted">
                                    ⏳ Expires on:
                                    <strong><?php echo date('d M, Y', strtotime($user['current_period_end'])); ?></strong>
                                </li>
                            <?php endif; ?>
                        </ul>
                    <?php else: ?>
                        <p class="mb-1">You are currently on the Free plan.</p>
                        <ul class="list-none p-0 mb-1 text-secondary text-left w-100">
                            <li class="mb-05">❌ Limited Generations</li>
                            <li class="mb-05">❌ Standard Resolution Only</li>
                            <li class="mb-05 opacity-0">Spacer</li>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="card-footer">
                    <?php if ($isPro): ?>
                        <button class="btn-auth glass full-width opacity-7 cursor-default" disabled>Active</button>
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
                    <p class="mt-2 fs-sm text-muted">
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
                    <h3 class="section-title mb-15 fs-15">User Actions</h3>
                    <div class="w-100 d-flex flex-column gap-1">
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
        <section class="glass animate-fade gallery-section">
            <div class="mb-15">
                <h2 class="section-title m-0">Your Gallery</h2>
            </div>

            <div id="dashboard-gallery-grid" class="dashboard-image-grid">
                <!-- Images will be loaded here via JS -->
                <p class="text-center text-muted p-2">Loading your images...</p>
            </div>

            <div class="btn-group mt-2 justify-center">
                <button id="download-all-btn" class="btn-auth btn-primary hidden-btn min-w-250">
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
    <script src="assets/js/storage.js?v=2"></script>
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
                    galleryGrid.innerHTML = '<p class="text-center text-muted p-2">No images found in this browser.</p>';
                    downloadBtn.classList.add('hidden-btn');
                    return;
                }

                downloadBtn.classList.remove('hidden-btn');

                // Render Images
                myImages.forEach(img => {
                    const url = URL.createObjectURL(img.blob);
                    const fileName = img.fileName || 'image.png';
                    const prompt = img.prompt || '';

                    const card = document.createElement('div');
                    card.className = 'image-card glass';

                    card.innerHTML = `
                        <div class="img-wrapper">
                            <button class="btn-download-single" title="Download image">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            </button>
                            <img src="${url}" alt="Generated Image" class="fade-img loaded">
                        </div>
                        <div class="card-info">
                            <div class="image-name-tag" title="${fileName}">${fileName}</div>
                            <div class="image-prompt-tag" title="${prompt}">${prompt}</div>
                        </div>
                    `;

                    // Single Download Logic
                    const singleDownloadBtn = card.querySelector('.btn-download-single');
                    singleDownloadBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = fileName;
                        link.click();
                    });

                    galleryGrid.appendChild(card);
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
                galleryGrid.innerHTML = '<p class="text-center p-2" style="color: #ef4444;">Error loading gallery.</p>';
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