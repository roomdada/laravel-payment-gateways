<?php

namespace PaymentManager\Examples;

use PaymentManager\Contracts\PaymentManagerInterface;
use PaymentManager\Gateways\BizaoGateway;

/**
 * Exemple pour tester l'authentification Bizao
 * et diagnostiquer le problème d'access token
 */
class BizaoAuthTest
{
    private PaymentManagerInterface $paymentManager;

    public function __construct(PaymentManagerInterface $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * Test d'authentification Bizao
     */
    public function testBizaoAuth(): void
    {
        echo "=== Test d'Authentification Bizao ===\n\n";

        // 1. Vérifier la configuration
        $this->checkBizaoConfig();

        // 2. Tester l'authentification
        $this->testAuthentication();

        // 3. Tester l'initialisation de paiement
        $this->testPaymentInitialization();

        echo "=== Test terminé ===\n\n";
    }

    /**
     * Vérifier la configuration Bizao
     */
    private function checkBizaoConfig(): void
    {
        echo "1. 📋 Vérification de la configuration Bizao...\n";

        $bizaoGateway = $this->paymentManager->getGateway('bizao');

        if (!$bizaoGateway) {
            echo "   ❌ Gateway Bizao non trouvé\n";
            return;
        }

        $config = $bizaoGateway->getConfig();

        echo "   ✅ Gateway Bizao trouvé\n";
        echo "   📍 Client ID: " . (isset($config['client_id']) ? 'PRÉSENT' : 'MANQUANT') . "\n";
        echo "   📍 Client Secret: " . (isset($config['client_secret']) ? 'PRÉSENT' : 'MANQUANT') . "\n";
        echo "   📍 Base URL: " . ($config['base_url'] ?? 'NON DÉFINI') . "\n";
        echo "   📍 Environnement: " . ($config['environment'] ?? 'NON DÉFINI') . "\n";
        echo "   📍 Mode test: " . ($bizaoGateway->isTestMode() ? 'OUI' : 'NON') . "\n\n";
    }

    /**
     * Tester l'authentification
     */
    private function testAuthentication(): void
    {
        echo "2. 🔐 Test d'authentification...\n";

        $bizaoGateway = $this->paymentManager->getGateway('bizao');

        if (!$bizaoGateway) {
            echo "   ❌ Gateway Bizao non trouvé\n";
            return;
        }

        try {
            // Utiliser la réflexion pour accéder à la méthode privée
            $reflection = new \ReflectionClass($bizaoGateway);
            $method = $reflection->getMethod('ensureAccessToken');
            $method->setAccessible(true);

            $result = $method->invoke($bizaoGateway);

            if ($result) {
                echo "   ✅ Authentification réussie\n";

                // Récupérer le token (si possible)
                $tokenProperty = $reflection->getProperty('accessToken');
                $tokenProperty->setAccessible(true);
                $token = $tokenProperty->getValue($bizaoGateway);

                if ($token) {
                    echo "   📍 Token obtenu: " . substr($token, 0, 20) . "...\n";
                }
            } else {
                echo "   ❌ Authentification échouée\n";
            }

        } catch (\Exception $e) {
            echo "   ❌ Erreur d'authentification: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    /**
     * Tester l'initialisation de paiement
     */
    private function testPaymentInitialization(): void
    {
        echo "3. 💳 Test d'initialisation de paiement...\n";

        $data = [
            'amount' => 100,
            'currency' => 'XOF',
            'description' => 'Test d\'authentification Bizao',
            'return_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+22670123456',
            'customer_name' => 'Test User',
        ];

        try {
            $response = $this->paymentManager->initializePayment($data, 'bizao');

            if ($response->isSuccessful()) {
                echo "   ✅ Paiement initialisé avec succès\n";
                echo "   📍 Transaction ID: " . $response->getTransactionId() . "\n";
                echo "   📍 Statut: " . $response->getStatus() . "\n";

                $responseData = $response->getData();
                if (isset($responseData['payment_url'])) {
                    echo "   📍 URL de paiement: " . $responseData['payment_url'] . "\n";
                }
            } else {
                echo "   ❌ Échec de l'initialisation\n";
                echo "   📍 Erreur: " . $response->getErrorMessage() . "\n";
                echo "   📍 Code: " . $response->getErrorCode() . "\n";
            }

        } catch (\Exception $e) {
            echo "   ❌ Exception: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    /**
     * Test manuel de l'API d'authentification
     */
    public function testManualAuth(): void
    {
        echo "=== Test Manuel de l'API d'Authentification ===\n\n";

        $clientId = env('BIZAO_CLIENT_ID');
        $clientSecret = env('BIZAO_CLIENT_SECRET');
        $baseUrl = env('BIZAO_BASE_URL', 'https://api.bizao.com');

        echo "1. 📋 Configuration:\n";
        echo "   📍 Client ID: " . ($clientId ? 'PRÉSENT' : 'MANQUANT') . "\n";
        echo "   📍 Client Secret: " . ($clientSecret ? 'PRÉSENT' : 'MANQUANT') . "\n";
        echo "   📍 Base URL: {$baseUrl}\n\n";

        if (!$clientId || !$clientSecret) {
            echo "❌ Credentials manquants !\n";
            return;
        }

        echo "2. 🌐 Test de connectivité...\n";

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 10]);

            $response = $client->post($baseUrl . '/v1/auth/token', [
                'json' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'grant_type' => 'client_credentials',
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            echo "   ✅ Connexion réussie\n";
            echo "   📍 Status Code: " . $response->getStatusCode() . "\n";
            echo "   📍 Response: " . substr($body, 0, 200) . "...\n";

            if (isset($data['access_token'])) {
                echo "   ✅ Token obtenu: " . substr($data['access_token'], 0, 20) . "...\n";
            } else {
                echo "   ❌ Token non trouvé dans la réponse\n";
            }

        } catch (\Exception $e) {
            echo "   ❌ Erreur de connexion: " . $e->getMessage() . "\n";
        }

        echo "\n=== Test manuel terminé ===\n\n";
    }

    /**
     * Configuration recommandée pour le développement
     */
    public function showRecommendedConfig(): void
    {
        echo "=== Configuration Recommandée pour le Développement ===\n\n";

        echo "📝 Variables d'environnement recommandées:\n\n";

        echo "# Configuration Bizao (mode sandbox)\n";
        echo "BIZAO_ENABLED=true\n";
        echo "BIZAO_CLIENT_ID=your_sandbox_client_id\n";
        echo "BIZAO_CLIENT_SECRET=your_sandbox_client_secret\n";
        echo "BIZAO_ENVIRONMENT=sandbox\n";
        echo "BIZAO_BASE_URL=https://api.bizao.com\n";
        echo "BIZAO_CURRENCY=XOF\n";
        echo "BIZAO_TIMEOUT=30\n\n";

        echo "💡 Notes:\n";
        echo "- Utilisez des credentials de sandbox pour le développement\n";
        echo "- Vérifiez que l'URL de base est correcte\n";
        echo "- Assurez-vous que les credentials sont valides\n";
        echo "- En mode sandbox, certains endpoints peuvent être différents\n\n";
    }
}
