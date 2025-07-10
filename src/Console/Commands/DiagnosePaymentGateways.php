<?php

namespace PaymentManager\Console\Commands;

use Illuminate\Console\Command;
use PaymentManager\Contracts\PaymentManagerInterface;
use PaymentManager\Managers\PaymentManager;

class DiagnosePaymentGateways extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:diagnose {--gateway= : Diagnostiquer un gateway spécifique}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnostiquer les passerelles de paiement';

    /**
     * Execute the console command.
     */
    public function handle(PaymentManagerInterface $paymentManager)
    {
        $this->info('🔍 Diagnostic des Passerelles de Paiement');
        $this->newLine();

        // 1. Vérifier la configuration
        $this->checkConfiguration();

        // 2. Vérifier les variables d'environnement
        $this->checkEnvironmentVariables();

        // 3. Vérifier les gateways
        $this->checkGateways($paymentManager);

        // 4. Test de connectivité
        $this->testConnectivity($paymentManager);

        $this->newLine();
        $this->info('✅ Diagnostic terminé !');
    }

    /**
     * Vérifier la configuration
     */
    protected function checkConfiguration(): void
    {
        $this->info('1. 📋 Vérification de la configuration...');

        $config = config('laravel-payment-gateways');

        if (!$config) {
            $this->error('   ❌ Configuration non trouvée !');
            $this->warn('   💡 Exécutez : php artisan vendor:publish --tag=laravel-payment-gateways-config');
            return;
        }

        $this->info('   ✅ Configuration chargée');
        $this->info('   📍 Gateway par défaut : ' . ($config['default'] ?? 'NON DÉFINI'));
        $this->info('   🔢 Nombre de gateways configurés : ' . count($config['gateways'] ?? []));

        if (empty($config['gateways'])) {
            $this->warn('   ⚠️  Aucun gateway configuré !');
        }

        $this->newLine();
    }

    /**
     * Vérifier les variables d'environnement
     */
    protected function checkEnvironmentVariables(): void
    {
        $this->info('2. 🌍 Vérification des variables d\'environnement...');

        $gateways = ['cinetpay', 'bizao', 'winipayer'];
        $allGood = true;

        foreach ($gateways as $gateway) {
            $this->info("   📍 {$gateway}:");

            switch ($gateway) {
                case 'cinetpay':
                    $enabled = env('CINETPAY_ENABLED', true);
                    $apiKey = env('CINETPAY_API_KEY');
                    $siteId = env('CINETPAY_SITE_ID');
                    $environment = env('CINETPAY_ENVIRONMENT', 'TEST');

                    $this->info("      - Activé : " . ($enabled ? '✅' : '❌'));
                    $this->info("      - API Key : " . ($apiKey ? '✅' : '❌'));
                    $this->info("      - Site ID : " . ($siteId ? '✅' : '❌'));
                    $this->info("      - Environnement : {$environment}");

                    if (!$enabled || !$apiKey || !$siteId) {
                        $allGood = false;
                    }
                    break;

                case 'bizao':
                    $enabled = env('BIZAO_ENABLED', true);
                    $clientId = env('BIZAO_CLIENT_ID');
                    $clientSecret = env('BIZAO_CLIENT_SECRET');
                    $environment = env('BIZAO_ENVIRONMENT', 'sandbox');

                    $this->info("      - Activé : " . ($enabled ? '✅' : '❌'));
                    $this->info("      - Client ID : " . ($clientId ? '✅' : '❌'));
                    $this->info("      - Client Secret : " . ($clientSecret ? '✅' : '❌'));
                    $this->info("      - Environnement : {$environment}");

                    if (!$enabled || !$clientId || !$clientSecret) {
                        $allGood = false;
                    }
                    break;

                case 'winipayer':
                    $enabled = env('WINIPAYER_ENABLED', true);
                    $merchantId = env('WINIPAYER_MERCHANT_ID');
                    $apiKey = env('WINIPAYER_API_KEY');
                    $environment = env('WINIPAYER_ENVIRONMENT', 'test');

                    $this->info("      - Activé : " . ($enabled ? '✅' : '❌'));
                    $this->info("      - Merchant ID : " . ($merchantId ? '✅' : '❌'));
                    $this->info("      - API Key : " . ($apiKey ? '✅' : '❌'));
                    $this->info("      - Environnement : {$environment}");

                    if (!$enabled || !$merchantId || !$apiKey) {
                        $allGood = false;
                    }
                    break;
            }
        }

        if (!$allGood) {
            $this->warn('   ⚠️  Certaines variables d\'environnement sont manquantes !');
        } else {
            $this->info('   ✅ Toutes les variables d\'environnement sont configurées');
        }

        $this->newLine();
    }

    /**
     * Vérifier les gateways
     */
    protected function checkGateways(PaymentManagerInterface $paymentManager): void
    {
        $this->info('3. 🔌 Vérification des gateways...');

        $gateways = ['cinetpay', 'bizao', 'winipayer'];
        $initializedCount = 0;
        $availableCount = 0;

        foreach ($gateways as $gatewayName) {
            $gateway = $paymentManager->getGateway($gatewayName);

            if ($gateway) {
                $initializedCount++;
                $this->info("   📍 {$gatewayName}:");
                $this->info("      - Initialisé : ✅");
                $this->info("      - Activé : " . ($gateway->isEnabled() ? '✅' : '❌'));
                $this->info("      - Disponible : " . ($gateway->isAvailable() ? '✅' : '❌'));
                $this->info("      - Priorité : {$gateway->getPriority()}");

                if ($gateway->isAvailable()) {
                    $availableCount++;
                }
            } else {
                $this->info("   📍 {$gatewayName}: ❌ NON INITIALISÉ");
            }
        }

        $this->info("   📊 Résumé : {$initializedCount} initialisés, {$availableCount} disponibles");

        if ($availableCount === 0) {
            $this->error('   ❌ Aucun gateway disponible !');
            $this->warn('   💡 Vérifiez la configuration et les variables d\'environnement');
        }

        $this->newLine();
    }

    /**
     * Test de connectivité
     */
    protected function testConnectivity(PaymentManagerInterface $paymentManager): void
    {
        $this->info('4. 🌐 Test de connectivité...');

        $availableGateways = $paymentManager->getAvailableGateways();

        if (empty($availableGateways)) {
            $this->warn('   ⚠️  Aucun gateway disponible pour le test de connectivité');
            return;
        }

        foreach ($availableGateways as $name => $gateway) {
            $this->info("   📍 Test de {$name}...");

            try {
                // Test simple d'initialisation de paiement
                $paymentData = [
                    'amount' => 100,
                    'currency' => 'XOF',
                    'description' => 'Test de connectivité',
                    'return_url' => 'https://example.com/success',
                    'cancel_url' => 'https://example.com/cancel',
                    'transaction_id' => 'TEST_' . uniqid(),
                ];

                $response = $gateway->initializePayment($paymentData);

                if ($response->isSuccessful()) {
                    $this->info("      ✅ Connectivité OK");
                } else {
                    $this->warn("      ⚠️  Connectivité OK mais erreur métier : " . $response->getErrorMessage());
                }

            } catch (\Exception $e) {
                $this->error("      ❌ Erreur de connectivité : " . $e->getMessage());
            }
        }

        $this->newLine();
    }
}
