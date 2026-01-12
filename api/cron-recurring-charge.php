<?php
/**
 * CRON Script: Ejecutar cobros recurrentes de Wompi
 * Se recomienda correr este script una vez al día.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/wompi-helper.php';

// Seguridad básica: Solo permitir ejecución si hay una llave secreta o desde CLI
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== RECURRING_CHARGE_SECRET)) {
    die("Acceso no autorizado");
}

try {
    $db = getDB();
    $wompi = new WompiHelper();

    // 1. Buscar suscripciones que vencen hoy o ya vencieron y siguen 'active'
    // Además deben tener una fuente de pago de Wompi guardada
    $stmt = $db->prepare("
        SELECT s.*, u.email 
        FROM subscriptions s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.status = 'active' 
        AND s.plan_type = 'pro'
        AND s.current_period_end <= NOW()
        AND s.wompi_payment_source_id IS NOT NULL
    ");
    $stmt->execute();
    $subsToCharge = $stmt->fetchAll();

    echo "Procesando " . count($subsToCharge) . " suscripciones...\n";

    foreach ($subsToCharge as $sub) {
        $amount = 2000000; // 20,000 COP ($5 USD aprox)
        $reference = 'RECURRING-' . $sub['user_id'] . '-' . date('Ymd-Hi');

        echo "Cobrando a usuario {$sub['user_id']}... ";

        $res = $wompi->createRecurringTransaction(
            $sub['wompi_payment_source_id'],
            $amount,
            $reference,
            $sub['wompi_customer_email'] ?? $sub['email']
        );

        if (isset($res['data']['status']) && ($res['data']['status'] === 'PENDING' || $res['data']['status'] === 'APPROVED')) {
            // Si es aprobado o queda pendiente, extendemos el periodo
            $stmtUpdate = $db->prepare("UPDATE subscriptions SET 
                current_period_end = DATE_ADD(current_period_end, INTERVAL 1 MONTH),
                updated_at = NOW() 
                WHERE id = ?");
            $stmtUpdate->execute([$sub['id']]);

            // RESET CREDITS TO 50,000 on successful renewal
            $stmtCredits = $db->prepare("UPDATE users SET credits = 50000 WHERE id = ?");
            $stmtCredits->execute([$sub['user_id']]);

            echo "¡Éxito! Nueva fecha: " . date('Y-m-d', strtotime('+1 month', strtotime($sub['current_period_end']))) . "\n";
        } else {
            $error = $res['error']['message'] ?? ($res['data']['status_message'] ?? 'Fallido');
            echo "FALLÓ: $error\n";

            // Si el cobro falla, podríamos marcar la suscripción como inactiva después de N intentos
            // $stmtFail = $db->prepare("UPDATE subscriptions SET status = 'inactive' WHERE id = ?");
            // $stmtFail->execute([$sub['id']]);
        }
    }

    echo "Proceso terminado.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
