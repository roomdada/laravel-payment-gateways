<?php

namespace PaymentManager\Gateways;

use PaymentManager\Contracts\PaymentException;
use PaymentManager\Contracts\PaymentResponseInterface;
use PaymentManager\Responses\PaymentResponse;

class BizaoGateway extends AbstractGateway
{
    /**
     * @var string|null
     */
    protected ?string $accessToken = null;

    /**
     * @inheritDoc
     */
    protected function validateConfig(): void
    {
        $required = ['client_id', 'client_secret', 'base_url'];

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

        // Ensure we have a valid access token
        if (!$this->ensureAccessToken()) {
            return PaymentResponse::failure($this->name, 'Failed to obtain access token');
        }

        $payload = [
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? $this->getCurrency(),
            'description' => $paymentData['description'],
            'merchant_reference' => $paymentData['transaction_id'] ?? uniqid('BZ_'),
            'return_url' => $paymentData['return_url'],
            'cancel_url' => $paymentData['cancel_url'],
            'notify_url' => $paymentData['notify_url'] ?? $this->config['webhook_url'],
            'customer_email' => $paymentData['customer_email'] ?? null,
            'customer_phone' => $paymentData['customer_phone'] ?? null,
            'customer_name' => $paymentData['customer_name'] ?? null,
        ];

        try {
            $response = $this->makeRequest('POST', $this->getBaseUrl() . '/v1/payment/init', [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->accessToken,
                ],
            ]);

            $this->logOperation('initialize_payment_response', $paymentData, $response);

            if (isset($response['status']) && $response['status'] === 'success') {
                return PaymentResponse::success($this->name, [
                    'transaction_id' => $payload['merchant_reference'],
                    'status' => 'pending',
                    'amount' => $paymentData['amount'],
                    'currency' => $paymentData['currency'] ?? $this->getCurrency(),
                    'payment_url' => $response['data']['payment_url'] ?? null,
                    'metadata' => [
                        'bizao_transaction_id' => $response['data']['transaction_id'] ?? null,
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

        if (!$this->ensureAccessToken()) {
            return PaymentResponse::failure($this->name, 'Failed to obtain access token');
        }

        try {
            $response = $this->makeRequest('GET', $this->getBaseUrl() . '/v1/payment/status/' . $transactionId, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->accessToken,
                ],
            ]);

            $this->logOperation('verify_payment_response', ['transaction_id' => $transactionId], $response);

            if (isset($response['status']) && $response['status'] === 'success') {
                $data = $response['data'] ?? [];
                return PaymentResponse::success($this->name, [
                    'transaction_id' => $transactionId,
                    'status' => $this->mapBizaoStatus($data['payment_status'] ?? 'UNKNOWN'),
                    'amount' => $data['amount'] ?? 0,
                    'currency' => $data['currency'] ?? $this->getCurrency(),
                    'metadata' => [
                        'bizao_transaction_id' => $data['transaction_id'] ?? null,
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

        $transactionId = $webhookData['merchant_reference'] ?? null;
        $status = $webhookData['payment_status'] ?? 'UNKNOWN';

        return PaymentResponse::success($this->name, [
            'transaction_id' => $transactionId,
            'status' => $this->mapBizaoStatus($status),
            'amount' => $webhookData['amount'] ?? 0,
            'currency' => $webhookData['currency'] ?? $this->getCurrency(),
            'metadata' => [
                'bizao_transaction_id' => $webhookData['transaction_id'] ?? null,
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
        // L'utilisateur contrôle les endpoints via la configuration
        // Pas de vérification automatique - laisser l'utilisateur gérer
        return true;
    }

    /**
     * Ensure we have a valid access token
     *
     * @return bool
     */
    protected function ensureAccessToken(): bool
    {
        if ($this->accessToken) {
            return true;
        }

        try {
            $response = $this->makeRequest('POST', $this->getBaseUrl() . '/v1/auth/token', [
                'json' => [
                    'client_id' => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                    'grant_type' => 'client_credentials',
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            if (isset($response['access_token'])) {
                $this->accessToken = $response['access_token'];
                return true;
            }

            return false;
        } catch (PaymentException $e) {
            return false;
        }
    }

    /**
     * Map Bizao status to standard status
     *
     * @param string $bizaoStatus
     * @return string
     */
    protected function mapBizaoStatus(string $bizaoStatus): string
    {
        $statusMap = [
            'SUCCESS' => 'completed',
            'PENDING' => 'pending',
            'FAILED' => 'failed',
            'CANCELLED' => 'cancelled',
            'EXPIRED' => 'expired',
        ];

        return $statusMap[strtoupper($bizaoStatus)] ?? 'unknown';
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
        $expectedSignature = $this->generateSignature($webhookData);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate signature for webhook verification
     *
     * @param array $data
     * @return string
     */
    protected function generateSignature(array $data): string
    {
        $signatureData = [
            $data['merchant_reference'] ?? '',
            $data['amount'] ?? '',
            $data['currency'] ?? '',
            $data['payment_status'] ?? '',
            $this->config['client_secret'] ?? '',
        ];

        return hash('sha256', implode('', $signatureData));
    }
}
