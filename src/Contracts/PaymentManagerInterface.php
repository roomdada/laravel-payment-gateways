<?php

namespace PaymentManager\Contracts;

interface PaymentManagerInterface
{
    /**
     * Initialize a payment with automatic gateway selection
     *
     * @param array $paymentData
     * @param string|null $preferredGateway
     * @return PaymentResponseInterface
     * @throws PaymentException
     */
    public function initializePayment(array $paymentData, ?string $preferredGateway = null): PaymentResponseInterface;

    /**
     * Verify payment status
     *
     * @param string $transactionId
     * @param string|null $gatewayName
     * @return PaymentResponseInterface
     * @throws PaymentException
     */
    public function verifyPayment(string $transactionId, ?string $gatewayName = null): PaymentResponseInterface;

    /**
     * Process webhook notification
     *
     * @param array $webhookData
     * @param string $gatewayName
     * @return PaymentResponseInterface
     * @throws PaymentException
     */
    public function processWebhook(array $webhookData, string $gatewayName): PaymentResponseInterface;

    /**
     * Get available gateways
     *
     * @return array
     */
    public function getAvailableGateways(): array;

    /**
     * Get gateway by name
     *
     * @param string $gatewayName
     * @return PaymentGatewayInterface|null
     */
    public function getGateway(string $gatewayName): ?PaymentGatewayInterface;

    /**
     * Set the default gateway
     *
     * @param string $gatewayName
     * @return void
     */
    public function setDefaultGateway(string $gatewayName): void;

    /**
     * Get the default gateway
     *
     * @return string
     */
    public function getDefaultGateway(): string;

    /**
     * Check if failover is enabled
     *
     * @return bool
     */
    public function isFailoverEnabled(): bool;

    /**
     * Enable or disable failover
     *
     * @param bool $enabled
     * @return void
     */
    public function setFailoverEnabled(bool $enabled): void;
}
