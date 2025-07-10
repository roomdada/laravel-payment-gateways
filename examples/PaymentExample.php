<?php

namespace PaymentManager\Examples;

use PaymentManager\Contracts\PaymentManagerInterface;
use PaymentManager\Contracts\PaymentException;

/**
 * Exemple d'utilisation du Payment Manager Package
 */
class PaymentExample
{
    public function __construct(
        private PaymentManagerInterface $paymentManager
    ) {}

    /**
     * Exemple d'initialisation d'un paiement
     */
    public function initializePaymentExample()
    {
        $paymentData = [
            'amount' => 5000.00,
            'currency' => 'XOF',
            'description' => 'Paiement pour commande #12345',
            'return_url' => 'https://votre-site.com/payment/success',
            'cancel_url' => 'https://votre-site.com/payment/cancel',
            'customer_email' => 'client@example.com',
            'customer_phone' => '+22670123456',
            'customer_name' => 'John Doe',
            'transaction_id' => 'CMD_' . uniqid(), // Optionnel, généré automatiquement si non fourni
        ];

        try {
            // Utiliser le gateway par défaut
            $response = $this->paymentManager->initializePayment($paymentData);

            if ($response->isSuccessful()) {
                echo "Paiement initialisé avec succès!\n";
                echo "Transaction ID: " . $response->getTransactionId() . "\n";
                echo "URL de paiement: " . $response->getPaymentUrl() . "\n";
                echo "Gateway utilisé: " . $response->getGatewayName() . "\n";

                // Rediriger l'utilisateur vers la page de paiement
                // header('Location: ' . $response->getPaymentUrl());
                // exit;
            } else {
                echo "Erreur lors de l'initialisation: " . $response->getErrorMessage() . "\n";
            }

        } catch (PaymentException $e) {
            echo "Exception de paiement: " . $e->getMessage() . "\n";
        } catch (\Exception $e) {
            echo "Erreur générale: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Exemple d'utilisation d'un gateway spécifique
     */
    public function useSpecificGatewayExample()
    {
        $paymentData = [
            'amount' => 2500.00,
            'currency' => 'XOF',
            'description' => 'Paiement avec Bizao',
            'return_url' => 'https://votre-site.com/payment/success',
            'cancel_url' => 'https://votre-site.com/payment/cancel',
        ];

        try {
            // Forcer l'utilisation de Bizao
            $response = $this->paymentManager->initializePayment($paymentData, 'bizao');

            if ($response->isSuccessful()) {
                echo "Paiement Bizao initialisé!\n";
                echo "Transaction ID: " . $response->getTransactionId() . "\n";
            }

        } catch (PaymentException $e) {
            echo "Erreur Bizao: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Exemple de vérification du statut d'un paiement
     */
    public function verifyPaymentExample(string $transactionId)
    {
        try {
            $response = $this->paymentManager->verifyPayment($transactionId);

            if ($response->isSuccessful()) {
                echo "Statut du paiement: " . $response->getStatus() . "\n";
                echo "Montant: " . $response->getAmount() . " " . $response->getCurrency() . "\n";
                echo "Gateway: " . $response->getGatewayName() . "\n";

                switch ($response->getStatus()) {
                    case 'completed':
                        echo "✅ Paiement réussi!\n";
                        $this->processSuccessfulPayment($response);
                        break;
                    case 'pending':
                        echo "⏳ Paiement en cours...\n";
                        break;
                    case 'failed':
                        echo "❌ Paiement échoué\n";
                        break;
                    case 'cancelled':
                        echo "🚫 Paiement annulé\n";
                        break;
                    default:
                        echo "❓ Statut inconnu\n";
                }
            } else {
                echo "Erreur de vérification: " . $response->getErrorMessage() . "\n";
            }

        } catch (PaymentException $e) {
            echo "Erreur de vérification: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Exemple de gestion des gateways disponibles
     */
    public function manageGatewaysExample()
    {
        // Obtenir tous les gateways disponibles
        $availableGateways = $this->paymentManager->getAvailableGateways();

        echo "Gateways disponibles:\n";
        foreach ($availableGateways as $name => $gateway) {
            echo "- {$name} (Priorité: {$gateway->getPriority()})\n";
        }

        // Obtenir le gateway par défaut
        $defaultGateway = $this->paymentManager->getDefaultGateway();
        echo "Gateway par défaut: {$defaultGateway}\n";

        // Changer le gateway par défaut
        if (isset($availableGateways['bizao'])) {
            $this->paymentManager->setDefaultGateway('bizao');
            echo "Gateway par défaut changé vers Bizao\n";
        }

        // Vérifier si le failover est activé
        $failoverEnabled = $this->paymentManager->isFailoverEnabled();
        echo "Failover activé: " . ($failoverEnabled ? 'Oui' : 'Non') . "\n";
    }

    /**
     * Exemple de traitement d'un webhook
     */
    public function processWebhookExample(array $webhookData, string $gatewayName)
    {
        try {
            $response = $this->paymentManager->processWebhook($webhookData, $gatewayName);

            if ($response->isSuccessful()) {
                echo "Webhook traité avec succès!\n";
                echo "Transaction ID: " . $response->getTransactionId() . "\n";
                echo "Statut: " . $response->getStatus() . "\n";

                if ($response->getStatus() === 'completed') {
                    $this->processSuccessfulPayment($response);
                }

                return true;
            } else {
                echo "Erreur de traitement webhook: " . $response->getErrorMessage() . "\n";
                return false;
            }

        } catch (PaymentException $e) {
            echo "Exception webhook: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Exemple de gestion des erreurs avec retry
     */
    public function handleErrorsWithRetryExample()
    {
        $paymentData = [
            'amount' => 1000.00,
            'currency' => 'XOF',
            'description' => 'Test avec retry',
            'return_url' => 'https://votre-site.com/success',
            'cancel_url' => 'https://votre-site.com/cancel',
        ];

        $maxAttempts = 3;
        $attempt = 1;

        while ($attempt <= $maxAttempts) {
            try {
                echo "Tentative {$attempt}...\n";

                $response = $this->paymentManager->initializePayment($paymentData);

                if ($response->isSuccessful()) {
                    echo "Succès à la tentative {$attempt}!\n";
                    return $response;
                }

                // Si c'est une erreur non-récupérable, ne pas retry
                if (in_array($response->getErrorCode(), ['INVALID_AMOUNT', 'INVALID_CURRENCY'])) {
                    echo "Erreur non-récupérable: " . $response->getErrorMessage() . "\n";
                    break;
                }

                echo "Échec, nouvelle tentative...\n";
                $attempt++;

                if ($attempt <= $maxAttempts) {
                    sleep(2); // Attendre 2 secondes avant de retry
                }

            } catch (PaymentException $e) {
                echo "Exception à la tentative {$attempt}: " . $e->getMessage() . "\n";
                $attempt++;

                if ($attempt <= $maxAttempts) {
                    sleep(2);
                }
            }
        }

        echo "Toutes les tentatives ont échoué\n";
        return null;
    }

    /**
     * Traitement d'un paiement réussi
     */
    private function processSuccessfulPayment($response)
    {
        echo "🎉 Traitement du paiement réussi!\n";
        echo "Transaction ID: " . $response->getTransactionId() . "\n";
        echo "Montant: " . $response->getAmount() . " " . $response->getCurrency() . "\n";
        echo "Gateway: " . $response->getGatewayName() . "\n";

        // Ici vous pouvez ajouter votre logique métier :
        // - Mettre à jour le statut de la commande
        // - Envoyer un email de confirmation
        // - Créer une facture
        // - etc.

        $metadata = $response->getMetadata();
        if (!empty($metadata)) {
            echo "Métadonnées: " . json_encode($metadata) . "\n";
        }
    }
}

// Exemple d'utilisation
if (php_sapi_name() === 'cli') {
    // Dans un contexte CLI, vous pouvez tester comme ceci :
    // $example = new PaymentExample($paymentManager);
    // $example->initializePaymentExample();
}
