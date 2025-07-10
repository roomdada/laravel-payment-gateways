<?php

namespace PaymentManager\Responses;

use PaymentManager\Contracts\PaymentResponseInterface;

class PaymentResponse implements PaymentResponseInterface
{
    /**
     * @var bool
     */
    protected bool $successful;

    /**
     * @var string|null
     */
    protected ?string $transactionId;

    /**
     * @var string
     */
    protected string $status;

    /**
     * @var float
     */
    protected float $amount;

    /**
     * @var string
     */
    protected string $currency;

    /**
     * @var string
     */
    protected string $gatewayName;

    /**
     * @var array
     */
    protected array $rawData;

    /**
     * @var string|null
     */
    protected ?string $errorMessage;

    /**
     * @var string|null
     */
    protected ?string $errorCode;

    /**
     * @var string|null
     */
    protected ?string $paymentUrl;

    /**
     * @var array
     */
    protected array $metadata;

    /**
     * Create a new payment response instance
     *
     * @param bool $successful
     * @param string $gatewayName
     * @param array $data
     */
    public function __construct(bool $successful, string $gatewayName, array $data = [])
    {
        $this->successful = $successful;
        $this->gatewayName = $gatewayName;
        $this->rawData = $data;

        $this->transactionId = $data['transaction_id'] ?? null;
        $this->status = $data['status'] ?? 'unknown';
        $this->amount = $data['amount'] ?? 0.0;
        $this->currency = $data['currency'] ?? 'XOF';
        $this->errorMessage = $data['error_message'] ?? null;
        $this->errorCode = $data['error_code'] ?? null;
        $this->paymentUrl = $data['payment_url'] ?? null;
        $this->metadata = $data['metadata'] ?? [];
    }

    /**
     * Create a successful payment response
     *
     * @param string $gatewayName
     * @param array $data
     * @return static
     */
    public static function success(string $gatewayName, array $data = []): static
    {
        return new static(true, $gatewayName, $data);
    }

    /**
     * Create a failed payment response
     *
     * @param string $gatewayName
     * @param string $errorMessage
     * @param string|null $errorCode
     * @param array $data
     * @return static
     */
    public static function failure(string $gatewayName, string $errorMessage, ?string $errorCode = null, array $data = []): static
    {
        $data['error_message'] = $errorMessage;
        $data['error_code'] = $errorCode;

        return new static(false, $gatewayName, $data);
    }

    /**
     * @inheritDoc
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * @inheritDoc
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @inheritDoc
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @inheritDoc
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @inheritDoc
     */
    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    /**
     * @inheritDoc
     */
    public function getRawData(): array
    {
        return $this->rawData;
    }

    /**
     * @inheritDoc
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * @inheritDoc
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * @inheritDoc
     */
    public function getPaymentUrl(): ?string
    {
        return $this->paymentUrl;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Convert the response to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'successful' => $this->successful,
            'transaction_id' => $this->transactionId,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'gateway_name' => $this->gatewayName,
            'error_message' => $this->errorMessage,
            'error_code' => $this->errorCode,
            'payment_url' => $this->paymentUrl,
            'metadata' => $this->metadata,
            'raw_data' => $this->rawData,
        ];
    }
}
