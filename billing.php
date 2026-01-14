<?php
require_once 'includes/config.php';

// 1. Seguridad: Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$db = getDB();

// 2. Obtener info de suscripción y método de pago
try {
    $stmt = $db->prepare("SELECT * FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    $paymentSourceId = $subscription['wompi_payment_source_id'] ?? null;
    $hasCard = !empty($paymentSourceId);
    $cardInfo = null;

    if ($hasCard) {
        require_once 'includes/wompi-helper.php';
        $wompi = new WompiHelper();
        $sourceData = $wompi->getPaymentSource($paymentSourceId);

        if (isset($sourceData['data']['public_data'])) {
            $cardInfo = $sourceData['data']['public_data'];
        }
    }
} catch (Exception $e) {
    die("Error loading billing: " . $e->getMessage());
}

$pageTitle = "Billing & Payments";
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
        .billing-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .card-management {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .payment-method-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-md);
        }

        .card-details {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .card-icon {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .btn-danger {
            background: rgba(var(--accent-rgb), 0.1);
            color: var(--accent) !important;
            border: 1px solid var(--accent);
        }

        .btn-danger:hover {
            background: var(--accent);
            color: white !important;
        }

        /* New Card Form Styles */
        #add-card-section {
            display: none;
            margin-top: 2rem;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px dashed var(--glass-border);
            border-radius: var(--radius-md);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-control {
            background: var(--bg-input);
            border: 1px solid var(--glass-border);
            padding: 0.8rem;
            color: white;
            border-radius: 8px;
            width: 100%;
        }
    </style>
</head>

<body>
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

        <section class="glass animate-fade" style="padding: 2.5rem; border-radius: 20px;">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.2rem;">Registered Payment Methods</h3>

            <?php if ($hasCard): ?>
                <div class="card-management">
                    <div class="payment-method-card">
                        <div class="card-details">
                            <span class="card-icon">💳</span>
                            <div>
                                <p style="font-weight: 600;">
                                    <?php echo isset($cardInfo['brand']) ? htmlspecialchars($cardInfo['brand']) : 'Primary Card'; ?>
                                    ending in ****
                                    <?php echo isset($cardInfo['last_four']) ? htmlspecialchars($cardInfo['last_four']) : ''; ?>
                                </p>
                                <p style="font-size: 0.85rem; color: var(--text-muted);">
                                    Used for your PRO subscription renewals.
                                </p>
                            </div>
                        </div>
                        <button onclick="deleteCard()" class="btn-auth btn-danger" style="padding: 0.5rem 1rem;">
                            Remove Card
                        </button>
                    </div>

                    <div
                        style="background: rgba(var(--primary-rgb), 0.1); padding: 1rem; border-radius: 12px; border-left: 4px solid var(--primary);">
                        <p style="font-size: 0.9rem;">
                            💡 <strong>Note:</strong> If you remove this card, your PRO subscription will not renew next
                            month.
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem;">
                    <p style="color: var(--text-muted); margin-bottom: 2rem;">You don't have any registered payment methods
                        yet.</p>
                    <button onclick="toggleAddCard()" class="btn-auth btn-primary">
                        Add New Card
                    </button>
                    <p style="margin-top: 1rem; font-size: 0.8rem; color: var(--text-muted);">
                        * This will securely save your card for future PRO renewals.
                    </p>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($hasCard): ?>
            <section class="animate-fade" style="margin-top: 2rem; text-align: center;">
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">Want to change your card?</p>
                <button onclick="toggleAddCard()" class="btn-auth glass" id="toggle-btn">
                    Replace Primary Card
                </button>
            </section>
        <?php endif; ?>

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
    </main>

    <script>
        const WOMPI_PUB_KEY = '<?php echo WOMPI_PUBLIC_KEY; ?>';
        const WOMPI_API_URL = WOMPI_PUB_KEY.includes('pub_test')
            ? 'https://sandbox.wompi.co/v1'
            : 'https://production.wompi.co/v1';

        function toggleAddCard() {
            const section = document.getElementById('add-card-section');
            const isVisible = section.style.display === 'block';
            section.style.display = isVisible ? 'none' : 'block';
            if (!isVisible) section.scrollIntoView({ behavior: 'smooth' });
        }

        document.getElementById('wompi-card-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('save-card-btn');
            const originalText = btn.innerText;
            btn.innerText = 'Securing Card... 🔒';
            btn.disabled = true;

            const cardData = {
                number: document.getElementById('card-number').value.replace(/\s/g, ''),
                cvv: document.getElementById('card-cvc').value,
                exp_month: document.getElementById('exp-month').value,
                exp_year: document.getElementById('exp-year').value,
                card_holder: document.getElementById('card-holder').value
            };

            try {
                // 1. Tokenize directly with Wompi
                const tokenRes = await fetch(`${WOMPI_API_URL}/tokens/cards`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${WOMPI_PUB_KEY}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(cardData)
                });

                const tokenData = await tokenRes.json();

                if (tokenData.error || !tokenData.data || !tokenData.data.id) {
                    throw new Error(tokenData.error ? tokenData.error.type : 'Card validation failed');
                }

                const cardToken = tokenData.data.id;

                // 2. Send token to our backend
                const saveRes = await fetch('api/add-payment-method.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ token: cardToken })
                });

                const saveResult = await saveRes.json();

                if (saveResult.success) {
                    alert('Success! Your payment method has been updated.');
                    location.reload();
                } else {
                    alert('Error saving card: ' + saveResult.error);
                }

            } catch (err) {
                console.error(err);
                alert('Could not secure card. Please verify your details.');
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        });

        async function deleteCard() {
            if (!confirm('Are you sure you want to remove your primary payment method? Your subscription will not be automatically renewed.')) {
                return;
            }

            try {
                const response = await fetch('api/delete-payment-method.php', {
                    method: 'POST'
                });
                const result = await response.json();

                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (result.error || 'Could not remove card'));
                }
            } catch (err) {
                alert('Connection error');
            }
        }
    </script>

    <?php include 'includes/layouts/footer.php'; ?>
    <?php include 'includes/layouts/main-scripts.php'; ?>
</body>

</html>