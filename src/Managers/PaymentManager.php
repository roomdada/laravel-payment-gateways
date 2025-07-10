<?php

namespace PaymentManager\Managers;

use PaymentManager\Contracts\PaymentException;
use PaymentManager\Contracts\PaymentGatewayInterface;
use PaymentManager\Contracts\PaymentManagerInterface;
use PaymentManager\Contracts\PaymentResponseInterface;
use PaymentManager\Gateways\BizaoGateway;
use PaymentManager\Gateways\CinetpayGateway;
use PaymentManager\Gateways\WinipayerGateway;

class PaymentManager implements PaymentManagerInterface
{
    /**
     * @var array
     */
    protected array $gateways = [];

    /**
     * @var string
     */
    protected string $defaultGateway;

    /**
     * @var bool
     */
    protected bool $failoverEnabled;

    /**
     * @var int
     */
    protected int $maxRetries;

    /**
     * @var int
     */
    protected int $retryDelay;

    /**
     * @var bool
     */
    protected bool $exponentialBackoff;

    /**
     * Create a new payment manager instance
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->defaultGateway = $config['default'] ?? 'cinetpay';
        $this->failoverEnabled = $config['failover']['enabled'] ?? true;
        $this->maxRetries = $config['failover']['max_retries'] ?? 3;
        $this->retryDelay = $config['failover']['retry_delay'] ?? 2;
        $this->exponentialBackoff = $config['failover']['exponential_backoff'] ?? true;

        $this->initializeGateways($config['gateways'] ?? []);
    }

    /**
     * Initialize payment gateways
     *
     * @param array $gatewayConfigs
     * @return void
     */
    protected function initializeGateways(array $gatewayConfigs): void
    {
        $gatewayClasses = [
            'cinetpay' => CinetpayGateway::class,
            'bizao' => BizaoGateway::class,
            'winipayer' => WinipayerGateway::class,
        ];

        foreach ($gatewayConfigs as $name => $config) {
            if (!isset($gatewayClasses[$name])) {
                continue;
            }

            if (!($config['enabled'] ?? true)) {
                continue;
            }

            try {
                $gatewayClass = $gatewayClasses[$name];
                $this->gateways[$name] = new $gatewayClass(
                    $config,
                    $name,
                    $config['priority'] ?? 999,
                    $config['enabled'] ?? true
                );
            } catch (PaymentException $e) {
                // Log gateway initialization error but continue
                $this->logError("Failed to initialize gateway {$name}: " . $e->getMessage());
            }
        }

        // Sort gateways by priority
        uasort($this->gateways, function (PaymentGatewayInterface $a, PaymentGatewayInterface $b) {
            return $a->getPriority() <=> $b->getPriority();
        });
    }

    /**
     * @inheritDoc
     */
    public function initializePayment(array $paymentData, ?string $preferredGateway = null): PaymentResponseInterface
    {
        $gateways = $this->getAvailableGateways();

        if (empty($gateways)) {
            throw PaymentException::gatewayFailure('all', 'No payment gateways available');
        }

        // If preferred gateway is specified and available, use it
        if ($preferredGateway && isset($gateways[$preferredGateway])) {
            return $this->tryGateway($preferredGateway, 'initializePayment', [$paymentData]);
        }

        // Try default gateway first
        if (isset($gateways[$this->defaultGateway])) {
            try {
                return $this->tryGateway($this->defaultGateway, 'initializePayment', [$paymentData]);
            } catch (PaymentException $e) {
                if (!$this->failoverEnabled) {
                    throw $e;
                }
                // Continue to failover
            }
        }

        // Try all available gateways in priority order
        foreach ($gateways as $name => $gateway) {
            try {
                return $this->tryGateway($name, 'initializePayment', [$paymentData]);
            } catch (PaymentException $e) {
                $this->logError("Gateway {$name} failed: " . $e->getMessage());

                if (!$this->failoverEnabled) {
                    throw $e;
                }

                // Continue to next gateway
            }
        }

        throw PaymentException::gatewayFailure('all', 'All payment gateways failed');
    }

    /**
     * @inheritDoc
     */
    public function verifyPayment(string $transactionId, ?string $gatewayName = null): PaymentResponseInterface
    {
        if ($gatewayName && isset($this->gateways[$gatewayName])) {
            return $this->tryGateway($gatewayName, 'verifyPayment', [$transactionId]);
        }

        // Try to find the gateway that processed this transaction
        foreach ($this->gateways as $name => $gateway) {
            try {
                return $this->tryGateway($name, 'verifyPayment', [$transactionId]);
            } catch (PaymentException $e) {
                // Continue to next gateway
            }
        }

        throw PaymentException::gatewayFailure('all', 'Could not verify payment with any gateway');
    }

