<div align="center">

# 💰 Fundry — Multi-Currency Wallet & Financial Ledger for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hamadou/fundry.svg?style=for-the-badge)](https://packagist.org/packages/hamadou/fundry)
[![Total Downloads](https://img.shields.io/packagist/dt/hamadou/fundry.svg?style=for-the-badge)](https://packagist.org/packages/hamadou/fundry)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012%20%7C%2013-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![License](https://img.shields.io/packagist/l/hamadou/fundry.svg?style=for-the-badge)](LICENSE)

**Fundry** est le package Laravel de référence pour intégrer des **portefeuilles virtuels (e-wallets)**, gérer des **transactions financières atomiques (ACID)** et supporter des **devises multiples (Fiat & Crypto)** avec une précision mathématique stricte via **`bcmath`**.

[Fonctionnalités](#-fonctionnalités-clés) • [Installation](#-installation-rapide) • [Configuration](#️-configuration--transferts-inter-devises) • [Guide d'utilisation](#-guide-dutilisation) • [Commandes CLI](#️-commandes-artisan-cli) • [Sécurité](#-sécurité--concurrence)

</div>

---

## 🌟 Pourquoi choisir Fundry ?

Construire un système financier dans une application web ne tolère aucune approximation : erreurs d'arrondi flottant, conditions de concurrence (*race conditions*), deadlocks SQL ou incohérences de taux de change peuvent coûter cher. **Fundry** vous fournit une fondation financière clé en main, testée et prête pour la production :

| Pilier | Ce que Fundry vous garantit |
|---|---|
| 🧮 **Précision `bcmath`** | Calculs monétaires exacts jusqu'à **8 décimales** (idéal pour les centimes Fiat et les Satoshis Crypto). |
| 🔒 **Zéro Race Condition & Zéro Deadlock** | Transactions SQL atomiques avec verrouillage pessimiste (`SELECT ... FOR UPDATE`) ordonné par ID croissant. |
| 💱 **Multi-Devises Hybride (Fiat & Crypto)** | Support natif ISO 4217 (`USD`, `EUR`, `XOF`, `MAD`, `NGN`...) et cryptomonnaies (`BTC`, `ETH`, `USDT`...). |
| 🔀 **Transferts Inter-Devises au choix (Option A / B)** | Activez la **conversion automatique** à la volée (**Option A**) ou **bloquez** les transferts entre devises différentes (**Option B**) via une simple variable `.env` ou par transaction. |
| 💸 **Moteur de Commissions Transparent** | Calcul automatique des frais sur dépôts, retraits et transferts avec traçabilité complète dans les métadonnées. |
| 📊 **Reporting PDF & Excel intégré** | Exportez vos journaux de transactions, portefeuilles, devises et limites en `.pdf` ou `.xlsx` en une commande. |

---

## ✨ Fonctionnalités clés

### 👛 Gestion avancée des portefeuilles (`Wallet`)
- Portefeuilles illimités par utilisateur (un par devise ou par usage).
- Typage fort via `WalletType` : `PERSONAL`, `BUSINESS`, `SAVINGS`, `INVESTMENT`, `GOVERNMENT`.
- Garde-fous financiers configurables par portefeuille :
  - `max_balance` : Plafond maximal de solde autorisé.
  - `min_balance` : Solde plancher incompressible.
  - `transaction_limit` : Montant maximal par opération.
- Gestion automatique du portefeuille principal (`is_default`).

### 💱 Devises (`Currency`) & Pays (`Country`)
- Liaison native entre les pays (**ISO 3166-1 alpha-2/alpha-3**) et leurs devises officielles (**ISO 4217**).
- Support des cryptomonnaies (tickers jusqu'à 10 caractères : `BTC`, `ETH`, `USDT`, etc.).
- Synchronisation des taux de change manuelle ou automatisée via **ExchangeRate-API**.
- Cache intelligent compatible avec tous les drivers Laravel (`file`, `database`, `redis`, `array`).

### 🧾 Transactions & Auditabilité (`Transaction`)
- Identifiants **UUID** et références uniques auto-générées (`TXN2026...`).
- Types d'opérations (`TransactionType`) : `DEPOSIT`, `WITHDRAWAL`, `TRANSFER`, `EXCHANGE`, `FEE`, `REFUND`.
- Cycle de vie (`TransactionStatus`) : `PENDING`, `COMPLETED`, `FAILED`, `CANCELLED`, `REVERSED`.
- Événements Laravel déclenchés en temps réel : `WalletCreated` et `TransactionCreated`.

---

## 📦 Prérequis

- **PHP** : `^8.1` (compatible PHP 8.1, 8.2, 8.3, 8.4, 8.5)
- **Extensions PHP** : `ext-bcmath` et `ext-json`
- **Laravel** : `10.x`, `11.x`, `12.x` ou `13.x`
- **Base de données** : MySQL, PostgreSQL, SQLite ou SQL Server

---

## 🚀 Installation rapide

### 1. Installer le package via Composer

```bash
composer require hamadou/fundry
```

*(Optionnel)* Pour activer la génération de rapports **PDF** :
```bash
composer require barryvdh/laravel-dompdf:^3.0
```

### 2. Lancer l'installateur Fundry

```bash
php artisan fundry:install
```
> Cette commande publie automatiquement le fichier `config/fundry.php` et les migrations dans l'ordre strict des clés étrangères (`countries` → `currencies` → `wallets` → `transactions`).

### 3. Exécuter les migrations et initialiser les données

```bash
php artisan migrate
php artisan fundry:currencies seed
```
*(Ou utilisez `php artisan db:seed --class="Hamadou\Fundry\Seeders\FundrySeeder"`)*

### 4. Ajouter le trait `HasWallets` à votre modèle `User`

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Hamadou\Fundry\Traits\HasWallets;

class User extends Authenticatable
{
    use HasWallets;
}
```

---

## ⚙️ Configuration & Transferts Inter-Devises

Le fichier `config/fundry.php` (et vos variables `.env`) vous permet de piloter finement le comportement du package :

```env
FUNDRY_DEFAULT_CURRENCY=USD
FUNDRY_PRECISION=8
FUNDRY_ALLOW_CROSS_CURRENCY_TRANSFERS=true

# Taux de change
FUNDRY_EXCHANGE_RATE_PROVIDER=manual
FUNDRY_EXCHANGE_RATE_API_KEY=your_api_key_here

# Cache
FUNDRY_CACHE_ENABLED=true
FUNDRY_CACHE_TTL=3600

# Limites quotidiennes par défaut
FUNDRY_DAILY_DEPOSIT_LIMIT=10000
FUNDRY_DAILY_WITHDRAWAL_LIMIT=10000
FUNDRY_DAILY_TRANSFER_LIMIT=50000
```

### 🔀 Mode de Transfert Inter-Devises : Option A vs Option B

Vous avez le contrôle total sur la façon dont Fundry gère un transfert entre deux portefeuilles de devises différentes (ex: `USD` → `EUR` ou `XOF` → `USD`) :

- **Option A — Conversion automatique (`FUNDRY_ALLOW_CROSS_CURRENCY_TRANSFERS=true`, par défaut)** :
  Le montant transféré est automatiquement converti dans la devise du portefeuille destinataire selon le taux de change actif. La transaction enregistre à la fois `amount` (devise source), `converted_amount` (devise cible) et `exchange_rate`.
- **Option B — Blocage strict (`FUNDRY_ALLOW_CROSS_CURRENCY_TRANSFERS=false`)** :
  Les transferts ne sont autorisés qu'entre portefeuilles de **même devise**. Toute tentative de transfert inter-devises lève immédiatement une `InvalidCurrencyException`.

> 💡 **Surcharge par opération :** Vous pouvez également forcer l'Option A ou l'Option B sur un transfert spécifique via le paramètre `allowCrossCurrency: true|false` dans `TransferDTO` !

---

## 📖 Guide d'utilisation

### 1. Créer un portefeuille

```php
use Hamadou\Fundry\Facades\Fundry;
use Hamadou\Fundry\Enums\WalletType;
use Hamadou\Fundry\Models\Currency;

$usd = Currency::findByIsoCode('USD');

$wallet = Fundry::createWallet($user, [
    'currency_id'       => $usd->id,
    'name'              => 'Portefeuille Principal USD',
    'type'              => WalletType::PERSONAL,
    'balance'           => 0,
    'max_balance'       => 100000.00,
    'min_balance'       => 10.00,
    'transaction_limit' => 5000.00,
    'is_default'        => true,
]);
```

### 2. Effectuer un dépôt (Simple ou avec DTO & Commission)

```php
use Hamadou\Fundry\Facades\Fundry;
use Hamadou\Fundry\DTOs\DepositDTO;

// Dépôt simple sans frais
$tx = Fundry::deposit($wallet, 1000.00, 'Rechargement par carte');

// Dépôt avec DTO et commission de 2.5%
$dto = new DepositDTO(
    userId: $user->id,
    walletId: $wallet->id,
    amount: 1000.00,
    description: 'Paiement client #INV-2026',
    commissionPercentage: 2.5,
    metadata: ['gateway' => 'stripe', 'order_id' => 42]
);

$tx = Fundry::depositWithDTO($dto);
// Montant brut : 1000.00 | Commission (2.5%) : 25.00 | Crédité sur le wallet : 975.00
```

### 3. Effectuer un retrait (Simple ou avec DTO & Commission)

```php
use Hamadou\Fundry\DTOs\WithdrawalDTO;

$dto = new WithdrawalDTO(
    userId: $user->id,
    walletId: $wallet->id,
    amount: 500.00,              // Montant net demandé
    description: 'Retrait vers compte bancaire',
    commissionPercentage: 1.0    // 1% de frais (5.00)
);

$tx = Fundry::withdrawWithDTO($dto);
// Montant net retiré : 500.00 | Commission : 5.00 | Total débité du wallet : 505.00
```

### 4. Transfert entre portefeuilles (Même devise ou Inter-devises)

Dans Fundry, **les frais de transfert sont à la charge de l'expéditeur** (`Total Débité = Montant + Commission`), garantissant que le destinataire reçoit exactement le montant annoncé :

```php
use Hamadou\Fundry\DTOs\TransferDTO;

$dto = new TransferDTO(
    userId: $sender->id,
    fromWalletId: $senderWallet->id,
    toWalletId: $receiverWallet->id,
    amount: 1000.00,
    description: 'Virement P2P',
    commissionPercentage: 1.5,   // 1.5% de frais à la charge de l'expéditeur
    allowCrossCurrency: true     // Optionnel : force l'Option A (true) ou l'Option B (false)
);

$tx = Fundry::transferWithDTO($dto);
// Débité chez l'expéditeur : 1015.00 (1000 + 15 de commission)
// Crédité chez le destinataire : 1000.00 (ou montant converti si devises différentes)
```

### 5. Méthodes du trait `HasWallets` sur le modèle `User`

```php
$defaultWallet = $user->getDefaultWallet();
$eurWallet     = $user->getWalletByCurrency('EUR');
$eurBalance    = $user->getWalletBalance('EUR');
$totalInUsd    = $user->getTotalBalanceInUsd();

if ($user->hasSufficientBalance(250.00, 'EUR')) {
    // L'utilisateur peut retirer 250 EUR en respectant min_balance et transaction_limit
}
```

### 6. Helpers globaux

```php
$fundryInstance = fundry();
echo format_currency(1250.5, 'EUR'); // "1,250.50 EUR"
$allowed = can_withdraw($wallet, 100.00);
```

---

## 🛠️ Commandes Artisan (CLI)

| Commande | Description | Exemples d'options |
|---|---|---|
| `php artisan fundry:install` | Publie la config, les migrations et initialise optionnellement les seeders | `--force`, `--seed` |
| `php artisan fundry:sync-countries` | Synchronise le référentiel des pays ISO 3166-1 | `--force` |
| `php artisan fundry:currencies {action}` | Liste (`list`), initialise (`seed`/`sync`) ou vide le cache (`clear-cache`) des devises | `--type=fiat\|crypto\|device` |
| `php artisan fundry:validate-currencies` | Audite l'intégrité des devises et corrige automatiquement les anomalies | `--fix` |
| `php artisan fundry:update-rates` | Met à jour les taux de change (mode `manual` ou `exchangerate-api`) | `--provider=exchangerate-api --api-key=XXX --base=USD` |
| `php artisan fundry:cash {action}` | Affiche les statistiques (`stats`) ou la liste (`list`) des devises fiduciaires | `stats`, `list` |
| `php artisan fundry:crypto {action}` | Affiche la liste (`list`) ou les statistiques (`stats`) des cryptomonnaies | `list`, `stats` |
| `php artisan fundry:report {type}` | Génère un rapport PDF ou Excel (`transactions`, `wallets`, `currencies`, `limits`) | `--format=pdf\|excel --start-date=2026-01-01 --currency=USD` |

---

## 🔐 Sécurité & Concurrence

- **Prévention des Deadlocks SQL** : Lors d'un transfert entre deux portefeuilles, `WalletService` verrouille systématiquement les lignes par ordre croissant d'identifiant (`min($fromId, $toId)` puis `max($fromId, $toId)`), éliminant tout risque d'interblocage lors de transferts croisés simultanés.
- **Isolation et contrôle d'accès** : Toute opération vérifie que le portefeuille appartient bien à l'utilisateur émetteur (`UnauthorizedWalletException` — HTTP `403`) et que les portefeuilles impliqués sont actifs.
- **Exceptions typées** :
  - `Hamadou\Fundry\Exceptions\InsufficientFundsException`
  - `Hamadou\Fundry\Exceptions\InvalidAmountException`
  - `Hamadou\Fundry\Exceptions\InvalidCurrencyException`
  - `Hamadou\Fundry\Exceptions\UnauthorizedWalletException`
  - `Hamadou\Fundry\Exceptions\ConcurrencyException`

---

## 🧪 Tests & Qualité

Le package dispose d'une suite complète de tests unitaires et fonctionnels sous **PHPUnit** et **Orchestra Testbench** :

```bash
# Exécuter l'ensemble de la suite de tests
vendor/bin/phpunit

# Exécuter avec rapport de couverture HTML
vendor/bin/phpunit --coverage-html coverage
```

---

## 🤝 Contribution & Licence

Les contributions (Issues et Pull Requests) sont les bienvenues ! Ce projet est distribué sous licence **MIT**. Consultez le fichier [LICENSE](LICENSE) pour plus d'informations.

## 👤 Auteur

**Hamadou Tall**
- 📧 Email : [tallhamadou30@gmail.com](mailto:tallhamadou30@gmail.com)
- 🐙 GitHub : [@hamadou](https://github.com/hamadou)

---

### 🏷️ Hashtags & Mots-clés

`#Laravel` `#PHP` `#Fundry` `#Fintech` `#EWallet` `#VirtualWallet` `#MultiCurrency` `#PaymentGateway` `#CryptoWallet` `#FiatCurrency` `#BCMath` `#FinancialLedger` `#LaravelPackage` `#OpenSource` `#ECommerce` `#ISO4217` `#Transactions` `#MobileMoney` `#BankingSystem` `#Laravel12` `#Laravel13`
