<?php

namespace PaymentManager\Gateways;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PaymentManager\Contracts\PaymentException;
use PaymentManager\Contracts\PaymentGatewayInterface;
use PaymentManager\Contracts\PaymentResponseInterface;
use PaymentManager\Responses\PaymentResponse;

abstract class AbstractGateway implements PaymentGatewayInterface
{
    /**
     * @var array
     */
    protected array $config;

    /**
     * @var Client
     */
    protected Client $httpClient;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var int
     */
    protected int $priority;

    /**
     * @var bool
     */
    protected bool $enabled;

    /**
     * Create a new gateway instance
     *
     * @param array $config
     * @param string $name
     * @param int $priority
     * @param bool $enabled
     */
    public function __construct(array $config, string $name, int $priority = 1, bool $enabled = true)
    {
        $this->config = $config;
        $this->name = $name;
        $this->priority = $priority;
        $this->enabled = $enabled;

        $this->httpClient = new Client([
            'timeout' => $config['timeout'] ?? 30,
            'connect_timeout' => 10,
        ]);

        $this->validateConfig();
    }

    /**
     * Validate gateway configuration
     *
     * @throws PaymentException
     */
    abstract protected function validateConfig(): void;

    /**
     * Make HTTP request to gateway API
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return array
     * @throws PaymentException
     */
    protected function makeRequest(string $method, string $url, array $options = []): array
    {
        try {
            $response = $this->httpClient->request($method, $url, $options);
            $body = $response->getBody()->getContents();

            return json_decode($body, true) ?: [];
        } catch (GuzzleException $e) {
            throw PaymentException::networkError(
                $this->name,
                $e->getMessage(),
                ['url' => $url, 'method' => $method]
            );
        }
    }

    /**
     * Log payment operation
     *
     * @param string $operation
     * @param array $data
     * @param array $response
     * @return void
     */
    protected function logOperation(string $operation, array $data, array $response = []): void
    {
        if (!config('laravel-payment-gateways.logging.enabled', true)) {
            return;
        }

        $logData = [
            'gateway' => $this->name,
            'operation' => $operation,
            'data' => $data,
            'response' => $response,
            'timestamp' => now()->toISOString(),
        ];

        $channel = config('laravel-payment-gateways.logging.channel', 'payment');
        $level = config('laravel-payment-gateways.logging.level', 'info');

        logger()->channel($channel)->log($level, 'Payment operation', $logData);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(): bool
    {
        // Un gateway est disponible s'il est activé et configuré
        // L'utilisateur contrôle les endpoints via la configuration
        return $this->enabled && !empty($this->config['base_url']);
    }

    /**
     * Check gateway health/availability
     *
     * @return bool
     * @throws PaymentException
     */
    protected function checkHealth(): bool
    {
        // Par défaut, considérer le gateway comme disponible
        // L'utilisateur peut surcharger cette méthode si nécessaire
        return true;
    }

    /**
     * Get base URL for API requests
     *
     * @return string
     */
    protected function getBaseUrl(): string
    {
        return $this->config['base_url'] ?? '';
    }

    /**
     * Get currency for payments
     *
     * @return string
     */
    protected function getCurrency(): string
    {
        return $this->config['currency'] ?? 'XOF';
    }

    /**
     * Get environment (production/test)
     *
     * @return string
     */
    protected function getEnvironment(): string
    {
        return $this->config['environment'] ?? 'production';
    }

    /**
     * Check if running in test mode
     *
     * @return bool
     */
    protected function isTestMode(): bool
    {
        $env = $this->getEnvironment();
        return in_array(strtolower($env), ['test', 'sandbox', 'dev']);
    }
}
