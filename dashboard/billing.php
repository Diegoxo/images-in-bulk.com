<?php
$pathPrefix = '../';
require_once '../includes/controllers/billing_controller.php';
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
    <link rel="stylesheet" href="../assets/css/style.css?v=1.1">
</head>

<body>
    <!-- Main Header Section -->
    <?php include '../includes/layouts/header.php'; ?>

    <main class="billing-container" data-wompi-pub="<?php echo $wompiPubKey; ?>"
        data-wompi-url="<?php echo $wompiApiUrl; ?>" data-prefix="<?php echo $pathPrefix; ?>"
        data-csrf="<?php echo $csrfToken; ?>">
        <header class="animate-fade billing-header">
            <a href="./" class="back-link">
                ← Back to Dashboard
            </a>
            <h1 class="section-title">Billing Management</h1>
            <p class="subtitle">Manage your payment methods and subscription billing.</p>
        </header>

        <!-- Card Info Section -->
        <section class="glass animate-fade billing-section">
            <h3 class="billing-section-title">Registered Payment Methods</h3>
            <div class="card-management-wrapper">
                <?php echo $cardDetailsHtml; ?>
            </div>
        </section>

        <!-- Action Section (Replace/Change Card) -->
        <?php echo $paymentMethodActionHtml; ?>

        <!-- Add/Replace Card Form Modal -->
        <div id="add-card-modal" class="custom-modal hidden">
            <div class="modal-overlay" onclick="closeModal('add-card-modal')"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Enter Card Details</h2>
                    <button class="close-modal" onclick="closeModal('add-card-modal')">&times;</button>
                </div>
                <div class="modal-body p-2">
                    <form id="wompi-card-form">
                        <div class="form-field-group">
                            <label class="form-label-small">Card Holder Name</label>
                            <input type="text" id="card-holder" class="form-control"
                                placeholder="NAME AS IT APPEARS ON CARD" required>
                        </div>
                        <div class="form-field-group">
                            <label class="form-label-small">Card Number</label>
                            <input type="text" id="card-number" class="form-control" placeholder="0000 0000 0000 0000"
                                maxlength="16" required>
                        </div>
                        <div class="form-grid">
                            <div>
                                <label class="form-label-small">Exp. Month</label>
                                <select id="exp-month" class="form-control" required>
                                    <option value="">Month</option>
                                    <?php for ($i = 1; $i <= 12; $i++)
                                        echo "<option value='" . str_pad($i, 2, '0', STR_PAD_LEFT) . "'>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>"; ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label-small">Exp. Year</label>
                                <select id="exp-year" class="form-control" required>
                                    <option value="">Year</option>
                                    <?php for ($i = date('y'); $i <= date('y') + 10; $i++)
                                        echo "<option value='$i'>20$i</option>"; ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label-small">CVC</label>
                                <input type="text" id="card-cvc" class="form-control" placeholder="123" maxlength="4"
                                    required>
                            </div>
                        </div>

                        <div class="form-actions-row">
                            <button type="submit" id="save-card-btn" class="btn-auth btn-primary btn-flex">
                                Save Card Securely
                            </button>
                            <button type="button" onclick="closeModal('add-card-modal')"
                                class="btn-auth glass">Cancel</button>
                        </div>

                        <p class="form-footer-tip">
                            🔒 Your card data is encrypted and sent directly to Wompi.
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <?php echo $cancelActionHtml; ?>
    </main>

    <!-- Scripts -->
    <script src="../assets/js/billing.js?v=3"></script>
    <!-- Cancellation Modal Overlay -->
    <div id="cancel-subscription-modal" class="custom-modal hidden">
        <div class="modal-overlay" onclick="closeModal('cancel-subscription-modal')"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cancel Subscription</h2>
                <button class="close-modal" onclick="closeModal('cancel-subscription-modal')">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel your PRO plan? You will no longer be charged automatically.</p>
                <div class="policy-badge">
                    <span class="icon">🛡️</span>
                    <span>You will keep your PRO benefits and credits until the end of your current period.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-auth glass" onclick="closeModal('cancel-subscription-modal')">No, Stay PRO</button>
                <button class="btn-auth btn-danger" id="confirm-cancel-btn">Yes, Cancel Plan</button>
            </div>
        </div>
    </div>

    <?php include '../includes/layouts/footer.php'; ?>

    <?php include '../includes/layouts/main-scripts.php'; ?>
</body>

</html>