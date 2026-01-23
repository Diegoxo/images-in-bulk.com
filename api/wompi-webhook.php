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
            }
        }

        try {
            if ($isAddon) {
                // Lógica de créditos extra
                $stmt = $db->prepare("UPDATE users SET credits = credits + 55000 WHERE id = ?");
                $stmt->execute([$userId]);
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
