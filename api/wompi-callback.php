<?php
/**
 * Wompi Callback Handler
 * Recibe al usuario, verifica el pago y guarda la fuente para cobros recurrentes.
 */
require_once '../includes/config.php';
require_once '../includes/wompi-helper.php';

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$transactionId = isset($_GET['id']) ? $_GET['id'] : null;

// --- DIAGNOSTIC LOG START ---
file_put_contents(__DIR__ . '/../wompi_last_callback.json', json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'get_params' => $_GET
], JSON_PRETTY_PRINT));
// --- DIAGNOSTIC LOG END ---

if (!$transactionId) {
    header('Location: ../pricing.php?error=no_id');
    exit;
}

try {
    $db = getDB();
    $wompi = new WompiHelper();

    // 1. Verificar estado real de la transacción en Wompi
    $res = $wompi->getTransaction($transactionId);
    $transaction = $res['data'] ?? null;

    if (!$transaction || $transaction['status'] !== 'APPROVED') {
        header('Location: ../pricing.php?error=payment_not_approved');
        exit;
    }

    $userId = $_SESSION['user_id'];
    $customerEmail = $transaction['customer_email'];
    $paymentMethod = $transaction['payment_method'];
    $paymentSourceId = null;
    $reference = $transaction['reference'];

    $isAddon = strpos($reference, 'ADDON') === 0;
    $isAnnual = strpos($reference, 'ANNUAL') === 0;
    $interval = $isAnnual ? '1 YEAR' : '1 MONTH';
    $cycle = $isAnnual ? 'yearly' : 'monthly';

    // 2. Si el pago fue con tarjeta, crear/guardar la Fuente de Pago para el futuro
    if ($transaction['payment_method_type'] === 'CARD') {
        $cardToken = $transaction['payment_method']['token'] ?? null;
        if ($cardToken) {
            $paymentSourceId = $wompi->createPaymentSource($cardToken, $customerEmail);

            if ($paymentSourceId) {
                // Obtener detalles de la tarjeta para guardarlos localmente
                $sourceRes = $wompi->getPaymentSource($paymentSourceId);
                $brand = 'Card';
                $last4 = '****';
                $exp_month = null;
                $exp_year = null;

                if (isset($sourceRes['data']['public_data'])) {
                    $pd = $sourceRes['data']['public_data'];
                    $brand = $pd['brand'] ?? 'Card';
                    $last4 = $pd['last_four'] ?? '****';
                    $exp_month = $pd['exp_month'] ?? null;
                    $exp_year = $pd['exp_year'] ?? null;
                }

                // Verificar si ya existe esta tarjeta o si es la primera para ponerla por defecto
                $stmtCheck = $db->prepare("SELECT id FROM payment_methods WHERE user_id = ? AND brand = ? AND last4 = ?");
                $stmtCheck->execute([$userId, $brand, $last4]);

                if ($stmtCheck->rowCount() === 0) {
                    $stmtHasCards = $db->prepare("SELECT id FROM payment_methods WHERE user_id = ?");
                    $stmtHasCards->execute([$userId]);
                    $isDefault = ($stmtHasCards->rowCount() === 0);

                    $stmtIns = $db->prepare("INSERT INTO payment_methods (user_id, wompi_payment_source_id, brand, last4, exp_month, exp_year, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmtIns->execute([$userId, $paymentSourceId, $brand, $last4, $exp_month, $exp_year, $isDefault]);
                }
            }
        }
    }

    // 3. Activar o actualizar (Solo si NO es un Addon)
    if ($isAddon) {
        // Lógica de créditos extra: Crear paquete con vencimiento de 1 mes
        $db->prepare("INSERT INTO credit_bundles (user_id, amount_original, amount_remaining, expires_at) 
                      VALUES (?, 55000, 55000, DATE_ADD(NOW(), INTERVAL 1 MONTH))")->execute([$userId]);

        // Sincronizar columna extra_credits en tabla users
        $db->prepare("UPDATE users SET extra_credits = (SELECT SUM(amount_remaining) FROM credit_bundles WHERE user_id = ? AND expires_at > NOW() AND amount_remaining > 0) WHERE id = ?")
            ->execute([$userId, $userId]);
    } else {
        // Lógica de Suscripción PRO
        $stmt = $db->prepare("SELECT id FROM subscriptions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch();

        if ($subscription) {
            $stmt = $db->prepare("UPDATE subscriptions SET 
                plan_type = 'pro', 
                status = 'active', 
                billing_cycle = ?,
                wompi_payment_source_id = COALESCE(?, wompi_payment_source_id), 
                wompi_customer_email = ?,
                current_period_start = NOW(),
                current_period_end = DATE_ADD(NOW(), INTERVAL $interval) 
                WHERE user_id = ?");
            $stmt->execute([$cycle, $paymentSourceId, $customerEmail, $userId]);
        } else {
            $stmt = $db->prepare("INSERT INTO subscriptions (user_id, plan_type, status, billing_cycle, current_period_start, current_period_end, wompi_payment_source_id, wompi_customer_email) 
                VALUES (?, 'pro', 'active', ?, NOW(), DATE_ADD(NOW(), INTERVAL $interval), ?, ?)");
            $stmt->execute([$userId, $cycle, $paymentSourceId, $customerEmail]);
        }

        // Reset de créditos mensuales CORRESPONDIENTE al plan (Solo para nuevas subs o renewals)
        $db->prepare("UPDATE users SET credits = 50000 WHERE id = ?")->execute([$userId]);
    }

    header('Location: ../pricing.php?payment=success');
    exit;

} catch (Exception $e) {
    error_log("Error en Wompi Callback: " . $e->getMessage());
    header('Location: ../pricing.php?error=system_error');
    exit;
}

