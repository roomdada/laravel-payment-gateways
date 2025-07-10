<?php

namespace PaymentManager\Contracts;

interface PaymentResponseInterface
{
    /**
     * Check if the payment was successful
     *
     * @return bool
     */
    public function isSuccessful(): bool;

    /**
     * Get the transaction ID
     *
     * @return string|null
     */
    public function getTransactionId(): ?string;

    /**
     * Get the payment status
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Get the payment amount
     *
     * @return float
     */
    public function getAmount(): float;

    /**
     * Get the payment currency
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Get the gateway name that processed the payment
     *
     * @return string
     */
    public function getGatewayName(): string;

    /**
     * Get the raw response data from the gateway
     *
     * @return array
     */
    public function getRawData(): array;

    /**
     * Get error message if payment failed
     *
     * @return string|null
     */
    public function getErrorMessage(): ?string;

    /**
     * Get error code if payment failed
     *
     * @return string|null
     */
    public function getErrorCode(): ?string;

    /**
     * Get the payment URL for redirect-based payments
     *
     * @return string|null
     */
    public function getPaymentUrl(): ?string;

    /**
     * Get additional metadata
     *
     * @return array
     */
    public function getMetadata(): array;
}
