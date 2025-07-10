# Laravel Payment Gateways

Un package Laravel réutilisable pour gérer plusieurs agrégateurs de paiement (Cinetpay, Bizao, Winipayer) de manière modulaire, configurable et hautement flexible.

## 🚀 Fonctionnalités

- **Interface unifiée** : Une interface commune pour tous les gateways de paiement
- **Failover automatique** : Basculement automatique entre les gateways en cas de défaillance
- **Configuration dynamique** : Configuration via fichiers ou base de données
- **Webhooks intégrés** : Gestion automatique des notifications de paiement
- **Logging complet** : Traçabilité de toutes les opérations
- **Extensible** : Facile d'ajouter de nouveaux gateways
- **Tests complets** : Tests unitaires et d'intégration inclus

## 📋 Prérequis

- PHP 8.1+
- Laravel 9.0+ ou 10.0+
- Composer

## 🔧 Installation

1. **Installer le package via Composer :**

```bash
composer require room/laravel-payment-gateways
```

2. **Publier la configuration :**

```bash
php artisan vendor:publish --tag=laravel-payment-gateways-config
```

3. **Exécuter les migrations :**

```bash
php artisan migrate
```

## ⚙️ Configuration

### Variables d'environnement

Ajoutez ces variables à votre fichier `.env` :

```env
# Gateway par défaut
PAYMENT_DEFAULT_GATEWAY=cinetpay

# Configuration Cinetpay
CINETPAY_ENABLED=true
CINETPAY_API_KEY=your_api_key
CINETPAY_SITE_ID=your_site_id
CINETPAY_ENVIRONMENT=PROD
CINETPAY_BASE_URL=https://api-checkout.cinetpay.com/v2
CINETPAY_CURRENCY=XOF
CINETPAY_WEBHOOK_URL=https://your-domain.com/payment/webhook/cinetpay

# Configuration Bizao
BIZAO_ENABLED=true
BIZAO_CLIENT_ID=your_client_id
BIZAO_CLIENT_SECRET=your_client_secret
BIZAO_ENVIRONMENT=production
BIZAO_BASE_URL=https://api.bizao.com
BIZAO_CURRENCY=XOF
BIZAO_WEBHOOK_URL=https://your-domain.com/payment/webhook/bizao

# Configuration Winipayer
WINIPAYER_ENABLED=true
WINIPAYER_MERCHANT_ID=your_merchant_id
WINIPAYER_API_KEY=your_api_key
WINIPAYER_ENVIRONMENT=live
WINIPAYER_BASE_URL=https://api.winipayer.com
WINIPAYER_CURRENCY=XOF
WINIPAYER_WEBHOOK_URL=https://your-domain.com/payment/webhook/winipayer

# Configuration du failover
PAYMENT_FAILOVER_ENABLED=true
PAYMENT_MAX_RETRIES=3
PAYMENT_RETRY_DELAY=2
PAYMENT_EXPONENTIAL_BACKOFF=true

# Configuration des webhooks
PAYMENT_WEBHOOKS_ENABLED=true
PAYMENT_WEBHOOK_ROUTE_PREFIX=payment/webhook
PAYMENT_WEBHOOK_TIMEOUT=10

# Configuration du logging
PAYMENT_LOGGING_ENABLED=true
PAYMENT_LOG_CHANNEL=payment
PAYMENT_LOG_LEVEL=info
```

### Configuration avancée

Vous pouvez également configurer les gateways via le fichier `config/laravel-payment-gateways.php` :

```php
return [
    'default' => env('PAYMENT_DEFAULT_GATEWAY', 'cinetpay'),

    'gateways' => [
        'cinetpay' => [
            'enabled' => env('CINETPAY_ENABLED', true),
            'priority' => 1,
            'api_key' => env('CINETPAY_API_KEY'),
            'site_id' => env('CINETPAY_SITE_ID'),
            'environment' => env('CINETPAY_ENVIRONMENT', 'PROD'),
            'base_url' => env('CINETPAY_BASE_URL', 'https://api-checkout.cinetpay.com/v2'),
            'currency' => env('CINETPAY_CURRENCY', 'XOF'),
            'timeout' => env('CINETPAY_TIMEOUT', 30),
            'webhook_url' => env('CINETPAY_WEBHOOK_URL'),
        ],
        // ... autres gateways
    ],

    'failover' => [
        'enabled' => env('PAYMENT_FAILOVER_ENABLED', true),
        'max_retries' => env('PAYMENT_MAX_RETRIES', 3),
        'retry_delay' => env('PAYMENT_RETRY_DELAY', 2),
        'exponential_backoff' => env('PAYMENT_EXPONENTIAL_BACKOFF', true),
    ],
];
```

