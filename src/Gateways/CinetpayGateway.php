<?php

namespace PaymentManager\Gateways;

use PaymentManager\Contracts\PaymentException;
use PaymentManager\Contracts\PaymentResponseInterface;
use PaymentManager\Responses\PaymentResponse;

class CinetpayGateway extends AbstractGateway
{
    /**
     * @inheritDoc
     */
    protected function validateConfig(): void
    {
        $required = ['api_key', 'site_id', 'base_url'];

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
            'apikey' => $this->config['api_key'],
            'site_id' => $this->config['site_id'],
            'transaction_id' => $paymentData['transaction_id'] ?? uniqid('CP_'),
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? $this->getCurrency(),
            'description' => $paymentData['description'],
            'return_url' => $paymentData['return_url'],
            'cancel_url' => $paymentData['cancel_url'],
            'notify_url' => $paymentData['notify_url'] ?? $this->config['webhook_url'],
            'lang' => $paymentData['lang'] ?? 'fr',
            'channels' => $paymentData['channels'] ?? 'ALL',
        ];

        try {
            $response = $this->makeRequest('POST', $this->getBaseUrl() . '/payment', [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $this->logOperation('initialize_payment_response', $paymentData, $response);

            if (isset($response['code']) && $response['code'] === '201') {
                return PaymentResponse::success($this->name, [
                    'transaction_id' => $payload['transaction_id'],
                    'status' => 'pending',
                    'amount' => $paymentData['amount'],
                    'currency' => $paymentData['currency'] ?? $this->getCurrency(),
                    'payment_url' => $response['data']['payment_url'] ?? null,
                    'metadata' => [
                        'cinetpay_transaction_id' => $response['data']['transaction_id'] ?? null,
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
            'apikey' => $this->config['api_key'],
            'site_id' => $this->config['site_id'],
            'transaction_id' => $transactionId,
        ];

        try {
            $response = $this->makeRequest('POST', $this->getBaseUrl() . '/payment/check', [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $this->logOperation('verify_payment_response', ['transaction_id' => $transactionId], $response);

            if (isset($response['code']) && $response['code'] === '00') {
                $data = $response['data'] ?? [];
                return PaymentResponse::success($this->name, [
                    'transaction_id' => $transactionId,
                    'status' => $this->mapCinetpayStatus($data['status'] ?? 'UNKNOWN'),
                    'amount' => $data['amount'] ?? 0,
                    'currency' => $data['currency'] ?? $this->getCurrency(),
                    'metadata' => [
                        'cinetpay_transaction_id' => $data['transaction_id'] ?? null,
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

        if (!$this->verifyWebhookSignature($webhookData)) {
            return PaymentResponse::failure($this->name, 'Invalid webhook signature');
        }

        $transactionId = $webhookData['transaction_id'] ?? null;
        $status = $webhookData['status'] ?? 'UNKNOWN';

        return PaymentResponse::success($this->name, [
            'transaction_id' => $transactionId,
            'status' => $this->mapCinetpayStatus($status),
            'amount' => $webhookData['amount'] ?? 0,
            'currency' => $webhookData['currency'] ?? $this->getCurrency(),
            'metadata' => [
                'cinetpay_transaction_id' => $webhookData['transaction_id'] ?? null,
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
     * Map Cinetpay status to standard status
     *
     * @param string $cinetpayStatus
     * @return string
     */
    protected function mapCinetpayStatus(string $cinetpayStatus): string
    {
        $statusMap = [
            'SUCCESS' => 'completed',
            'PENDING' => 'pending',
            'FAILED' => 'failed',
            'CANCELLED' => 'cancelled',
            'EXPIRED' => 'expired',
        ];

        return $statusMap[strtoupper($cinetpayStatus)] ?? 'unknown';
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
            $data['transaction_id'] ?? '',
            $data['amount'] ?? '',
            $data['currency'] ?? '',
            $data['status'] ?? '',
            $this->config['api_key'] ?? '',
        ];

        return hash('sha256', implode('', $signatureData));
    }
}
