<?php

namespace PaymentManager\Gateways;

use PaymentManager\Contracts\PaymentException;
use PaymentManager\Contracts\PaymentResponseInterface;
use PaymentManager\Responses\PaymentResponse;

class WinipayerGateway extends AbstractGateway
{
    /**
     * @inheritDoc
     */
    protected function validateConfig(): void
    {
        $required = ['merchant_id', 'api_key', 'base_url'];

        foreach ($required as $field) {
            if (empty($this->config[$field])) {
                throw PaymentException::invalidConfiguration(
                    $this->name,
                    "Missing required configuration field: {$field}"
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function initializePayment(array $paymentData): PaymentResponseInterface
    {
        $this->logOperation('initialize_payment', $paymentData);

        $requiredFields = ['amount', 'currency', 'description', 'return_url', 'cancel_url'];
        foreach ($requiredFields as $field) {
            if (empty($paymentData[$field])) {
                return PaymentResponse::failure(
                    $this->name,
                    "Missing required field: {$field}"
                );
            }
        }

        $payload = [
            'merchant_id' => $this->config['merchant_id'],
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? $this->getCurrency(),
            'description' => $paymentData['description'],
            'reference' => $paymentData['transaction_id'] ?? uniqid('WP_'),
            'return_url' => $paymentData['return_url'],
            'cancel_url' => $paymentData['cancel_url'],
            'notify_url' => $paymentData['notify_url'] ?? $this->config['webhook_url'],
            'customer_email' => $paymentData['customer_email'] ?? null,
            'customer_phone' => $paymentData['customer_phone'] ?? null,
            'customer_name' => $paymentData['customer_name'] ?? null,
        ];

        // Add signature
        $payload['signature'] = $this->generateSignature($payload);

        try {
            $response = $this->makeRequest('POST', $this->getBaseUrl() . '/api/payment/init', [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                ],
            ]);

            $this->logOperation('initialize_payment_response', $paymentData, $response);

            if (isset($response['success']) && $response['success'] === true) {
                return PaymentResponse::success($this->name, [
                    'transaction_id' => $payload['reference'],
                    'status' => 'pending',
                    'amount' => $paymentData['amount'],
                    'currency' => $paymentData['currency'] ?? $this->getCurrency(),
                    'payment_url' => $response['data']['payment_url'] ?? null,
                    'metadata' => [
                        'winipayer_transaction_id' => $response['data']['transaction_id'] ?? null,
                        'payment_token' => $response['data']['payment_token'] ?? null,
                    ],
                    'raw_data' => $response,
                ]);
            }

            return PaymentResponse::failure(
                $this->name,
                $response['message'] ?? 'Payment initialization failed',
                $response['code'] ?? null,
                $response
            );

        } catch (PaymentException $e) {
            $this->logOperation('initialize_payment_error', $paymentData, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function verifyPayment(string $transactionId): PaymentResponseInterface
    {
        $this->logOperation('verify_payment', ['transaction_id' => $transactionId]);

        $payload = [
            'merchant_id' => $this->config['merchant_id'],
            'reference' => $transactionId,
        ];

        // Add signature
        $payload['signature'] = $this->generateSignature($payload);

        try {
            $response = $this->makeRequest('POST', $this->getBaseUrl() . '/api/payment/status', [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                ],
            ]);

            $this->logOperation('verify_payment_response', ['transaction_id' => $transactionId], $response);

            if (isset($response['success']) && $response['success'] === true) {
                $data = $response['data'] ?? [];
                return PaymentResponse::success($this->name, [
                    'transaction_id' => $transactionId,
                    'status' => $this->mapWinipayerStatus($data['status'] ?? 'UNKNOWN'),
                    'amount' => $data['amount'] ?? 0,
                    'currency' => $data['currency'] ?? $this->getCurrency(),
                    'metadata' => [
                        'winipayer_transaction_id' => $data['transaction_id'] ?? null,
                        'payment_method' => $data['payment_method'] ?? null,
                        'operator' => $data['operator'] ?? null,
                    ],
                    'raw_data' => $response,
                ]);
            }

            return PaymentResponse::failure(
                $this->name,
                $response['message'] ?? 'Payment verification failed',
                $response['code'] ?? null,
                $response
            );

        } catch (PaymentException $e) {
            $this->logOperation('verify_payment_error', ['transaction_id' => $transactionId], ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function processWebhook(array $webhookData): PaymentResponseInterface
    {
        $this->logOperation('process_webhook', $webhookData);

        // Verify webhook signature
        if (!$this->verifyWebhookSignature($webhookData)) {
            return PaymentResponse::failure($this->name, 'Invalid webhook signature');
        }

        $transactionId = $webhookData['reference'] ?? null;
        $status = $webhookData['status'] ?? 'UNKNOWN';

        return PaymentResponse::success($this->name, [
            'transaction_id' => $transactionId,
            'status' => $this->mapWinipayerStatus($status),
            'amount' => $webhookData['amount'] ?? 0,
            'currency' => $webhookData['currency'] ?? $this->getCurrency(),
            'metadata' => [
                'winipayer_transaction_id' => $webhookData['transaction_id'] ?? null,
                'payment_method' => $webhookData['payment_method'] ?? null,
                'operator' => $webhookData['operator'] ?? null,
            ],
            'raw_data' => $webhookData,
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function checkHealth(): bool
    {
        try {
            $response = $this->makeRequest('GET', $this->getBaseUrl() . '/api/health', [
                'timeout' => 5,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                ],
            ]);

            return isset($response['status']) && $response['status'] === 'OK';
        } catch (PaymentException $e) {
            return false;
        }
    }

    /**
     * Map Winipayer status to standard status
     *
     * @param string $winipayerStatus
     * @return string
     */
    protected function mapWinipayerStatus(string $winipayerStatus): string
    {
        $statusMap = [
            'SUCCESS' => 'completed',
            'PENDING' => 'pending',
            'FAILED' => 'failed',
            'CANCELLED' => 'cancelled',
            'EXPIRED' => 'expired',
        ];

        return $statusMap[strtoupper($winipayerStatus)] ?? 'unknown';
    }

    /**
     * Generate signature for API requests
     *
     * @param array $data
     * @return string
     */
    protected function generateSignature(array $data): string
    {
        // Remove signature field if present
        unset($data['signature']);

        // Sort keys alphabetically
        ksort($data);

        // Create signature string
        $signatureString = '';
        foreach ($data as $key => $value) {
            $signatureString .= $key . '=' . $value . '&';
        }
        $signatureString = rtrim($signatureString, '&');

        // Add API key
        $signatureString .= $this->config['api_key'];

        return hash('sha256', $signatureString);
    }

    /**
     * Verify webhook signature
     *
     * @param array $webhookData
     * @return bool
     */
    protected function verifyWebhookSignature(array $webhookData): bool
    {
        $signature = $webhookData['signature'] ?? '';
        $expectedSignature = $this->generateWebhookSignature($webhookData);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate signature for webhook verification
     *
     * @param array $data
     * @return string
     */
    protected function generateWebhookSignature(array $data): string
    {
        // Remove signature field if present
        unset($data['signature']);

        // Sort keys alphabetically
        ksort($data);

        // Create signature string
        $signatureString = '';
        foreach ($data as $key => $value) {
            $signatureString .= $key . '=' . $value . '&';
        }
        $signatureString = rtrim($signatureString, '&');

        // Add API key
        $signatureString .= $this->config['api_key'];

        return hash('sha256', $signatureString);
    }
}