## 🎯 Utilisation

### Initialisation d'un paiement

```php
use PaymentManager\Contracts\PaymentManagerInterface;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentManagerInterface $paymentManager
    ) {}

    public function initializePayment(Request $request)
    {
        $paymentData = [
            'amount' => 1000.00,
            'currency' => 'XOF',
            'description' => 'Paiement pour commande #123',
            'return_url' => route('payment.success'),
            'cancel_url' => route('payment.cancel'),
            'customer_email' => 'client@example.com',
            'customer_phone' => '+22670123456',
            'customer_name' => 'John Doe',
        ];

        try {
            // Utiliser le gateway par défaut
            $response = $this->paymentManager->initializePayment($paymentData);

            // Ou spécifier un gateway préféré
            // $response = $this->paymentManager->initializePayment($paymentData, 'bizao');

            if ($response->isSuccessful()) {
                return redirect($response->getPaymentUrl());
            }

            return back()->withErrors(['payment' => $response->getErrorMessage()]);

        } catch (\Exception $e) {
            return back()->withErrors(['payment' => 'Erreur lors de l\'initialisation du paiement']);
        }
    }
}
```

### Vérification du statut d'un paiement

```php
public function verifyPayment(string $transactionId)
{
    try {
        $response = $this->paymentManager->verifyPayment($transactionId);

        if ($response->isSuccessful()) {
            switch ($response->getStatus()) {
                case 'completed':
                    return response()->json(['status' => 'success', 'message' => 'Paiement réussi']);
                case 'pending':
                    return response()->json(['status' => 'pending', 'message' => 'Paiement en cours']);
                case 'failed':
                    return response()->json(['status' => 'failed', 'message' => 'Paiement échoué']);
                default:
                    return response()->json(['status' => 'unknown', 'message' => 'Statut inconnu']);
            }
        }

        return response()->json(['status' => 'error', 'message' => $response->getErrorMessage()]);

    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'Erreur lors de la vérification']);
    }
}
```

### Gestion des webhooks

Les webhooks sont automatiquement configurés aux routes suivantes :
- `POST /payment/webhook/cinetpay`
- `POST /payment/webhook/bizao`
- `POST /payment/webhook/winipayer`

Vous pouvez personnaliser la gestion des webhooks en créant votre propre contrôleur :

```php
use PaymentManager\Contracts\PaymentManagerInterface;

class CustomWebhookController extends Controller
{
    public function __construct(
        private PaymentManagerInterface $paymentManager
    ) {}

    public function handleCinetpayWebhook(Request $request)
    {
        try {
            $response = $this->paymentManager->processWebhook($request->all(), 'cinetpay');

            if ($response->isSuccessful()) {
                // Traiter le paiement réussi
                $this->processSuccessfulPayment($response);
                return response('OK', 200);
            }

            return response('Error', 400);

        } catch (\Exception $e) {
            return response('Error: ' . $e->getMessage(), 500);
        }
    }

    private function processSuccessfulPayment($response)
    {
        // Votre logique métier ici
        // Par exemple, mettre à jour le statut de la commande
    }
}
```

### Gestion avancée des gateways

```php
// Obtenir tous les gateways disponibles
$availableGateways = $this->paymentManager->getAvailableGateways();

// Obtenir un gateway spécifique
$cinetpayGateway = $this->paymentManager->getGateway('cinetpay');

// Changer le gateway par défaut
$this->paymentManager->setDefaultGateway('bizao');

// Activer/désactiver le failover
$this->paymentManager->setFailoverEnabled(false);
```

## 🔧 Extension du package

### Ajouter un nouveau gateway

1. **Créer la classe du gateway :**

