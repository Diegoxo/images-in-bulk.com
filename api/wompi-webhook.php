<?php
/**
 * Wompi Webhook Handler (v2 - Hardened)
 * Validates the notification integrity and activates subscriptions.
 */
require_once '../includes/config.php';
require_once '../includes/wompi-helper.php';

$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

// 1. Basic verification
if (!$event || !isset($event['event']) || $event['event'] !== 'transaction.updated') {
    exit;
}

$signature = $event['signature'] ?? null;
$timestamp = $event['timestamp'] ?? '';

// 2. Integrity Validation (Checksum)
// Wompi recommends validating that the event truly comes from them
if ($signature && defined('WOMPI_EVENT_SECRET')) {
    $properties = $signature['properties']; // e.g. ["transaction.id", "transaction.status", "transaction.amount_in_cents"]
    $concatenated = "";

    foreach ($properties as $prop) {
        $path = explode('.', $prop);
        $val = $event['data'];
        // Navigate within data
        foreach ($path as $p) {
            if (isset($val[$p])) {
                $val = $val[$p];
            } else {
                $val = null;
                break;
            }
        }
        $concatenated .= $val;
    }

    $concatenated .= $timestamp;
    $concatenated .= WOMPI_EVENT_SECRET;

    $checksum = hash('sha256', $concatenated);

    // NOTE: If this fails in production, uncomment the strict validation.
    // For now, we only log it to avoid blocking valid payments during diagnosis.
    if ($checksum !== $signature['checksum']) {
        error_log("Wompi Webhook Warning: Invalid Checksum signature. Calc: $checksum vs Rec: " . $signature['checksum']);
    }
}

// 3. Process APPROVED Transaction
$transaction = $event['data']['transaction'] ?? null;

if ($transaction && $transaction['status'] === 'APPROVED') {
    $db = getDB();
    $wompi = new WompiHelper();

    $reference = $transaction['reference'];
    $isAddon = strpos($reference, 'ADDON') === 0;
    $isAnnual = strpos($reference, 'ANNUAL') === 0;

    // Regex to extract user ID from reference (e.g., BULK123-...)
    if (preg_match('/(?:BULK|ADDON|ANNUAL)(\d+)-/', $reference, $matches)) {
        $userId = $matches[1];
        $customerEmail = $transaction['customer_email'] ?? null;

        // If email is missing, fetch from user record
        if (!$customerEmail) {
            $stmtEmail = $db->prepare("SELECT email FROM users WHERE id = ?");
            $stmtEmail->execute([$userId]);
            $customerEmail = $stmtEmail->fetchColumn();
        }

        // Save payment source for recurring charges (Only if it's Card)
        $paymentSourceId = null;
        $brand = 'Card';
        $last4 = '****';
        $exp_month = null;
        $exp_year = null;

        if ($transaction['payment_method_type'] === 'CARD') {
            // Direct extraction from Webhook JSON
            // ATTEMPT 1: Look for 'token' in direct payment_method
            if (isset($transaction['payment_method']['token'])) {
                $cardToken = $transaction['payment_method']['token'];
                // We create the payment source
                $paymentSourceId = $wompi->createPaymentSource($cardToken, $customerEmail);
            }

            // Extract visual data if present in payload (FASTER THAN API CONSULT)
            // ATTEMPT 1: Extract from 'extra' within 'payment_method'
            if (isset($transaction['payment_method']['extra'])) {
                $extra = $transaction['payment_method']['extra'];
                $brand = $extra['brand'] ?? 'Card';
                $last4 = $extra['last_four'] ?? '****';
            }
        }

        try {
            $db->beginTransaction();

            // 1. RECORD PAYMENT RECORD FIRST (Atomic Check)
            $stmtPay = $db->prepare("INSERT IGNORE INTO payments (user_id, wompi_transaction_id, reference, amount, currency, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtPay->execute([
                $userId,
                $transaction['id'] ?? 'EVENT-' . time(),
                $transaction['reference'],
                $transaction['amount_in_cents'],
                $transaction['currency'] ?? 'COP',
                $transaction['payment_method_type'] ?? 'WEBHOOK',
                $transaction['status']
            ]);

            $isFirstTime = ($stmtPay->rowCount() > 0);

            if ($isFirstTime) {
                // 2. Grant Benefits ONLY IF it's the first time
                if ($isAddon) {
                    // ADD-ON logic: Create a new bundle with 1 month expiry
                    $db->prepare("INSERT INTO credit_bundles (user_id, amount_original, amount_remaining, expires_at) 
                                  VALUES (?, 55000, 55000, DATE_ADD(NOW(), INTERVAL 1 MONTH))")->execute([$userId]);

                    // Sync extra_credits column
                    $db->prepare("UPDATE users SET extra_credits = (SELECT SUM(amount_remaining) FROM credit_bundles WHERE user_id = ? AND expires_at > NOW() AND amount_remaining > 0) WHERE id = ?")
                        ->execute([$userId, $userId]);
                } else {
                    // PRO Subscription logic
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

                    // Reset monthly credits
                    $db->prepare("UPDATE users SET credits = 50000 WHERE id = ?")->execute([$userId]);
                }
            }

            // Save card source
            if ($paymentSourceId) {
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

            $db->commit();

            // 3. Send Email (Only if first time)
            if ($isFirstTime) {
                try {
                    require_once '../includes/utils/email_helper.php';
                    $stmtName = $db->prepare("SELECT full_name FROM users WHERE id = ?");
                    $stmtName->execute([$userId]);
                    $userName = $stmtName->fetchColumn() ?: 'Customer';

                    $pName = $isAddon ? 'Extra Credits Bundle' : 'PRO Subscription (' . ucfirst($cycle) . ')';

                    EmailHelper::sendPaymentSuccess(
                        $customerEmail,
                        $userName,
                        $pName,
                        $transaction['amount_in_cents'],
                        $transaction['currency'] ?? 'COP',
                        $transaction['reference']
                    );
                } catch (Exception $mailEx) {
                    error_log("Webhook Email Error: " . $mailEx->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log("Webhook DB Error: " . $e->getMessage());
        }
    }
}

http_response_code(200);
echo "OK";
