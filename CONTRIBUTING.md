# Guide de Contribution - Laravel Payment Gateways

Merci de votre intérêt pour contribuer au package Laravel Payment Gateways ! Ce document décrit le processus de contribution et les règles à suivre.

## 🎯 Avant de Commencer

### Prérequis
- PHP 8.1+
- Composer
- Git
- Compréhension de Laravel et des packages

### Communication
- **Issues** : Pour les bugs, suggestions d'amélioration, questions
- **Discussions** : Pour les discussions générales et l'aide
- **Pull Requests** : Pour les contributions de code

## 🔄 Processus de Contribution

### 1. Fork et Clone
```bash
# Fork le repository sur GitHub
# Puis clonez votre fork
git clone https://github.com/votre-username/laravel-payment-gateways.git
cd laravel-payment-gateways

# Ajoutez le repository original comme upstream
git remote add upstream https://github.com/roomdada/laravel-payment-gateways.git
```

### 2. Installation des Dépendances
```bash
composer install
```

### 3. Branches de Développement
```bash
# Créez une branche pour votre fonctionnalité
git checkout -b feature/nom-de-la-fonctionnalite

# Ou pour un bug fix
git checkout -b fix/nom-du-bug
```

### 4. Développement
- Suivez les standards de code PHP (PSR-12)
- Ajoutez des tests pour les nouvelles fonctionnalités
- Mettez à jour la documentation si nécessaire
- Respectez l'architecture existante

### 5. Tests
```bash
# Exécutez les tests
./vendor/bin/phpunit

# Vérifiez la qualité du code
./vendor/bin/phpstan analyse
./vendor/bin/php-cs-fixer fix --dry-run
```

### 6. Commit et Push
```bash
# Commits conventionnels
git commit -m "feat: ajouter support pour nouveau gateway"
git commit -m "fix: corriger problème d'authentification Bizao"
git commit -m "docs: mettre à jour la documentation"

# Push vers votre fork
git push origin feature/nom-de-la-fonctionnalite
```

### 7. Pull Request
- Créez une Pull Request vers la branche `main`
- Utilisez le template de PR fourni
- Décrivez clairement les changements
- Mentionnez les issues liées

## 📋 Standards de Code

### PHP
- **PSR-12** : Standards de codage PHP
- **Type hints** : Utilisez les types PHP 8.1+
- **DocBlocks** : Documentation des méthodes publiques
- **Namespaces** : Respectez la structure des namespaces

### Laravel
- **Service Providers** : Suivez les conventions Laravel
- **Configurations** : Utilisez les fichiers de config
- **Migrations** : Nommage conventionnel
- **Tests** : Tests unitaires et d'intégration

### Architecture
- **Interfaces** : Définissez des contrats clairs
- **Abstraction** : Utilisez l'abstraction appropriée
- **SOLID** : Respectez les principes SOLID
- **Flexibilité** : Privilégiez la configuration

## 🧪 Tests

### Types de Tests
- **Tests Unitaires** : Testez les classes individuellement
- **Tests d'Intégration** : Testez l'interaction entre composants
- **Tests de Fonctionnalité** : Testez les cas d'usage complets

### Exécution des Tests
```bash
# Tous les tests
./vendor/bin/phpunit

# Tests unitaires uniquement
./vendor/bin/phpunit --testsuite=Unit

# Tests d'intégration uniquement
./vendor/bin/phpunit --testsuite=Feature

# Avec couverture de code
./vendor/bin/phpunit --coverage-html coverage/
```

### Ajouter des Tests
```php
<?php

namespace PaymentManager\Tests\Unit;

use PaymentManager\Managers\PaymentManager;
use PHPUnit\Framework\TestCase;

class PaymentManagerTest extends TestCase
{
    public function test_initialize_payment_with_valid_data()
    {
        // Votre test ici
    }
}
```

## 📝 Documentation

### Mise à Jour de la Documentation
- **README.md** : Documentation principale
- **TROUBLESHOOTING.md** : Guide de dépannage
- **CHANGELOG.md** : Historique des changements
- **Exemples** : Code d'exemple dans `/examples/`

### Standards de Documentation
- **Clarté** : Écrivez de manière claire et concise
- **Exemples** : Incluez des exemples pratiques
- **Mise à jour** : Gardez la documentation à jour
- **Traduction** : Considérez les traductions si nécessaire

## 🔍 Review Process

### Code Review
- **Automatique** : Les PR sont automatiquement assignées aux code owners
- **Obligatoire** : Au moins une approbation requise
- **Standards** : Vérification des standards de code
- **Tests** : Vérification que les tests passent

### Critères d'Approval
- ✅ Code fonctionnel et testé
- ✅ Standards de code respectés
- ✅ Documentation mise à jour
- ✅ Tests ajoutés/modifiés
- ✅ Pas de régression

## 🚀 Release Process

### Versioning
- **Semantic Versioning** : MAJOR.MINOR.PATCH
- **Breaking Changes** : Incrémentez MAJOR
- **Nouvelles Fonctionnalités** : Incrémentez MINOR
- **Bug Fixes** : Incrémentez PATCH

### Release Notes
- **CHANGELOG.md** : Mise à jour obligatoire
- **GitHub Releases** : Notes de release détaillées
- **Migration Guide** : Si breaking changes

## 🐛 Reporting de Bugs

### Template d'Issue
```markdown
## Description
Description claire du problème

## Étapes pour Reproduire
1. Étape 1
2. Étape 2
3. Étape 3

## Comportement Attendu
Ce qui devrait se passer

## Comportement Actuel
Ce qui se passe actuellement

## Environnement
- PHP Version: 8.1+
- Laravel Version: 9.0+
- Package Version: 1.0.0
- OS: Linux/macOS/Windows

## Informations Supplémentaires
Logs, captures d'écran, etc.
```

## 💡 Suggestions d'Amélioration

### Template de Feature Request
```markdown
## Description
Description de la fonctionnalité souhaitée

## Cas d'Usage
Comment cette fonctionnalité serait utilisée

## Propositions d'Implémentation
Idées sur l'implémentation (optionnel)

## Alternatives Considérées
Autres approches possibles (optionnel)
```

## 📞 Support

### Questions et Aide
- **GitHub Issues** : Pour les bugs et questions techniques
- **GitHub Discussions** : Pour les discussions générales
- **Documentation** : Consultez d'abord la documentation

### Contact
- **Maintainer** : @roomdada
- **Email** : roomcodetraining@gmail.com

## 🙏 Remerciements

Merci à tous les contributeurs qui participent à l'amélioration de ce package !

---

**Note** : Ce guide peut être mis à jour. Vérifiez toujours la version la plus récente avant de contribuer.
