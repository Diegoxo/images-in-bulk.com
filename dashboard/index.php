<?php
$pathPrefix = '../';
require_once '../includes/controllers/dashboard_controller.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $pageTitle; ?> | Images In Bulks
    </title>
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon.ico">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <!-- Main Header Section -->
    <?php include '../includes/layouts/header.php'; ?>

    <main class="container">

        <!-- Profile Header -->
        <section class="animate-fade glass profile-section">
            <div class="profile-header">
                <?php echo $avatarHtml; ?>

                <div class="profile-info">
                    <h1>
                        <?php echo htmlspecialchars($user['full_name']); ?>
                        <?php echo $profileBadgeHtml; ?>
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
                    <?php echo $planDetailsHtml; ?>
                </div>

                <div class="card-footer">
                    <?php echo $planActionHtml; ?>
                </div>
            </div>

            <!-- Credits Card -->
            <div class="glass dash-card">
                <div class="card-content">
                    <div class="stat-value">
                        <?php echo number_format($credits); ?>
                    </div>
                    <div class="stat-label">Available Credits</div>
                    <p class="mt-2 fs-sm text-muted">
                        <?php echo $creditsTipHtml; ?>
                    </p>
                </div>
                <div class="card-footer">
                    <a href="../generator" class="btn-auth glass full-width">Use Credits</a>
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
                    <a href="../generator" class="btn-auth glass full-width">View Generator</a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="glass dash-card">
                <div class="card-content">
                    <h3 class="section-title mb-15 fs-15">User Actions</h3>
                    <div class="w-100 d-flex flex-column gap-1">
                        <a href="../pricing" class="btn-auth glass full-width">
                            View Pricing
                        </a>
                        <a href="billing" class="btn-auth glass full-width">
                            Billing & Payments 💳
                        </a>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="../generator" class="btn-auth btn-primary full-width">
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
                <p class="text-center text-muted p-2">Loading your images...</p>
            </div>

            <div class="btn-group mt-2 justify-center">
                <button id="download-all-btn" class="btn-auth btn-primary hidden-btn min-w-250">
                    Download All (.zip)
                </button>
            </div>
        </section>

        <!-- Pass PHP environment to Global JS -->
        <script>
            const CURRENT_USER_ID = <?php echo $userId; ?>;
        </script>

        <!-- External Libraries & Dashboard Scripts -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="../assets/js/storage.js?v=2"></script>
        <script src="../assets/js/dashboard-gallery.js?v=1"></script>

    </main>

    <!-- Main Footer Section -->
    <?php include '../includes/layouts/footer.php'; ?>

    <?php include '../includes/layouts/main-scripts.php'; ?>
</body>

</html>