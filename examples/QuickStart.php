<?php

namespace PaymentManager\Examples;

use PaymentManager\Contracts\PaymentManagerInterface;
use PaymentManager\Contracts\PaymentException;

/**
 * Exemple de démarrage rapide pour le package Laravel Payment Gateways
 *
 * Ce fichier montre comment configurer et utiliser rapidement le package
 * pour résoudre le problème "No payment gateways available"
 */
class QuickStart
{
    private PaymentManagerInterface $paymentManager;

    public function __construct(PaymentManagerInterface $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * Configuration minimale pour les tests
     */
    public function setupMinimalConfig(): void
    {
        echo "=== Configuration Minimale ===\n\n";

        // 1. Vérifier que la configuration est publiée
        if (!config('laravel-payment-gateways')) {
            echo "❌ Configuration non trouvée !\n";
            echo "💡 Exécutez : php artisan vendor:publish --tag=laravel-payment-gateways-config\n\n";
            return;
        }

        echo "✅ Configuration trouvée\n";

        // 2. Vérifier les variables d'environnement minimales
        $this->checkMinimalEnvironment();

        // 3. Tester la disponibilité des gateways
        $this->testGatewayAvailability();

        echo "=== Configuration terminée ===\n\n";
    }

    /**
     * Vérifier les variables d'environnement minimales
     */
    private function checkMinimalEnvironment(): void
    {
        echo "📋 Vérification des variables d'environnement...\n";

        $gateways = [
            'cinetpay' => [
                'enabled' => env('CINETPAY_ENABLED', true),
                'api_key' => env('CINETPAY_API_KEY'),
                'site_id' => env('CINETPAY_SITE_ID'),
                'environment' => env('CINETPAY_ENVIRONMENT', 'TEST'),
            ],
            'bizao' => [
                'enabled' => env('BIZAO_ENABLED', true),
                'client_id' => env('BIZAO_CLIENT_ID'),
                'client_secret' => env('BIZAO_CLIENT_SECRET'),
                'environment' => env('BIZAO_ENVIRONMENT', 'sandbox'),
            ],
            'winipayer' => [
                'enabled' => env('WINIPAYER_ENABLED', true),
                'merchant_id' => env('WINIPAYER_MERCHANT_ID'),
                'api_key' => env('WINIPAYER_API_KEY'),
                'environment' => env('WINIPAYER_ENVIRONMENT', 'test'),
            ],
        ];

        $configuredGateways = [];

        foreach ($gateways as $name => $config) {
            if ($config['enabled']) {
                $hasRequiredConfig = false;

                switch ($name) {
                    case 'cinetpay':
                        $hasRequiredConfig = !empty($config['api_key']) && !empty($config['site_id']);
                        break;
                    case 'bizao':
                        $hasRequiredConfig = !empty($config['client_id']) && !empty($config['client_secret']);
                        break;
                    case 'winipayer':
                        $hasRequiredConfig = !empty($config['merchant_id']) && !empty($config['api_key']);
                        break;
                }

                if ($hasRequiredConfig) {
                    $configuredGateways[] = $name;
                    echo "   ✅ {$name} configuré ({$config['environment']})\n";
                } else {
                    echo "   ❌ {$name} non configuré (variables manquantes)\n";
                }
            } else {
                echo "   ⚠️  {$name} désactivé\n";
            }
        }

        if (empty($configuredGateways)) {
            echo "\n⚠️  Aucun gateway configuré !\n";
            echo "💡 Ajoutez au moins ces variables dans votre .env :\n\n";
            echo "CINETPAY_ENABLED=true\n";
            echo "CINETPAY_API_KEY=your_test_api_key\n";
            echo "CINETPAY_SITE_ID=your_test_site_id\n";
            echo "CINETPAY_ENVIRONMENT=TEST\n\n";
        } else {
            echo "\n✅ Gateways configurés : " . implode(', ', $configuredGateways) . "\n";
        }
    }

    /**
     * Tester la disponibilité des gateways
     */
    private function testGatewayAvailability(): void
    {
        echo "🔌 Test de disponibilité des gateways...\n";

        $availableGateways = $this->paymentManager->getAvailableGateways();

        if (empty($availableGateways)) {
            echo "❌ Aucun gateway disponible !\n";
            echo "💡 Vérifiez la configuration et les variables d'environnement\n\n";
            return;
        }

        echo "✅ Gateways disponibles : " . count($availableGateways) . "\n";
        foreach ($availableGateways as $name => $gateway) {
            echo "   - {$name} (Priorité: {$gateway->getPriority()})\n";
        }
        echo "\n";
    }

    /**
     * Exemple d'utilisation simple
     */
    public function simplePaymentExample(): void
    {
        echo "=== Exemple de Paiement Simple ===\n\n";

        try {
            // Données de paiement minimales
            $paymentData = [
                'amount' => 1000, // 1000 XOF
                'currency' => 'XOF',
                'description' => 'Test de paiement',
                'return_url' => 'https://example.com/success',
                'cancel_url' => 'https://example.com/cancel',
                'transaction_id' => 'TEST_' . uniqid(),
            ];

            echo "💰 Initialisation du paiement...\n";
            $response = $this->paymentManager->initializePayment($paymentData);

            if ($response->isSuccessful()) {
                echo "✅ Paiement initialisé avec succès !\n";
                echo "   Transaction ID: " . $response->getTransactionId() . "\n";
                echo "   Statut: " . $response->getStatus() . "\n";

                if (isset($response->getData()['payment_url'])) {
                    echo "   URL de paiement: " . $response->getData()['payment_url'] . "\n";
                }
            } else {
                echo "❌ Échec de l'initialisation : " . $response->getErrorMessage() . "\n";
            }

        } catch (PaymentException $e) {
            echo "❌ Exception : " . $e->getMessage() . "\n";
        }

        echo "\n=== Exemple terminé ===\n\n";
    }

    /**
     * Configuration pour le développement
     */
    public function developmentSetup(): void
    {
        echo "=== Configuration pour le Développement ===\n\n";

        echo "📝 Variables d'environnement recommandées pour le développement :\n\n";

        echo "# Gateway par défaut\n";
        echo "PAYMENT_DEFAULT_GATEWAY=cinetpay\n\n";

        echo "# Configuration Cinetpay (mode test)\n";
        echo "CINETPAY_ENABLED=true\n";
        echo "CINETPAY_API_KEY=test_api_key_123\n";
        echo "CINETPAY_SITE_ID=test_site_123\n";
        echo "CINETPAY_ENVIRONMENT=TEST\n";
        echo "CINETPAY_BASE_URL=https://api-checkout.cinetpay.com/v2\n";
        echo "CINETPAY_CURRENCY=XOF\n\n";

        echo "# Configuration Bizao (mode sandbox)\n";
        echo "BIZAO_ENABLED=true\n";
        echo "BIZAO_CLIENT_ID=test_client_id\n";
        echo "BIZAO_CLIENT_SECRET=test_client_secret\n";
        echo "BIZAO_ENVIRONMENT=sandbox\n";
        echo "BIZAO_BASE_URL=https://api.bizao.com\n";
        echo "BIZAO_CURRENCY=XOF\n\n";

        echo "# Configuration Winipayer (mode test)\n";
        echo "WINIPAYER_ENABLED=true\n";
        echo "WINIPAYER_MERCHANT_ID=test_merchant_id\n";
        echo "WINIPAYER_API_KEY=test_api_key\n";
        echo "WINIPAYER_ENVIRONMENT=test\n";
        echo "WINIPAYER_BASE_URL=https://api.winipayer.com\n";
        echo "WINIPAYER_CURRENCY=XOF\n\n";

        echo "# Configuration du failover\n";
        echo "PAYMENT_FAILOVER_ENABLED=true\n";
        echo "PAYMENT_MAX_RETRIES=3\n";
        echo "PAYMENT_RETRY_DELAY=2\n\n";

        echo "# Configuration du logging\n";
        echo "PAYMENT_LOGGING_ENABLED=true\n";
        echo "PAYMENT_LOG_CHANNEL=payment\n";
        echo "PAYMENT_LOG_LEVEL=debug\n\n";

        echo "💡 Après avoir ajouté ces variables, exécutez :\n";
        echo "   php artisan config:clear\n";
        echo "   php artisan payment:diagnose\n\n";
    }

    /**
     * Résolution rapide du problème "No payment gateways available"
     */
    public function quickFix(): void
    {
        echo "=== Résolution Rapide ===\n\n";

        echo "🔧 Étapes pour résoudre le problème \"No payment gateways available\" :\n\n";

        echo "1. Publier la configuration :\n";
        echo "   php artisan vendor:publish --tag=laravel-payment-gateways-config\n\n";

        echo "2. Ajouter les variables minimales dans .env :\n";
        echo "   CINETPAY_ENABLED=true\n";
        echo "   CINETPAY_API_KEY=test_key\n";
        echo "   CINETPAY_SITE_ID=test_site\n";
        echo "   CINETPAY_ENVIRONMENT=TEST\n\n";

        echo "3. Vider le cache :\n";
        echo "   php artisan config:clear\n";
        echo "   php artisan cache:clear\n\n";

        echo "4. Diagnostiquer :\n";
        echo "   php artisan payment:diagnose\n\n";

        echo "5. Tester :\n";
        echo "   php artisan tinker\n";
        echo "   >>> app('PaymentManager\\Contracts\\PaymentManagerInterface')->getAvailableGateways();\n\n";

        echo "✅ Si le problème persiste, consultez TROUBLESHOOTING.md\n\n";
    }
}
