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
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
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
                    <?php echo $profileInfoHtml; ?>
                </div>
            </div>
        </section>

        <!-- Email Change Modal Component -->
        <?php echo $emailChangeModalHtml; ?>
        <?php echo $passwordChangeModalHtml; ?>
        <?php echo $deleteAccountModalHtml; ?>

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
                        <?php echo number_format($totalCredits); ?>
                    </div>
                    <div class="stat-label">Total Credits Available</div>

                    <div class="credits-breakdown mt-2">
                        <div class="d-flex justify-between fs-sm text-secondary mb-05">
                            <span>Plan Credits:</span>
                            <strong><?php echo number_format($credits); ?></strong>
                        </div>
                        <div class="d-flex justify-between fs-sm text-secondary">
                            <span>Extra Credits:</span>
                            <strong class="text-accent">+<?php echo number_format($extraCredits); ?></strong>
                        </div>
                    </div>

                    <p class="mt-2 fs-sm text-muted">
                        Plan credits are used first and reset monthly.
                        <?php if ($extraCredits > 0 && $nextExpiry): ?>
                            <br><span class="text-accent">⏳ Next bundle expires:
                                <strong><?php echo date('d M, Y', strtotime($nextExpiry)); ?></strong></span>
                        <?php else: ?>
                            Extra credits are used after plan credits.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="card-footer">
                    <a href="../pricing" class="btn-auth glass full-width">Buy More Credits</a>
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
        <section class="glass animate-fade gallery-section mb-4">
            <div class="mb-15 text-center">
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

        <?php if (isset($user['auth_provider']) && $user['auth_provider'] === 'local'): ?>
            <div
                style="display:flex; justify-content:flex-end; margin-bottom:0.5rem; margin-top:-1rem; padding-right: 1rem;">
                <button id="params-change-password-btn" class="cancel-link">Change Password 🔒</button>
            </div>
        <?php endif; ?>

        <div
            style="display:flex; justify-content:flex-end; margin-bottom:2rem; padding-right: 1rem; margin-top: -0.5rem;">
            <button id="params-delete-account-btn" class="cancel-link" style="color: #ef4444;">Delete Account ⚠</button>
        </div>

        <!-- Pass PHP environment to Global JS & Profile Logic -->
        <?php echo $dashboardSpecificJs; ?>

        <!-- External Libraries & Dashboard Scripts -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="../assets/js/storage.js?v=<?php echo time(); ?>"></script>
        <script src="../assets/js/dashboard-gallery.js?v=<?php echo time(); ?>"></script>

    </main>

    <!-- Main Footer Section -->
    <?php include '../includes/layouts/footer.php'; ?>

    <?php include '../includes/layouts/main-scripts.php'; ?>
</body>

</html>