```php
<?php

namespace App\Gateways;

use PaymentManager\Gateways\AbstractGateway;
use PaymentManager\Contracts\PaymentException;
use PaymentManager\Contracts\PaymentResponseInterface;
use PaymentManager\Responses\PaymentResponse;

class CustomGateway extends AbstractGateway
{
    protected function validateConfig(): void
    {
        $required = ['api_key', 'base_url'];

        foreach ($required as $field) {
            if (empty($this->config[$field])) {
                throw PaymentException::invalidConfiguration(
                    $this->name,
                    "Missing required configuration field: {$field}"
                );
            }
        }
    }

    public function initializePayment(array $paymentData): PaymentResponseInterface
    {
        // Votre logique d'initialisation de paiement
        // ...

        return PaymentResponse::success($this->name, [
            'transaction_id' => $transactionId,
            'status' => 'pending',
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'],
            'payment_url' => $paymentUrl,
        ]);
    }

    public function verifyPayment(string $transactionId): PaymentResponseInterface
    {
        // Votre logique de vérification
        // ...

        return PaymentResponse::success($this->name, [
            'transaction_id' => $transactionId,
            'status' => $status,
            'amount' => $amount,
            'currency' => $currency,
        ]);
    }

    public function processWebhook(array $webhookData): PaymentResponseInterface
    {
        // Votre logique de traitement webhook
        // ...

        return PaymentResponse::success($this->name, [
            'transaction_id' => $transactionId,
            'status' => $status,
            'amount' => $amount,
            'currency' => $currency,
        ]);
    }

    protected function checkHealth(): bool
    {
        // Votre logique de vérification de santé
        // ...

        return true;
    }
}
```

2. **Enregistrer le gateway dans le service provider :**

```php
// Dans votre AppServiceProvider ou un service provider personnalisé
public function boot()
{
    $this->app->make(PaymentManagerInterface::class)->registerGateway('custom', CustomGateway::class);
}
```

## 🧪 Tests

### Exécuter les tests

```bash
# Tests unitaires
./vendor/bin/phpunit --testsuite=Unit

# Tests d'intégration
./vendor/bin/phpunit --testsuite=Feature

# Tous les tests
./vendor/bin/phpunit
```

### Exemple de test

```php
<?php

namespace PaymentManager\Tests\Unit;

use PaymentManager\Managers\PaymentManager;
use PaymentManager\Responses\PaymentResponse;
use PHPUnit\Framework\TestCase;

class PaymentManagerTest extends TestCase
{
    public function test_initialize_payment_with_default_gateway()
    {
        $config = [
            'default' => 'cinetpay',
            'gateways' => [
                'cinetpay' => [
                    'enabled' => true,
                    'priority' => 1,
                    'api_key' => 'test_key',
                    'site_id' => 'test_site',
                    'base_url' => 'https://api-test.cinetpay.com/v2',
                ],
            ],
        ];

        $manager = new PaymentManager($config);

        $paymentData = [
            'amount' => 1000.00,
            'currency' => 'XOF',
            'description' => 'Test payment',
            'return_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
        ];

        $response = $manager->initializePayment($paymentData);

        $this->assertInstanceOf(PaymentResponse::class, $response);
    }
}
```

## 📊 Monitoring et Logging

Le package enregistre automatiquement toutes les opérations de paiement. Vous pouvez configurer le canal de log dans votre configuration Laravel :

```php
// config/logging.php
'channels' => [
    'payment' => [
        'driver' => 'daily',
        'path' => storage_path('logs/payment.log'),
        'level' => env('PAYMENT_LOG_LEVEL', 'info'),
        'days' => 14,
    ],
],
```

## 🔒 Sécurité

- **Validation des signatures** : Tous les webhooks sont validés par signature
- **Configuration sécurisée** : Les credentials sont stockés dans les variables d'environnement
- **Logging sécurisé** : Les données sensibles ne sont pas loggées
- **Timeout configurable** : Protection contre les timeouts longs

## 🤝 Contribution

1. Fork le projet
2. Créer une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Commit vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 🆘 Support

Pour toute question ou problème :

- Ouvrir une issue sur GitHub
- Consulter la documentation
- Contacter l'équipe de développement

## 🔄 Changelog

### Version 1.0.0
- Support initial pour Cinetpay, Bizao et Winipayer
- Système de failover automatique
- Gestion des webhooks
- Logging complet
- Tests unitaires et d'intégration
