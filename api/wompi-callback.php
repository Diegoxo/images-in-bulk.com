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

    // 2. Si el pago fue con tarjeta, crear/guardar la Fuente de Pago para el futuro
    if ($transaction['payment_method_type'] === 'CARD' && isset($paymentMethod['extra']['bin'])) {
        // En una transacción aprobada, Wompi a veces no devuelve el token directo en el callback, 
        // pero podemos generar la fuente si el usuario aceptó términos.
        // NOTA: Para recurrencia real, lo ideal es tokenizar ANTES, pero intentamos extraer info:

        // Si Wompi devolvió un token de tarjeta en la respuesta (algunas versiones lo hacen)
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
            wompi_payment_source_id = ?, 
            wompi_customer_email = ?,
            current_period_start = NOW(),
            current_period_end = DATE_ADD(NOW(), INTERVAL 1 MONTH) 
            WHERE user_id = ?");
        $stmt->execute([$paymentSourceId, $customerEmail, $userId]);
    } else {
        $stmt = $db->prepare("INSERT INTO subscriptions (user_id, plan_type, status, current_period_start, current_period_end, wompi_payment_source_id, wompi_customer_email) 
            VALUES (?, 'pro', 'active', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH), ?, ?)");
        $stmt->execute([$userId, $paymentSourceId, $customerEmail]);
    }

    header('Location: ../generator.php?payment=success');
    exit;

} catch (Exception $e) {
    error_log("Error en Wompi Callback: " . $e->getMessage());
    header('Location: ../pricing.php?error=system_error');
    exit;
}

