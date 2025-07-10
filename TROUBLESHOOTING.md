# Guide de Dépannage - Laravel Payment Gateways

## Problème : "Gateway 'all' failed: No payment gateways available"

Cette erreur indique qu'aucune passerelle de paiement n'est disponible ou configurée correctement. Voici les étapes pour diagnostiquer et résoudre le problème.

## 🔍 Diagnostic

### 1. Vérifier la Configuration

Assurez-vous que le fichier de configuration est publié et configuré :

```bash
php artisan vendor:publish --tag=laravel-payment-gateways-config
```

### 2. Vérifier les Variables d'Environnement

Ajoutez ces variables à votre fichier `.env` :

```env
# Gateway par défaut
PAYMENT_DEFAULT_GATEWAY=cinetpay

# Configuration Cinetpay (exemple)
CINETPAY_ENABLED=true
CINETPAY_API_KEY=your_api_key_here
CINETPAY_SITE_ID=your_site_id_here
CINETPAY_ENVIRONMENT=TEST  # ou PROD
CINETPAY_BASE_URL=https://api-checkout.cinetpay.com/v2
CINETPAY_CURRENCY=XOF
CINETPAY_WEBHOOK_URL=https://your-domain.com/payment/webhook/cinetpay

# Configuration Bizao (exemple)
BIZAO_ENABLED=true
BIZAO_CLIENT_ID=your_client_id_here
BIZAO_CLIENT_SECRET=your_client_secret_here
BIZAO_ENVIRONMENT=sandbox  # ou production
BIZAO_BASE_URL=https://api.bizao.com
BIZAO_CURRENCY=XOF
BIZAO_WEBHOOK_URL=https://your-domain.com/payment/webhook/bizao

# Configuration Winipayer (exemple)
WINIPAYER_ENABLED=true
WINIPAYER_MERCHANT_ID=your_merchant_id_here
WINIPAYER_API_KEY=your_api_key_here
WINIPAYER_ENVIRONMENT=test  # ou live
WINIPAYER_BASE_URL=https://api.winipayer.com
WINIPAYER_CURRENCY=XOF
WINIPAYER_WEBHOOK_URL=https://your-domain.com/payment/webhook/winipayer
```

### 3. Vérifier la Configuration via Code

Ajoutez ce code de diagnostic dans votre contrôleur ou commande Artisan :

```php
use PaymentManager\Contracts\PaymentManagerInterface;

public function diagnosePaymentGateways(PaymentManagerInterface $paymentManager)
{
    echo "=== Diagnostic des Passerelles de Paiement ===\n\n";

    // 1. Vérifier la configuration
    $config = config('laravel-payment-gateways');
    echo "1. Configuration chargée : " . ($config ? 'OUI' : 'NON') . "\n";
    echo "   Gateway par défaut : " . ($config['default'] ?? 'NON DÉFINI') . "\n";
    echo "   Nombre de gateways configurés : " . count($config['gateways'] ?? []) . "\n\n";

    // 2. Lister tous les gateways
    echo "2. Gateways configurés :\n";
    foreach ($config['gateways'] ?? [] as $name => $gatewayConfig) {
        echo "   - {$name} : " . ($gatewayConfig['enabled'] ? 'ACTIVÉ' : 'DÉSACTIVÉ') . "\n";
        if (!$gatewayConfig['enabled']) {
            echo "     Raison : Désactivé dans la configuration\n";
        }
    }
    echo "\n";

    // 3. Vérifier les gateways initialisés
    $allGateways = $paymentManager->getGateway('cinetpay') ? ['cinetpay'] : [];
    $allGateways = array_merge($allGateways, $paymentManager->getGateway('bizao') ? ['bizao'] : []);
    $allGateways = array_merge($allGateways, $paymentManager->getGateway('winipayer') ? ['winipayer'] : []);

    echo "3. Gateways initialisés : " . implode(', ', $allGateways) . "\n\n";

    // 4. Vérifier les gateways disponibles
    $availableGateways = $paymentManager->getAvailableGateways();
    echo "4. Gateways disponibles : " . count($availableGateways) . "\n";
    foreach ($availableGateways as $name => $gateway) {
        echo "   - {$name} (Priorité: {$gateway->getPriority()})\n";
    }
    echo "\n";

    // 5. Diagnostic détaillé par gateway
    echo "5. Diagnostic détaillé :\n";
    foreach (['cinetpay', 'bizao', 'winipayer'] as $gatewayName) {
        $gateway = $paymentManager->getGateway($gatewayName);
        if ($gateway) {
            echo "   {$gatewayName}:\n";
            echo "     - Activé : " . ($gateway->isEnabled() ? 'OUI' : 'NON') . "\n";
            echo "     - Disponible : " . ($gateway->isAvailable() ? 'OUI' : 'NON') . "\n";
            echo "     - Configuration : " . (empty($gateway->getConfig()) ? 'VIDE' : 'PRÉSENTE') . "\n";
        } else {
            echo "   {$gatewayName}: NON INITIALISÉ\n";
        }
    }
}
```

