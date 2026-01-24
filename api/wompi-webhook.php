<?php
/**
 * Wompi Webhook Handler (v2 - Hardened)
 * Valida la integridad de la notificación y activa suscripciones.
 */
require_once '../includes/config.php';
require_once '../includes/wompi-helper.php';

$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

// 1. Verificación básica
if (!$event || $event['event'] !== 'transaction.updated') {
    exit;
}

$transaction = $event['data']['transaction'];
$signature = $event['signature'] ?? null;
$timestamp = $event['timestamp'] ?? '';

// 2. Validación de Integridad (Checksum)
// Wompi recomienda validar que el evento venga realmente de ellos
if ($signature && defined('WOMPI_EVENT_SECRET')) {
    $properties = $signature['properties'];
    $concatenated = "";
    foreach ($properties as $prop) {
        // Recorrer las propiedades en orden: id, status, amount_in_cents
        $path = explode('.', $prop);
        $val = $event;
        foreach ($path as $p) {
            $val = $val[$p];
        }
        $concatenated .= $val;
    }
    $concatenated .= $timestamp;
    $concatenated .= WOMPI_EVENT_SECRET;

    $checksum = hash('sha256', $concatenated);
    if ($checksum !== $signature['checksum']) {
        error_log("Wompi Webhook Error: Invalid Checksum signature.");
        http_response_code(403);
        exit;
    }
}

// 3. Procesar Transacción APROBADA
if ($transaction['status'] === 'APPROVED') {
    $db = getDB();
    $wompi = new WompiHelper();

    $reference = $transaction['reference'];
    $isAddon = strpos($reference, 'ADDON') === 0;
    $isAnnual = strpos($reference, 'ANNUAL') === 0;

    // Regex para extraer el ID del usuario de la referencia (e.g., BULK123-...)
    if (preg_match('/(?:BULK|ADDON|ANNUAL)(\d+)-/', $reference, $matches)) {
        $userId = $matches[1];
        $customerEmail = $transaction['customer_email'];

        // Guardar fuente de pago para cobros recurrentes (Solo si es Tarjeta)
        $paymentSourceId = null;
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

        try {
            if ($isAddon) {
                // ADD-ON logic: Crear un nuevo paquete con vencimiento de 1 mes
                $stmt = $db->prepare("INSERT INTO credit_bundles (user_id, amount_original, amount_remaining, expires_at) 
                                      VALUES (?, 55000, 55000, DATE_ADD(NOW(), INTERVAL 1 MONTH))");
                $stmt->execute([$userId]);

                // Sincronizar columna extra_credits en tabla users
                $db->prepare("UPDATE users SET extra_credits = (SELECT SUM(amount_remaining) FROM credit_bundles WHERE user_id = ? AND expires_at > NOW() AND amount_remaining > 0) WHERE id = ?")
                    ->execute([$userId, $userId]);
            } else {
                // Lógica de Suscripción PRO
                $cycle = $isAnnual ? 'yearly' : 'monthly';
                $interval = $isAnnual ? '1 YEAR' : '1 MONTH';

                $stmt = $db->prepare("SELECT id FROM subscriptions WHERE user_id = ?");
                $stmt->execute([$userId]);
                $subscription = $stmt->fetch();

                if ($subscription) {
                    $db->prepare("UPDATE subscriptions SET 
                        plan_type = 'pro', status = 'active', billing_cycle = ?,
                        last_credits_reset = NOW(), images_in_period = 0,
                        wompi_payment_source_id = COALESCE(?, wompi_payment_source_id), 
                        wompi_customer_email = ?,
                        current_period_end = DATE_ADD(NOW(), INTERVAL $interval) 
                        WHERE user_id = ?")->execute([$cycle, $paymentSourceId, $customerEmail, $userId]);
                } else {
                    $db->prepare("INSERT INTO subscriptions (user_id, plan_type, status, billing_cycle, last_credits_reset, current_period_end, images_in_period, wompi_payment_source_id, wompi_customer_email) 
                        VALUES (?, 'pro', 'active', ?, NOW(), DATE_ADD(NOW(), INTERVAL $interval), 0, ?, ?)")
                        ->execute([$userId, $cycle, $paymentSourceId, $customerEmail]);
                }

                // Reset de créditos mensuales
                $db->prepare("UPDATE users SET credits = 50000 WHERE id = ?")->execute([$userId]);
            }
        } catch (Exception $e) {
            error_log("Webhook DB Error: " . $e->getMessage());
        }
    }
}

http_response_code(200);
echo "OK";
