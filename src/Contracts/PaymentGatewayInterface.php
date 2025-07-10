<?php

namespace PaymentManager\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Initialize a payment transaction
     *
     * @param array $paymentData
     * @return PaymentResponseInterface
     * @throws PaymentException
     */
    public function initializePayment(array $paymentData): PaymentResponseInterface;

    /**
     * Verify payment status
     *
     * @param string $transactionId
     * @return PaymentResponseInterface
     * @throws PaymentException
     */
    public function verifyPayment(string $transactionId): PaymentResponseInterface;

    /**
     * Process webhook notification
     *
     * @param array $webhookData
     * @return PaymentResponseInterface
     * @throws PaymentException
     */
    public function processWebhook(array $webhookData): PaymentResponseInterface;

    /**
     * Check if the gateway is available/healthy
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Get gateway configuration
     *
     * @return array
     */
    public function getConfig(): array;

    /**
     * Get gateway name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get gateway priority (lower number = higher priority)
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Check if gateway is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;
}
