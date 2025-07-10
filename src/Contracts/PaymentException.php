<?php

namespace PaymentManager\Contracts;

use Exception;

class PaymentException extends Exception
{
    /**
     * @var string|null
     */
    protected ?string $gatewayName = null;

    /**
     * @var array
     */
    protected array $context = [];

    /**
     * Create a new payment exception instance
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     * @param string|null $gatewayName
     * @param array $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        Exception $previous = null,
        ?string $gatewayName = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->gatewayName = $gatewayName;
        $this->context = $context;
    }

    /**
     * Get the gateway name that caused the exception
     *
     * @return string|null
     */
    public function getGatewayName(): ?string
    {
        return $this->gatewayName;
    }

    /**
     * Get additional context information
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Create a payment exception for gateway failure
     *
     * @param string $gatewayName
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function gatewayFailure(string $gatewayName, string $message, array $context = []): static
    {
        return new static(
            "Gateway '{$gatewayName}' failed: {$message}",
            500,
            null,
            $gatewayName,
            $context
        );
    }

    /**
     * Create a payment exception for invalid configuration
     *
     * @param string $gatewayName
     * @param string $message
     * @return static
     */
    public static function invalidConfiguration(string $gatewayName, string $message): static
    {
        return new static(
            "Invalid configuration for gateway '{$gatewayName}': {$message}",
            400,
            null,
            $gatewayName
        );
    }

    /**
     * Create a payment exception for network error
     *
     * @param string $gatewayName
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function networkError(string $gatewayName, string $message, array $context = []): static
    {
        return new static(
            "Network error for gateway '{$gatewayName}': {$message}",
            503,
            null,
            $gatewayName,
            $context
        );
    }
}
