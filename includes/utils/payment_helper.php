<?php
/**
 * Payment Helper
 * Handles logic for generating payment signatures and configurations.
 */

function generateWompiSignature($userId, $amountInCents, $currency, $prefix = 'BULK')
{
    if (!$userId) {
        return null;
    }

    // Stable reference: PREFIX + ID + Date-Time
    $reference = $prefix . $userId . '-' . date('YmdHi');

    // Signature SHA256 (Reference + Amount + Currency + Secret)
    // WOMPI_INTEGRITY_SECRET must be defined in config
    $secret = defined('WOMPI_INTEGRITY_SECRET') ? WOMPI_INTEGRITY_SECRET : '';
    $rawString = $reference . $amountInCents . $currency . $secret;
    $signature = hash('sha256', $rawString);

    return [
        'reference' => $reference,
        'signature' => $signature,
        'publicKey' => defined('WOMPI_PUBLIC_KEY') ? WOMPI_PUBLIC_KEY : '',
        'amountInCents' => $amountInCents,
        'currency' => $currency
    ];
}
?>