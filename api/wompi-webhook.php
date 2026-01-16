<?php
/**
 * Wompi Webhook Handler
 * Escucha eventos de transacciones terminadas para asegurar que las suscripciones se activen.
 */
require_once '../includes/config.php';
require_once '../includes/wompi-helper.php';

$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

if (!$event || $event['event'] !== 'transaction.updated') {
    exit;
}

$transaction = $event['data']['transaction'];

if ($transaction['status'] === 'APPROVED') {
    $db = getDB();
    $wompi = new WompiHelper();

    // Extraer referencia y user_id
    // La referencia la armamos como BULK{id}-{fecha} en pricing.php
    $reference = $transaction['reference'];
    $isAddon = strpos($reference, 'ADDON') === 0;
    $isAnnual = strpos($reference, 'ANNUAL') === 0;

    if (preg_match('/(?:BULK|ADDON|ANNUAL)(\d+)-/', $reference, $matches)) {
        $userId = $matches[1];
        $customerEmail = $transaction['customer_email'];

        // Intentar crear fuente de pago si no existe
        $paymentSourceId = null;
        if ($transaction['payment_method_type'] === 'CARD') {
            // Nota: El webhook suele traer más info que el callback
            $cardToken = $transaction['payment_method']['token'] ?? null;
            if ($cardToken) {
                $paymentSourceId = $wompi->createPaymentSource($cardToken, $customerEmail);
            }
        }

        // Actualizar base de datos
        try {
            if (isset($isAddon) && $isAddon) {
                // ADD-ON logic: JUST ADD 55,000 CREDITS to current balance
                $stmtCredits = $db->prepare("UPDATE users SET credits = credits + 55000 WHERE id = ?");
                $stmtCredits->execute([$userId]);
            } else {
                // SUBSCRIPTION logic (Original + Annual Support)
                $cycle = (isset($isAnnual) && $isAnnual) ? 'yearly' : 'monthly';
                $interval = (isset($isAnnual) && $isAnnual) ? '1 YEAR' : '1 MONTH';

                $stmt = $db->prepare("SELECT id FROM subscriptions WHERE user_id = ?");
                $stmt->execute([$userId]);
                $subscription = $stmt->fetch();

                if ($subscription) {
                    $stmt = $db->prepare("UPDATE subscriptions SET 
                        plan_type = 'pro', 
                        status = 'active', 
                        billing_cycle = ?,
                        last_credits_reset = NOW(),
                        images_in_period = 0,
                        wompi_payment_source_id = COALESCE(?, wompi_payment_source_id), 
                        wompi_customer_email = ?,
                        current_period_end = DATE_ADD(NOW(), INTERVAL $interval) 
                        WHERE user_id = ?");
                    $stmt->execute([$cycle, $paymentSourceId, $customerEmail, $userId]);
                } else {
                    $stmt = $db->prepare("INSERT INTO subscriptions (user_id, plan_type, status, billing_cycle, last_credits_reset, current_period_end, images_in_period, wompi_payment_source_id, wompi_customer_email) 
                        VALUES (?, 'pro', 'active', ?, NOW(), DATE_ADD(NOW(), INTERVAL $interval), 0, ?, ?)");
                    $stmt->execute([$userId, $cycle, $paymentSourceId, $customerEmail]);
                }

                // RESET CREDITS TO 50,000 on successful main subscription payment
                $stmtCredits = $db->prepare("UPDATE users SET credits = 50000 WHERE id = ?");
                $stmtCredits->execute([$userId]);
            }
        } catch (Exception $e) {
            error_log("Webhook Error: " . $e->getMessage());
        }
    }
}

http_response_code(200);
