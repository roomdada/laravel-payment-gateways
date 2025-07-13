## 🐛 Description du Bug

Décrivez clairement et brièvement le problème.

## 🔄 Étapes pour Reproduire

1. Allez à '...'
2. Cliquez sur '...'
3. Faites défiler jusqu'à '...'
4. Voir l'erreur

## ✅ Comportement Attendu

Décrivez ce que vous pensiez qu'il allait se passer.

## ❌ Comportement Actuel

Décrivez ce qui s'est réellement passé.

## 📸 Captures d'Écran

Si applicable, ajoutez des captures d'écran pour expliquer votre problème.

## 💻 Environnement

**OS :** [ex: iOS, Windows, macOS]
**PHP Version :** [ex: 8.1, 8.2, 8.3]
**Laravel Version :** [ex: 9.0, 10.0, 11.0]
**Package Version :** [ex: 1.0.0, 1.1.0]
**Navigateur :** [ex: Chrome, Safari, Firefox]

## 📋 Informations Supplémentaires

Ajoutez tout autre contexte sur le problème ici.

## 🔍 Logs d'Erreur

```
Copiez-collez les logs d'erreur ici si disponibles
```

## 💡 Solutions Tentées

Décrivez les solutions que vous avez essayées pour résoudre le problème.

## 📝 Code de Reproduction

```php
// Code minimal pour reproduire le problème
$paymentManager = app('PaymentManager\Contracts\PaymentManagerInterface');
$response = $paymentManager->initializePayment([
    'amount' => 100,
    'currency' => 'XOF',
    // ...
]);
```

## 🏷️ Labels

- [ ] Bug
- [ ] Documentation
- [ ] Enhancement
- [ ] Good first issue
- [ ] Help wanted
- [ ] High priority
- [ ] Low priority
