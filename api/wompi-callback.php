<?php
/**
 * Wompi Callback Handler
 * Este archivo recibe al usuario después del pago y activa su suscripción.
 */
require_once '../includes/config.php';

// Wompi envía el ID de la transacción por GET: ?id=XXXXXXXXX
$transactionId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$transactionId) {
    header('Location: ../pricing.php?error=no_id');
    exit;
}

try {
    $db = getDB();
    $userId = $_SESSION['user_id'];

    // 1. Verificar si el usuario ya tiene una entrada en subscriptions
    $stmt = $db->prepare("SELECT id FROM subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch();

    if ($subscription) {
        // Actualizar suscripción existente a PRO (usando solo columnas que existen)
        $stmt = $db->prepare("UPDATE subscriptions SET plan_type = 'pro', status = 'active' WHERE user_id = ?");
        $stmt->execute([$userId]);
    } else {
        // Crear nueva suscripción PRO
        $stmt = $db->prepare("INSERT INTO subscriptions (user_id, plan_type, status, current_period_end) VALUES (?, 'pro', 'active', DATE_ADD(NOW(), INTERVAL 1 MONTH))");
        $stmt->execute([$userId]);
    }

    // 3. Redirigir al generador con mensaje de éxito
    header('Location: ../generator.php?payment=success');
    exit;

} catch (Exception $e) {
    // Log error y redirigir
    error_log("Error en Wompi Callback: " . $e->getMessage());
    header('Location: ../pricing.php?error=db_update_failed');
    exit;
}
