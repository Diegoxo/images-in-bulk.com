<?php
/**
 * Wompi Callback Handler
 * Receives the user, verifies the payment, and saves the source for recurring charges.
 */
require_once '../includes/config.php';
require_once '../includes/wompi-helper.php';

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$transactionId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$transactionId) {
    header('Location: ../pricing.php?error=no_id');
    exit;
}

try {
    $db = getDB();
    $wompi = new WompiHelper();

    // 1. Verify the actual status of the transaction in Wompi
    $res = $wompi->getTransaction($transactionId);
    $transaction = $res['data'] ?? null;

    if (!$transaction || $transaction['status'] !== 'APPROVED') {
        header('Location: ../pricing.php?error=payment_not_approved');
        exit;
    }

    $userId = $_SESSION['user_id'];
    // Fallback for customer_email if missing from transaction data
    $customerEmail = $transaction['customer_email'] ?? $_SESSION['user_email'] ?? null;

    // If still null, fetch from DB
    if (!$customerEmail) {
        $stmtEmail = $db->prepare("SELECT email FROM users WHERE id = ?");
        $stmtEmail->execute([$userId]);
        $customerEmail = $stmtEmail->fetchColumn();
    }

    $paymentMethod = $transaction['payment_method'] ?? null;
    $paymentSourceId = null;
    $reference = $transaction['reference'];

    $isAddon = strpos($reference, 'ADDON') === 0;
    $isAnnual = strpos($reference, 'ANNUAL') === 0;
    $interval = $isAnnual ? '1 YEAR' : '1 MONTH';
    $cycle = $isAnnual ? 'yearly' : 'monthly';

    // 2. If the payment was with a card, create/save the Payment Source for the future
    if ($transaction['payment_method_type'] === 'CARD') {
        $cardToken = $transaction['payment_method']['token'] ?? null;
        if ($cardToken) {
            $paymentSourceId = $wompi->createPaymentSource($cardToken, $customerEmail);

            if ($paymentSourceId) {
                // Get card details to save local copies
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

                // Check if this card already exists or if it's the first one to set as default
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

    // 3. Record the transaction in the history table
    $stmtPay = $db->prepare("INSERT IGNORE INTO payments (user_id, wompi_transaction_id, reference, amount, currency, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtPay->execute([
        $userId,
        $transaction['id'],
        $transaction['reference'],
        $transaction['amount_in_cents'],
        $transaction['currency'],
        $transaction['payment_method_type'],
        $transaction['status']
    ]);

    // 4. Activate or update (Only if NOT an Addon)
    if ($isAddon) {
        // Extra credits logic: Create bundle with 1 month expiry
        $db->prepare("INSERT INTO credit_bundles (user_id, amount_original, amount_remaining, expires_at) 
                      VALUES (?, 55000, 55000, DATE_ADD(NOW(), INTERVAL 1 MONTH))")->execute([$userId]);

        // Sync extra_credits column in users table
        $db->prepare("UPDATE users SET extra_credits = (SELECT SUM(amount_remaining) FROM credit_bundles WHERE user_id = ? AND expires_at > NOW() AND amount_remaining > 0) WHERE id = ?")
            ->execute([$userId, $userId]);
    } else {
        // PRO Subscription logic
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

        // Reset monthly credits according to the plan (Only for new subs or renewals)
        $db->prepare("UPDATE users SET credits = 50000 WHERE id = ?")->execute([$userId]);
    }

    // 5. Send Confirmation Email (Only if this is the first time we record the payment)
    if ($stmtPay->rowCount() > 0) {
        try {
            require_once '../includes/utils/email_helper.php';
            $stmtName = $db->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmtName->execute([$userId]);
            $userName = $stmtName->fetchColumn() ?: 'User';

            $planName = $isAddon ? 'Extra Credits Bundle' : 'PRO Subscription (' . ucfirst($cycle) . ')';

            EmailHelper::sendPaymentSuccess(
                $customerEmail,
                $userName,
                $planName,
                $transaction['amount_in_cents'],
                $transaction['currency'],
                $transaction['reference']
            );
        } catch (Exception $e) {
            error_log("Email Sending Error in Callback: " . $e->getMessage());
        }
    }

    header('Location: ../pricing.php?payment=success');
    exit;

} catch (Exception $e) {
    error_log("Error in Wompi Callback: " . $e->getMessage());
    header('Location: ../pricing.php?error=system_error');
    exit;
}

