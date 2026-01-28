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
                    <div id="name-display-container">
                        <h1>
                            <span id="current-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                            <?php echo $profileBadgeHtml; ?>
                            <button id="edit-name-trigger" class="edit-btn-icon" title="Edit Full Name">✏️</button>
                        </h1>
                    </div>

                    <!-- Hidden Edit Form -->
                    <div id="name-edit-container" class="name-edit-form d-none">
                        <input type="text" id="new-name-input" class="edit-input-field"
                            value="<?php echo htmlspecialchars($user['full_name']); ?>" maxlength="50">
                        <div class="edit-actions">
                            <button id="save-name-btn" class="btn-icon-action save" title="Save Changes">✓</button>
                            <button id="cancel-name-btn" class="btn-icon-action cancel" title="Cancel">✕</button>
                        </div>
                    </div>

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

        <!-- Pass PHP environment to Global JS -->
        <script>
            const CURRENT_USER_ID = <?php echo $userId; ?>;

            // --- Profile Name Editing Logic ---
            const nameDisplay = document.getElementById('name-display-container');
            const nameEdit = document.getElementById('name-edit-container');
            const editTrigger = document.getElementById('edit-name-trigger');
            const cancelBtn = document.getElementById('cancel-name-btn');
            const saveBtn = document.getElementById('save-name-btn');
            const nameInput = document.getElementById('new-name-input');
            const currentNameSpan = document.getElementById('current-name');

            // Toggle Edit View
            editTrigger.addEventListener('click', () => {
                nameDisplay.classList.add('d-none');
                nameEdit.classList.remove('d-none');
                nameInput.focus();
            });

            // Cancel Edit
            cancelBtn.addEventListener('click', () => {
                nameDisplay.classList.remove('d-none');
                nameEdit.classList.add('d-none');
                nameInput.value = currentNameSpan.textContent; // Reset input
            });

            // Save via API
            saveBtn.addEventListener('click', async () => {
                const newName = nameInput.value.trim();

                if (newName === currentNameSpan.textContent) {
                    cancelBtn.click();
                    return;
                }

                if (newName.length < 3) {
                    showNotification('Name is too short', 'error');
                    return;
                }

                saveBtn.disabled = true;
                saveBtn.innerHTML = '...';

                try {
                    const response = await fetch('../api/update-profile.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ full_name: newName })
                    });

                    const data = await response.json();

                    if (data.success) {
                        currentNameSpan.textContent = data.new_name;
                        // Update header name if it exists
                        const headerName = document.querySelector('.user-name');
                        if (headerName) headerName.textContent = data.new_name;

                        showNotification('Name updated successfully!', 'success');
                        cancelBtn.click();
                    } else {
                        showNotification(data.message || 'Error updating name', 'error');
                    }
                } catch (error) {
                    showNotification('Connection error', 'error');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '✓';
                }
            });

            // Allow "Enter" to save
            nameInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') saveBtn.click();
            });
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