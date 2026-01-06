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

        // Determinar ambiente basado en la llave pública
        $this->baseUrl = (strpos($this->publicKey, 'pub_test') !== false)
            ? 'https://sandbox.wompi.co/v1'
            : 'https://production.wompi.co/v1';
    }

    /**
     * Obtener el Acceptance Token necesario para crear fuentes de pago
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
     * Crear una Fuente de Pago (Card Token -> Payment Source)
     */
    public function createPaymentSource($token, $customerEmail)
    {
        if (empty($this->privateKey)) {
            error_log("Wompi Error: PRIVATE_KEY no configurada");
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
     * Realizar un cobro recurrente usando una fuente de pago
     */
    public function createRecurringTransaction($paymentSourceId, $amountInCents, $reference, $customerEmail)
    {
        if (empty($this->privateKey))
            return null;

        $url = $this->baseUrl . "/transactions";
        $payload = [
            'amount_in_cents' => (int) $amountInCents,
            'currency' => 'COP',
            'customer_email' => $customerEmail,
            'payment_source_id' => (int) $paymentSourceId,
            'reference' => $reference,
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
     * Consultar estado de una transacción
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
     * Consultar detalles de una fuente de pago
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
