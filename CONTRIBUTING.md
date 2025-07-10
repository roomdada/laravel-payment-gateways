# Guide de Contribution

Merci de votre intérêt pour contribuer au Laravel Payment Gateways Package ! Ce document vous guidera à travers le processus de contribution.

## 🚀 Comment Contribuer

### 1. Fork et Clone

1. Fork ce repository sur GitHub
2. Clone votre fork localement :
   ```bash
   git clone https://github.com/votre-username/laravel-payment-gateways.git
cd laravel-payment-gateways
   ```

### 2. Installation

```bash
composer install
```

### 3. Tests

Assurez-vous que tous les tests passent :

```bash
./vendor/bin/phpunit
```

### 4. Développement

1. Créez une branche pour votre fonctionnalité :
   ```bash
   git checkout -b feature/nouvelle-fonctionnalite
   ```

2. Développez votre fonctionnalité en suivant les standards de code

3. Ajoutez des tests pour votre code

4. Vérifiez que les tests passent :
   ```bash
   ./vendor/bin/phpunit
   ```

### 5. Commit et Push

```bash
git add .
git commit -m "feat: ajouter nouvelle fonctionnalité"
git push origin feature/nouvelle-fonctionnalite
```

### 6. Pull Request

1. Allez sur GitHub et créez une Pull Request
2. Décrivez clairement votre contribution
3. Attendez la review

## 📋 Standards de Code

### PHP

- Suivez les standards PSR-12
- Utilisez des noms de variables et méthodes descriptifs
- Ajoutez des commentaires PHPDoc pour les méthodes publiques
- Gardez les méthodes courtes et focalisées

### Tests

- Écrivez des tests pour toutes les nouvelles fonctionnalités
- Utilisez des noms de tests descriptifs
- Testez les cas d'erreur et les cas limites
- Maintenez une couverture de code élevée

### Documentation

- Mettez à jour la documentation si nécessaire
- Ajoutez des exemples d'utilisation
- Documentez les nouvelles fonctionnalités

## 🏗️ Architecture

### Ajouter un Nouveau Gateway

1. Créez une nouvelle classe qui étend `AbstractGateway`
2. Implémentez toutes les méthodes requises
3. Ajoutez des tests unitaires
4. Mettez à jour la documentation

Exemple :

```php
<?php

namespace PaymentManager\Gateways;

use PaymentManager\Contracts\PaymentException;
use PaymentManager\Contracts\PaymentResponseInterface;
use PaymentManager\Responses\PaymentResponse;

class MonNouveauGateway extends AbstractGateway
{
    protected function validateConfig(): void
    {
        // Validation de la configuration
    }

    public function initializePayment(array $paymentData): PaymentResponseInterface
    {
        // Logique d'initialisation
    }

    public function verifyPayment(string $transactionId): PaymentResponseInterface
    {
        // Logique de vérification
    }

    public function processWebhook(array $webhookData): PaymentResponseInterface
    {
        // Traitement webhook
    }

    protected function checkHealth(): bool
    {
        // Vérification de santé
    }
}
```

## 🐛 Signaler un Bug

1. Vérifiez que le bug n'a pas déjà été signalé
2. Créez une issue avec :
   - Description claire du problème
   - Étapes pour reproduire
   - Comportement attendu vs actuel
   - Version du package et de Laravel
   - Logs d'erreur si applicable

## 💡 Proposer une Fonctionnalité

1. Créez une issue pour discuter de la fonctionnalité
2. Décrivez le cas d'usage
3. Proposez une approche technique
4. Attendez la validation avant de commencer le développement

## 📝 Types de Contributions

### `feat:` - Nouvelles fonctionnalités
### `fix:` - Corrections de bugs
### `docs:` - Documentation
### `style:` - Formatage du code
### `refactor:` - Refactoring
### `test:` - Tests
### `chore:` - Maintenance

## 🤝 Code de Conduite

- Soyez respectueux envers les autres contributeurs
- Acceptez les critiques constructives
- Aidez les nouveaux contributeurs
- Restez focalisé sur l'amélioration du projet

## 📞 Besoin d'Aide ?

- Ouvrez une issue pour les questions
- Consultez la documentation
- Rejoignez notre communauté

Merci de contribuer ! 🎉