### 4. Vérifier les Logs

Activez le logging et vérifiez les logs :

```env
PAYMENT_LOGGING_ENABLED=true
PAYMENT_LOG_CHANNEL=payment
PAYMENT_LOG_LEVEL=debug
```

Puis vérifiez les logs :

```bash
tail -f storage/logs/payment.log
```

## 🛠️ Solutions Courantes

### Problème 1 : Configuration manquante

**Symptôme :** Aucun gateway n'est initialisé

**Solution :**
```php
// Vérifiez que la configuration est chargée
$config = config('laravel-payment-gateways');
if (empty($config['gateways'])) {
    // Publiez la configuration
    Artisan::call('vendor:publish', ['--tag' => 'laravel-payment-gateways-config']);
}
```

### Problème 2 : Variables d'environnement manquantes

**Symptôme :** Gateways initialisés mais non disponibles

**Solution :**
```php
// Vérifiez les variables d'environnement
if (empty(env('CINETPAY_API_KEY'))) {
    throw new Exception('CINETPAY_API_KEY manquante dans .env');
}
```

### Problème 3 : Validation de configuration échouée

**Symptôme :** Gateways non initialisés à cause d'erreurs de validation

**Solution :**
```php
// Utilisez une configuration minimale pour les tests
$config = [
    'default' => 'cinetpay',
    'gateways' => [
        'cinetpay' => [
            'enabled' => true,
            'priority' => 1,
            'api_key' => 'test_key',
            'site_id' => 'test_site',
            'environment' => 'TEST',
            'base_url' => 'https://api-checkout.cinetpay.com/v2',
            'currency' => 'XOF',
            'timeout' => 30,
        ],
    ],
    'failover' => [
        'enabled' => true,
        'max_retries' => 3,
        'retry_delay' => 2,
        'exponential_backoff' => true,
    ],
];

$paymentManager = new PaymentManager($config);
```

### Problème 4 : Mode test non configuré

**Symptôme :** Gateways en mode production mais sans vraies clés API

**Solution :**
```env
# Utilisez le mode test pour le développement
CINETPAY_ENVIRONMENT=TEST
BIZAO_ENVIRONMENT=sandbox
WINIPAYER_ENVIRONMENT=test
```

## 🧪 Test de Fonctionnement

Créez une commande Artisan pour tester le package :

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PaymentManager\Contracts\PaymentManagerInterface;

class TestPaymentGateways extends Command
{
    protected $signature = 'payment:test';
    protected $description = 'Test des passerelles de paiement';

    public function handle(PaymentManagerInterface $paymentManager)
    {
        $this->info('Test des passerelles de paiement...');

        try {
            // Test d'initialisation d'un paiement
            $paymentData = [
                'amount' => 1000,
                'currency' => 'XOF',
                'description' => 'Test de paiement',
                'return_url' => 'https://example.com/success',
                'cancel_url' => 'https://example.com/cancel',
                'transaction_id' => 'TEST_' . uniqid(),
            ];

            $response = $paymentManager->initializePayment($paymentData);

            if ($response->isSuccessful()) {
                $this->info('✅ Test réussi !');
                $this->info('Transaction ID: ' . $response->getTransactionId());
                $this->info('URL de paiement: ' . ($response->getData()['payment_url'] ?? 'N/A'));
            } else {
                $this->error('❌ Test échoué : ' . $response->getErrorMessage());
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception : ' . $e->getMessage());
        }
    }
}
```

## 📞 Support

Si le problème persiste après avoir suivi ce guide, vérifiez :

1. **Version de Laravel** : Le package supporte Laravel 9, 10 et 12
2. **Version de PHP** : PHP 8.1+ requis
3. **Dépendances** : `composer install` exécuté
4. **Cache** : `php artisan config:clear` et `php artisan cache:clear`

Pour plus d'aide, consultez la documentation complète ou ouvrez une issue sur GitHub.
