<?php
require_once 'includes/controllers/billing_controller.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $pageTitle; ?> | Images In Bulks
    </title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Main Header Section -->
    <?php include 'includes/layouts/header.php'; ?>

    <main class="billing-container">
        <header class="animate-fade" style="margin-bottom: 2rem;">
            <a href="dashboard"
                style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 5px; margin-bottom: 1rem;">
                ← Back to Dashboard
            </a>
            <h1 class="section-title">Billing Management</h1>
            <p class="subtitle">Manage your payment methods and subscription billing.</p>
        </header>

        <!-- Card Info Section -->
        <section class="glass animate-fade" style="padding: 2.5rem; border-radius: 20px;">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.2rem;">Registered Payment Methods</h3>
            <div class="card-management-wrapper">
                <?php echo $cardDetailsHtml; ?>
            </div>
        </section>

        <!-- Action Section (Replace/Change Card) -->
        <?php echo $paymentMethodActionHtml; ?>

        <!-- Add/Replace Card Form -->
        <section id="add-card-section" class="animate-fade">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.1rem;">Enter New Card Details</h3>
            <form id="wompi-card-form">
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-size:0.8rem; color:var(--text-muted);">Card
                        Holder Name</label>
                    <input type="text" id="card-holder" class="form-control" placeholder="NAME AS IT APPEARS ON CARD"
                        required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom:0.5rem; font-size:0.8rem; color:var(--text-muted);">Card
                        Number</label>
                    <input type="text" id="card-number" class="form-control" placeholder="0000 0000 0000 0000"
                        maxlength="16" required>
                </div>
                <div class="form-grid">
                    <div>
                        <label
                            style="display:block; margin-bottom:0.5rem; font-size:0.8rem; color:var(--text-muted);">Exp.
                            Month</label>
                        <select id="exp-month" class="form-control" required>
                            <option value="">Month</option>
                            <?php for ($i = 1; $i <= 12; $i++)
                                echo "<option value='" . str_pad($i, 2, '0', STR_PAD_LEFT) . "'>" . str_pad($i, 2, '0', STR_PAD_LEFT) . "</option>"; ?>
                        </select>
                    </div>
                    <div>
                        <label
                            style="display:block; margin-bottom:0.5rem; font-size:0.8rem; color:var(--text-muted);">Exp.
                            Year</label>
                        <select id="exp-year" class="form-control" required>
                            <option value="">Year</option>
                            <?php for ($i = date('y'); $i <= date('y') + 10; $i++)
                                echo "<option value='$i'>20$i</option>"; ?>
                        </select>
                    </div>
                    <div>
                        <label
                            style="display:block; margin-bottom:0.5rem; font-size:0.8rem; color:var(--text-muted);">CVC</label>
                        <input type="text" id="card-cvc" class="form-control" placeholder="123" maxlength="4" required>
                    </div>
                </div>

                <div style="display:flex; gap:1rem;">
                    <button type="submit" id="save-card-btn" class="btn-auth btn-primary" style="flex:1;">
                        Save Card Securely
                    </button>
                    <button type="button" onclick="toggleAddCard()" class="btn-auth glass">Cancel</button>
                </div>

                <p style="font-size: 0.75rem; color: var(--text-muted); text-align: center; margin-top: 1rem;">
                    🔒 Your card data is encrypted and sent directly to Wompi.
                </p>
            </form>
        </section>

        <?php echo $cancelActionHtml; ?>
    </main>

    <!-- Global Variables for external JS -->
    <script>
        const WOMPI_PUB_KEY = '<?php echo $wompiPubKey; ?>';
        const WOMPI_API_URL = '<?php echo $wompiApiUrl; ?>';
    </script>

    <!-- Scripts -->
    <script src="assets/js/billing.js?v=1"></script>
    <?php include 'includes/layouts/footer.php'; ?>

    <!-- Modular Script Injection -->
    <?php echo $renderCancelButtonHtml; ?>
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>