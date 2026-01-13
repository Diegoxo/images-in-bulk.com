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

    // 1. MONTHLY CREDIT REFILL (For all active PRO users)
    // Runs if the last reset was more than 1 month ago
    echo "Processing monthly credit refills...\n";
    $stmtRefill = $db->exec("
        UPDATE users u
        JOIN subscriptions s ON u.id = s.user_id
        SET u.credits = 50000, 
            s.last_credits_reset = DATE_ADD(s.last_credits_reset, INTERVAL 1 MONTH),
            s.updated_at = NOW()
        WHERE s.status = 'active'
        AND s.plan_type = 'pro'
        AND s.last_credits_reset <= DATE_SUB(NOW(), INTERVAL 1 MONTH)
    ");
    echo "Refilled credits for " . (int) $stmtRefill . " users.\n";

    // 2. RECURRING CHARGES (Renewals)
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

    echo "Procesando " . count($subsToCharge) . " renovaciones...\n";

    foreach ($subsToCharge as $sub) {
        $isAnnual = ($sub['billing_cycle'] === 'yearly');
        $amount = $isAnnual ? 85000000 : 8500000; // 850k COP vs 85k COP
        $interval = $isAnnual ? '1 YEAR' : '1 MONTH';
        $reference = 'RECURRING-' . ($isAnnual ? 'ANNUAL-' : '') . $sub['user_id'] . '-' . date('Ymd-Hi');

        echo "Cobrando a usuario {$sub['user_id']} (" . ($isAnnual ? 'Anual' : 'Mensual') . ")... ";

        $res = $wompi->createRecurringTransaction(
            $sub['wompi_payment_source_id'],
            $amount,
            $reference,
            $sub['wompi_customer_email'] ?? $sub['email']
        );

        if (isset($res['data']['status']) && ($res['data']['status'] === 'APPROVED')) {
            // Extend period and reset last refill date to NOW to sync
            $stmtUpdate = $db->prepare("UPDATE subscriptions SET 
                current_period_end = DATE_ADD(current_period_end, INTERVAL $interval),
                last_credits_reset = NOW(),
                updated_at = NOW() 
                WHERE id = ?");
            $stmtUpdate->execute([$sub['id']]);

            // Ensure credits are set to 50,000 on renewal (in case refill didn't catch it yet)
            $stmtCredits = $db->prepare("UPDATE users SET credits = 50000 WHERE id = ?");
            $stmtCredits->execute([$sub['user_id']]);

            echo "¡Éxito! Nueva fecha de vencimiento: " . date('Y-m-d', strtotime('+' . $interval, strtotime($sub['current_period_end']))) . "\n";
        } else {
            $error = $res['error']['message'] ?? ($res['data']['status_message'] ?? 'Fallido');
            echo "FALLÓ: $error\n";

            // Note: In production, you'd fail several times before inactivating
            // For now, let's keep it active but log it.
        }
    }

    echo "Proceso terminado.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