    /**
     * @inheritDoc
     */
    public function processWebhook(array $webhookData, string $gatewayName): PaymentResponseInterface
    {
        if (!isset($this->gateways[$gatewayName])) {
            throw PaymentException::gatewayFailure($gatewayName, 'Gateway not found');
        }

        return $this->tryGateway($gatewayName, 'processWebhook', [$webhookData]);
    }

    /**
     * @inheritDoc
     */
    public function getAvailableGateways(): array
    {
        $available = [];

        foreach ($this->gateways as $name => $gateway) {
            if ($gateway->isEnabled() && $gateway->isAvailable()) {
                $available[$name] = $gateway;
            }
        }

        return $available;
    }

    /**
     * @inheritDoc
     */
    public function getGateway(string $gatewayName): ?PaymentGatewayInterface
    {
        return $this->gateways[$gatewayName] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setDefaultGateway(string $gatewayName): void
    {
        if (!isset($this->gateways[$gatewayName])) {
            throw PaymentException::invalidConfiguration($gatewayName, 'Gateway not found');
        }

        $this->defaultGateway = $gatewayName;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultGateway(): string
    {
        return $this->defaultGateway;
    }

    /**
     * @inheritDoc
     */
    public function isFailoverEnabled(): bool
    {
        return $this->failoverEnabled;
    }

    /**
     * @inheritDoc
     */
    public function setFailoverEnabled(bool $enabled): void
    {
        $this->failoverEnabled = $enabled;
    }

    /**
     * Try to execute a method on a gateway with retry logic
     *
     * @param string $gatewayName
     * @param string $method
     * @param array $arguments
     * @return PaymentResponseInterface
     * @throws PaymentException
     */
    protected function tryGateway(string $gatewayName, string $method, array $arguments): PaymentResponseInterface
    {
        $gateway = $this->gateways[$gatewayName];
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $response = $gateway->$method(...$arguments);

                // If it's a successful response, return it immediately
                if ($response->isSuccessful()) {
                    return $response;
                }

                // If it's a failure response, check if we should retry
                if ($this->shouldRetry($response, $attempt)) {
                    $this->logWarning("Gateway {$gatewayName} failed on attempt {$attempt}, retrying...");
                    $this->sleep($attempt);
                    continue;
                }

                // Don't retry for certain types of failures
                return $response;

            } catch (PaymentException $e) {
                $lastException = $e;

                if ($this->shouldRetryException($e, $attempt)) {
                    $this->logWarning("Gateway {$gatewayName} threw exception on attempt {$attempt}, retrying...");
                    $this->sleep($attempt);
                    continue;
                }

                // Don't retry for certain types of exceptions
                throw $e;
            }
        }

        // All retries exhausted
        if ($lastException) {
            throw $lastException;
        }

        throw PaymentException::gatewayFailure($gatewayName, 'All retry attempts failed');
    }

    /**
     * Determine if a response should be retried
     *
     * @param PaymentResponseInterface $response
     * @param int $attempt
     * @return bool
     */
    protected function shouldRetry(PaymentResponseInterface $response, int $attempt): bool
    {
        if ($attempt >= $this->maxRetries) {
            return false;
        }

        // Don't retry for certain error codes
        $nonRetryableCodes = ['INVALID_AMOUNT', 'INVALID_CURRENCY', 'INVALID_CONFIGURATION'];

        return !in_array($response->getErrorCode(), $nonRetryableCodes);
    }

    /**
     * Determine if an exception should be retried
     *
     * @param PaymentException $exception
     * @param int $attempt
     * @return bool
     */
    protected function shouldRetryException(PaymentException $exception, int $attempt): bool
    {
        if ($attempt >= $this->maxRetries) {
            return false;
        }

        // Retry network errors and temporary failures
        $retryableCodes = [503, 502, 504, 500];

        return in_array($exception->getCode(), $retryableCodes);
    }

    /**
     * Sleep between retry attempts
     *
     * @param int $attempt
     * @return void
     */
    protected function sleep(int $attempt): void
    {
        $delay = $this->retryDelay;

        if ($this->exponentialBackoff) {
            $delay = $this->retryDelay * pow(2, $attempt - 1);
        }

        sleep($delay);
    }

    /**
     * Log an error message
     *
     * @param string $message
     * @return void
     */
    protected function logError(string $message): void
    {
        if (function_exists('logger')) {
            logger()->error($message);
        }
    }

    /**
     * Log a warning message
     *
     * @param string $message
     * @return void
     */
    protected function logWarning(string $message): void
    {
        if (function_exists('logger')) {
            logger()->warning($message);
        }
    }
}
