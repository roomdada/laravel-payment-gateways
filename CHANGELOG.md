# Changelog

Toutes les modifications notables de ce projet Laravel Payment Gateways seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Support initial pour Cinetpay, Bizao et Winipayer
- Système de failover automatique entre gateways
- Gestion des webhooks avec validation de signature
- Logging complet des opérations de paiement
- Configuration flexible via variables d'environnement
- Tests unitaires et d'intégration
- Documentation complète avec exemples d'utilisation
- Architecture modulaire et extensible
- Retry automatique avec backoff exponentiel
- Interface unifiée pour tous les gateways de paiement

### Technical
- Interface `PaymentGatewayInterface` pour standardiser les gateways
- Classe abstraite `AbstractGateway` pour faciliter l'extension
- Gestionnaire principal `PaymentManager` avec failover intelligent
- Service provider Laravel pour l'intégration
- Migrations de base de données pour le stockage des transactions
- Contrôleur webhook pour traiter les notifications
- Exceptions personnalisées pour la gestion d'erreurs

## [1.0.0] - 2024-01-01

### Added
- Version initiale du package
- Support complet pour Cinetpay, Bizao et Winipayer
- Système de failover automatique
- Gestion des webhooks
- Logging complet
- Tests unitaires et d'intégration
