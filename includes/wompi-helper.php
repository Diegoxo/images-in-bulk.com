<?php
/**
 * Wompi Helper for Recurring Payments
 */

class WompiHelper
{
    private $publicKey;
    private $privateKey;
    private $baseUrl;

    public function __construct()
    {
        $this->publicKey = defined('WOMPI_PUBLIC_KEY') ? WOMPI_PUBLIC_KEY : '';
        $this->privateKey = defined('WOMPI_PRIVATE_KEY') ? WOMPI_PRIVATE_KEY : '';

        // Determine environment based on public key
        $this->baseUrl = (strpos($this->publicKey, 'pub_test') !== false)
            ? 'https://sandbox.wompi.co/v1'
            : 'https://production.wompi.co/v1';
    }

    /**
     * Get the Acceptance Token required to create payment sources
     */
    public function getAcceptanceToken()
    {
        $url = $this->baseUrl . "/merchants/" . $this->publicKey;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['data']['presigned_acceptance']['acceptance_token'] ?? null;
    }

    /**
     * Create a Payment Source (Card Token -> Payment Source)
     */
    public function createPaymentSource($token, $customerEmail)
    {
        if (empty($this->privateKey)) {
            error_log("Wompi Error: PRIVATE_KEY not configured");
            return null;
        }

        $acceptanceToken = $this->getAcceptanceToken();

        $url = $this->baseUrl . "/payment_sources";
        $payload = [
            'type' => 'CARD',
            'token' => $token,
            'customer_email' => $customerEmail,
            'acceptance_token' => $acceptanceToken
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->privateKey
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['data']['id'] ?? null;
    }

    /**
     * Perform a recurring charge using a payment source
     */
    public function createRecurringTransaction($paymentSourceId, $amountInCents, $reference, $customerEmail)
    {
        if (empty($this->privateKey))
            return null;

        $acceptanceToken = $this->getAcceptanceToken();

        $url = $this->baseUrl . "/transactions";
        $payload = [
            'amount_in_cents' => (int) $amountInCents,
            'currency' => 'COP',
            'customer_email' => $customerEmail,
            'payment_source_id' => (int) $paymentSourceId,
            'reference' => $reference,
            'acceptance_token' => $acceptanceToken,
            'payment_method' => [
                'type' => 'CARD',
                'installments' => 1
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->privateKey
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Get transaction status
     */
    public function getTransaction($transactionId)
    {
        $url = $this->baseUrl . "/transactions/" . $transactionId;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Get payment source details
     */
    public function getPaymentSource($sourceId)
    {
        if (empty($this->privateKey))
            return null;

        $url = $this->baseUrl . "/payment_sources/" . $sourceId;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->privateKey
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}
