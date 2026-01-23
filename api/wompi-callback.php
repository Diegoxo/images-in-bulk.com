<?php
/**
 * Wompi Callback Handler
 * Recibe al usuario, verifica el pago y guarda la fuente para cobros recurrentes.
 */
require_once '../includes/config.php';
require_once '../includes/wompi-helper.php';

$transactionId = isset($_GET['id']) ? $_GET['id'] : null;

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
    $isAnnual = strpos($reference, 'ANNUAL') === 0;
    $interval = $isAnnual ? '1 YEAR' : '1 MONTH';
    $cycle = $isAnnual ? 'yearly' : 'monthly';

    // 2. Si el pago fue con tarjeta, crear/guardar la Fuente de Pago para el futuro
    if ($transaction['payment_method_type'] === 'CARD') {
        $cardToken = $transaction['payment_method']['token'] ?? null;
        if ($cardToken) {
            $paymentSourceId = $wompi->createPaymentSource($cardToken, $customerEmail);
        }
    }

    // 3. Activar o actualizar suscripción
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

    // Reset de créditos
    $db->prepare("UPDATE users SET credits = 50000 WHERE id = ?")->execute([$userId]);

    header('Location: ../generator.php?payment=success');
    exit;

} catch (Exception $e) {
    error_log("Error en Wompi Callback: " . $e->getMessage());
    header('Location: ../pricing.php?error=system_error');
    exit;
}

