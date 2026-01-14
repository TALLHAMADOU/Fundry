# Fundry

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hamadou/fundry.svg?style=flat-square)](https://packagist.org/packages/hamadou/fundry)
[![Total Downloads](https://img.shields.io/packagist/dt/hamadou/fundry.svg?style=flat-square)](https://packagist.org/packages/hamadou/fundry)
[![License](https://img.shields.io/packagist/l/hamadou/fundry.svg?style=flat-square)](LICENSE)

**Fundry** est un package Laravel complet et robuste pour la gestion de portefeuilles virtuels, de transactions financières et de devises multiples. Conçu pour les applications e-commerce, plateformes de paiement, systèmes de gestion financière et applications nécessitant une gestion monétaire fiable.

## 📋 Table des matières

1. [À propos du projet](#à-propos-du-projet)
2. [Fonctionnalités](#fonctionnalités)
3. [Architecture](#architecture)
4. [Prérequis](#prérequis)
5. [Installation](#installation)
6. [Configuration](#configuration)
7. [Concepts clés](#concepts-clés)
8. [Guide d'utilisation](#guide-dutilisation)
9. [Commandes Artisan](#commandes-artisan)
10. [API Reference](#api-reference)
11. [Sécurité](#sécurité)
12. [Performance et Cache](#performance-et-cache)
13. [Gestion des erreurs](#gestion-des-erreurs)
14. [Exemples complets](#exemples-complets)
15. [Tests](#tests)
16. [Dépannage](#dépannage)
17. [Contribution](#contribution)
18. [Licence](#licence)

---

## 🎯 À propos du projet

Fundry résout le problème complexe de la gestion financière dans les applications Laravel en fournissant un système de portefeuille simple, élégant et sécurisé. Que vous construisiez une plateforme e-commerce, une passerelle de paiement, ou une application de suivi financier, Fundry offre les outils nécessaires pour une gestion monétaire robuste.

### Capacités principales

- ✅ **Gestion multi-devises** - Support complet des devises fiat et cryptomonnaies
- ✅ **Transactions sécurisées** - Verrous de base de données pour éviter les race conditions
- ✅ **Système de commissions** - Gestion flexible des frais de transaction
- ✅ **Validation ISO 4217** - Codes de devise standardisés et validés
- ✅ **Liaison Pays-Devises** - Association automatique des devises aux pays
- ✅ **Historique complet** - Traçabilité totale des transactions
- ✅ **Export de rapports** - Génération PDF et Excel
- ✅ **Limites configurables** - Contrôles de sécurité personnalisables
- ✅ **Cache intégré** - Optimisation des performances
- ✅ **Commandes CLI** - Outils de gestion puissants

---

## ✨ Fonctionnalités

### 💰 Gestion de portefeuilles

- Création et gestion de portefeuilles multiples par utilisateur
- Types de portefeuilles : Personnel, Business, Épargne, Investissement, Gouvernemental
- Limites configurables (solde max/min, limite de transaction)
- Portefeuilles par défaut
- Statut actif/inactif
- Règles de sécurité personnalisables

### 💱 Support multi-devises

- Devises fiat (EUR, USD, XOF, etc.)
- Cryptomonnaies (BTC, ETH, USDT, etc.)
- Devises gouvernementales (device)
- Taux de change configurables
- Conversion automatique entre devises
- Validation ISO 4217 pour les devises fiat
- Liaison avec les pays (ISO 3166-1)

### 📊 Gestion des transactions

- Types de transactions : Dépôt, Retrait, Transfert, Échange, Frais, Remboursement
- Statuts : En attente, Complétée, Échouée
- Références uniques pour chaque transaction
- Métadonnées personnalisables
- Historique complet avec filtres
- Calcul de volumes quotidiens

### 🔐 Sécurité

- Vérification d'autorisation (wallet appartient à l'utilisateur)
- Verrous de base de données (lockForUpdate) pour la concurrence
- Validation stricte des montants
- Protection contre les montants négatifs
- Validation de précision décimale
- Exceptions explicites et sécurisées

### 📄 Exports et rapports

- Génération de rapports PDF
- Export Excel
- Filtres avancés (date, utilisateur, devise, type)
- Rapports sur transactions, portefeuilles, devises, limites

---

## 🏗️ Architecture

### Structure du package

```
Fundry/
├── src/
│   ├── Models/              # Modèles Eloquent
│   │   ├── Wallet.php      # Portefeuille
│   │   ├── Transaction.php # Transaction
│   │   ├── Currency.php    # Devise
│   │   └── Country.php     # Pays
│   ├── Services/           # Services métier
│   │   ├── WalletService.php
│   │   ├── TransactionService.php
│   │   └── CurrencyService.php
│   ├── DTOs/               # Data Transfer Objects
│   │   ├── DepositDTO.php
│   │   ├── WithdrawalDTO.php
│   │   └── TransferDTO.php
│   ├── Enums/              # Énumérations
│   │   ├── WalletType.php
│   │   ├── TransactionType.php
│   │   ├── TransactionStatus.php
│   │   └── CurrencyType.php
│   ├── Exceptions/         # Exceptions personnalisées
│   ├── Events/             # Événements Laravel
│   ├── Contracts/          # Interfaces
│   └── Console/Commands/   # Commandes Artisan
├── database/migrations/    # Migrations
├── Seeders/                # Seeders
└── tests/                  # Tests unitaires et fonctionnels
```

### Flux de données

```
Utilisateur → Facade Fundry → Service → Modèle → Base de données
                ↓
            DTO (validation)
                ↓
            Verrous DB
                ↓
            Transaction DB
                ↓
            Événements
```

---

## 📦 Prérequis

- **PHP** : 8.1 ou supérieur
- **Laravel** : 10.x, 11.x, ou 12.x
- **Extensions PHP** :
  - `bcmath` (calculs monétaires précis)
  - `json` (métadonnées)
- **Base de données** : MySQL, PostgreSQL, SQLite, SQL Server
- **Table `users`** : Le package utilise la table `users` standard de Laravel. Aucune modification de structure n'est nécessaire. Il suffit d'ajouter le trait `HasWallets` au modèle `User`.

---

## 🚀 Installation

### Étape 1 : Installation via Composer

```bash
composer require hamadou/fundry
```

### Étape 2 : Publier les migrations et la configuration

```bash
# Publier les migrations
php artisan vendor:publish --tag=fundry-migrations

# Publier la configuration
php artisan vendor:publish --tag=fundry-config
```

### Étape 3 : Configurer le modèle User

Le package utilise la table `users` existante de votre projet Laravel. Aucune modification de la structure de la table n'est nécessaire. Il suffit d'ajouter le trait `HasWallets` à votre modèle `User` :

```php
// app/Models/User.php
use Hamadou\Fundry\Traits\HasWallets;

class User extends Authenticatable
{
    use HasWallets;
    
    // ... vos autres traits et méthodes
}
```

**Note importante :** Le package utilise uniquement la colonne `id` de la table `users`. Aucun champ supplémentaire n'est requis dans la migration `users`. La table `users` standard de Laravel est suffisante.

### Étape 4 : Exécuter les migrations

```bash
php artisan migrate
```

Cela créera les tables suivantes :
- `countries` - Pays et leurs devises
- `currencies` - Devises (fiat, crypto, device)
- `wallets` - Portefeuilles utilisateurs (avec foreign key vers `users.id`)
- `transactions` - Historique des transactions (avec foreign key vers `users.id`)

**Important :** Assurez-vous que votre table `users` existe avant d'exécuter les migrations, car les tables `wallets` et `transactions` ont des contraintes de clé étrangère vers `users.id`.

### Étape 5 : Synchroniser les données de base

```bash
# Synchroniser les pays
php artisan fundry:sync-countries

# Seeder les devises principales
php artisan db:seed --class="Hamadou\Fundry\Seeders\CurrencySeeder"
```

---

## ⚙️ Configuration

### Fichier de configuration

Le fichier `config/fundry.php` contient toutes les options de configuration :

```php
return [
    // Devise par défaut
    'default_currency' => env('FUNDRY_DEFAULT_CURRENCY', 'USD'),
    
    // Précision décimale
    'precision' => env('FUNDRY_PRECISION', 8),
    
    // Utiliser le stockage en cents (non recommandé)
    'use_cents_storage' => env('FUNDRY_USE_CENTS', false),
    
    // Limites par défaut
    'limits' => [
        'daily_deposit' => env('FUNDRY_DAILY_DEPOSIT_LIMIT', 10000),
        'daily_withdrawal' => env('FUNDRY_DAILY_WITHDRAWAL_LIMIT', 10000),
        'daily_transfer' => env('FUNDRY_DAILY_TRANSFER_LIMIT', 50000),
    ],
    
    // Provider de taux de change
    'exchange_rate_provider' => env('FUNDRY_EXCHANGE_RATE_PROVIDER', 'manual'),
    'exchange_rate_api_key' => env('FUNDRY_EXCHANGE_RATE_API_KEY', null),
    
    // Cache
    'cache' => [
        'enabled' => env('FUNDRY_CACHE_ENABLED', true),
        'ttl' => env('FUNDRY_CACHE_TTL', 3600), // 1 heure
        'prefix' => 'fundry',
    ],
    
    // Exports
    'exports' => [
        'pdf' => [
            'orientation' => 'portrait',
            'paper' => 'a4',
        ],
        'excel' => [
            'format' => 'xlsx',
        ],
    ],
];
```

### Variables d'environnement (.env)

```env
# Devise par défaut
FUNDRY_DEFAULT_CURRENCY=USD

# Cache
FUNDRY_CACHE_ENABLED=true
FUNDRY_CACHE_TTL=3600

# Taux de change
FUNDRY_EXCHANGE_RATE_PROVIDER=manual
FUNDRY_EXCHANGE_RATE_API_KEY=your_api_key_here

# Limites
FUNDRY_DAILY_DEPOSIT_LIMIT=10000
FUNDRY_DAILY_WITHDRAWAL_LIMIT=10000
FUNDRY_DAILY_TRANSFER_LIMIT=50000
```

---

## 🧠 Concepts clés

### Portefeuille (Wallet)

Un portefeuille est un compte virtuel associé à un utilisateur et une devise. Chaque utilisateur peut avoir plusieurs portefeuilles.

**Propriétés principales :**
- `balance` : Solde actuel
- `max_balance` : Solde maximum autorisé
- `min_balance` : Solde minimum à maintenir
- `transaction_limit` : Limite par transaction
- `type` : Type de portefeuille (personnel, business, etc.)
- `is_active` : Statut actif/inactif
- `is_default` : Portefeuille par défaut

### Transaction

Une transaction représente une opération financière (dépôt, retrait, transfert).

**Types de transactions :**
- `DEPOSIT` : Dépôt de fonds
- `WITHDRAWAL` : Retrait de fonds
- `TRANSFER` : Transfert entre portefeuilles
- `EXCHANGE` : Échange de devises
- `FEE` : Frais de transaction
- `REFUND` : Remboursement

**Statuts :**
- `PENDING` : En attente
- `COMPLETED` : Complétée
- `FAILED` : Échouée

### Devise (Currency)

Une devise représente une unité monétaire (fiat, crypto, ou device).

**Types de devises :**
- `FIAT` : Monnaie fiduciaire (USD, EUR, XOF, etc.)
- `CRYPTO` : Cryptomonnaie (BTC, ETH, etc.)
- `DEVICE` : Devise gouvernementale

**Propriétés :**
- `iso_code` : Code ISO 4217 (3 lettres)
- `exchange_rate` : Taux de change par rapport à la devise de base
- `decimals` : Nombre de décimales
- `country_id` : Pays associé (pour fiat)

### Pays (Country)

Un pays contient les informations géographiques et sa devise officielle.

**Propriétés :**
- `iso_code` : Code ISO 3166-1 alpha-2 (2 lettres)
- `currency_code` : Code devise ISO 4217
- `continent` : Continent
- `capital` : Capitale

### DTO (Data Transfer Object)

Les DTOs encapsulent les données des transactions avec validation intégrée.

**Avantages :**
- Validation automatique
- Calcul des commissions
- Type-safe
- Réutilisable

---

## 📖 Guide d'utilisation

### Utilisation de la Facade

```php
use Hamadou\Fundry\Facades\Fundry;
```

### Création d'un portefeuille

```php
use Hamadou\Fundry\Facades\Fundry;
use Hamadou\Fundry\Enums\WalletType;
use Hamadou\Fundry\Models\Currency;

// Récupérer une devise
$usd = Currency::findByIsoCode('USD');

// Créer un portefeuille
$wallet = Fundry::createWallet($user, [
    'currency_id' => $usd->id,
    'name' => 'Mon Portefeuille Principal',
    'type' => WalletType::PERSONAL,
    'balance' => 0,
    'max_balance' => 100000.00,
    'min_balance' => 0,
    'transaction_limit' => 5000.00,
    'is_default' => true,
]);
```

### Dépôt de fonds

#### Méthode simple

```php
$transaction = Fundry::deposit($wallet, 1000.00, 'Dépôt initial');
```

#### Méthode avec DTO (recommandée)

```php
use Hamadou\Fundry\DTOs\DepositDTO;

$dto = new DepositDTO(
    userId: $user->id,
    walletId: $wallet->id,
    amount: 1000.00,
    description: 'Dépôt initial',
    commissionPercentage: 2.5 // 2.5% de commission
);

$transaction = Fundry::depositWithDTO($dto);

// Le montant net déposé sera 975.00 (1000 - 2.5%)
// La commission sera 25.00
```

#### Créer un DTO depuis un tableau

```php
$dto = DepositDTO::fromArray([
    'user_id' => $user->id,
    'wallet_id' => $wallet->id,
    'amount' => 1000.00,
    'description' => 'Dépôt',
    'commission_percentage' => 2.5,
    'metadata' => [
        'source' => 'bank_transfer',
        'reference' => 'BANK123456',
    ],
]);
```

### Retrait de fonds

#### Méthode simple

```php
$transaction = Fundry::withdraw($wallet, 500.00, 'Retrait');
```

#### Méthode avec DTO

```php
use Hamadou\Fundry\DTOs\WithdrawalDTO;

$dto = new WithdrawalDTO(
    userId: $user->id,
    walletId: $wallet->id,
    amount: 500.00, // Montant net retiré
    description: 'Retrait',
    commissionPercentage: 1.0 // 1% de commission
);

$transaction = Fundry::withdrawWithDTO($dto);

// Le montant total débité sera 505.00 (500 + 1%)
// La commission sera 5.00
```

### Transfert entre portefeuilles

#### Méthode simple

```php
$transaction = Fundry::transfer($wallet1, $wallet2, 1000.00, 'Transfert');
```

#### Méthode avec DTO

```php
use Hamadou\Fundry\DTOs\TransferDTO;

$dto = new TransferDTO(
    userId: $user->id,
    fromWalletId: $wallet1->id,
    toWalletId: $wallet2->id,
    amount: 1000.00,
    description: 'Transfert entre comptes',
    commissionPercentage: 1.5 // 1.5% de commission
);

$transaction = Fundry::transferWithDTO($dto);

// 1015.00 sera débité du wallet source
// 985.00 sera crédité au wallet destination
// 15.00 sera la commission
```

### Consultation du solde

```php
// Solde d'un portefeuille
$balance = Fundry::getWalletBalance($wallet);

// Historique des transactions
$history = Fundry::getWalletHistory($wallet, 50); // 50 dernières transactions

// Transactions d'un utilisateur
$transactions = Fundry::getUserTransactions($user->id, [
    'type' => 'deposit',
    'status' => 'completed',
    'start_date' => '2024-01-01',
    'end_date' => '2024-12-31',
], 100);
```

### Conversion de devises

```php
// Conversion simple
$amount = Fundry::convertAmount(100, 'USD', 'EUR');

// Conversion sécurisée avec validation
use Hamadou\Fundry\Models\Currency;

$usd = Currency::findByIsoCode('USD');
$eur = Currency::findByIsoCode('EUR');

try {
    $amount = $usd->convertToSafe(100, $eur);
    // Conversion réussie
} catch (\Hamadou\Fundry\Exceptions\InvalidCurrencyException $e) {
    // Gérer l'erreur
}
```

### Utilisation du trait HasWallets

Le trait `HasWallets` ajoute des méthodes pratiques au modèle User :

```php
// Créer un portefeuille
$wallet = $user->createWallet([
    'currency_id' => $currency->id,
    'name' => 'Mon Wallet',
    'type' => WalletType::PERSONAL,
]);

// Récupérer un portefeuille par devise
$usdWallet = $user->getWalletByCurrency('USD');

// Portefeuille par défaut
$defaultWallet = $user->getDefaultWallet();

// Solde dans une devise
$balance = $user->getWalletBalance('USD');

// Solde total en USD
$totalUsd = $user->getTotalBalanceInUsd();

// Vérifier si le solde est suffisant
if ($user->hasSufficientBalance(500, 'USD')) {
    // Le solde est suffisant
}

// Portefeuilles par type
$businessWallets = $user->getWalletsByType(WalletType::BUSINESS);
```

### Gestion des pays et devises

#### Créer un pays

```php
use Hamadou\Fundry\Models\Country;

$senegal = Country::create([
    'name' => 'Sénégal',
    'name_en' => 'Senegal',
    'iso_code' => 'SN',
    'iso_code_3' => 'SEN',
    'numeric_code' => '686',
    'phone_code' => '+221',
    'continent' => 'Africa',
    'capital' => 'Dakar',
    'currency_code' => 'XOF',
    'currency_name' => 'West African CFA Franc',
    'currency_symbol' => 'CFA',
]);
```

#### Créer une devise liée à un pays

```php
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Enums\CurrencyType;

$senegal = Country::findByIsoCode('SN');

$xof = Currency::create([
    'iso_code' => 'XOF',
    'code' => 'XOF',
    'name' => 'Franc CFA',
    'country_id' => $senegal->id,
    'type' => CurrencyType::FIAT,
    'symbol' => 'CFA',
    'exchange_rate' => 0.0017,
    'decimals' => 0,
]);
```

#### Rechercher une devise

```php
// Par code ISO 4217 (avec cache)
$usd = Currency::findByIsoCode('USD');

// Par pays
$france = Country::findByIsoCode('FR');
$euro = $france->currencies()->first();

// Par code devise du pays
$country = Country::where('currency_code', 'EUR')->first();
```

---

## 🛠️ Commandes Artisan

Fundry fournit plusieurs commandes Artisan pour faciliter la gestion du système.

### `fundry:install`

Installe et configure Fundry dans votre application.

```bash
php artisan fundry:install
```

**Actions effectuées :**
- Publie la configuration
- Publie les migrations
- Affiche les instructions de configuration

---

### `fundry:sync-countries`

Synchronise les pays et leurs devises depuis le seeder.

```bash
php artisan fundry:sync-countries
```

**Options :**
- `--force` : Force la mise à jour même si les pays existent déjà

**Exemple :**
```bash
php artisan fundry:sync-countries --force
```

**Ce que fait la commande :**
- Charge les pays depuis `CountrySeeder`
- Crée ou met à jour les pays dans la base de données
- Affiche le nombre total de pays synchronisés

---

### `fundry:validate-currencies`

Valide toutes les devises et détecte les problèmes potentiels.

```bash
php artisan fundry:validate-currencies
```

**Options :**
- `--fix` : Corrige automatiquement les erreurs détectées

**Exemple :**
```bash
# Validation simple
php artisan fundry:validate-currencies

# Validation avec correction automatique
php artisan fundry:validate-currencies --fix
```

**Vérifications effectuées :**
- ✅ Codes ISO 4217 valides pour les devises fiat
- ✅ Taux de change valides (> 0)
- ✅ Relations avec les pays
- ✅ Codes normalisés en majuscules
- ✅ Cohérence des données

**Sortie exemple :**
```
🔍 Validation des devises...

✅ Toutes les devises sont valides!
📊 Total de devises validées: 25
```

---

### `fundry:update-rates`

Met à jour les taux de change des devises.

```bash
php artisan fundry:update-rates
```

**Options :**
- `--provider=manual` : Provider à utiliser (manual, exchangerate-api)
- `--api-key=` : Clé API pour le provider externe
- `--base=USD` : Devise de base pour les taux

**Exemples :**

```bash
# Mode manuel (aucune mise à jour automatique)
php artisan fundry:update-rates

# Avec ExchangeRate-API
php artisan fundry:update-rates \
    --provider=exchangerate-api \
    --api-key=your_api_key_here \
    --base=USD
```

**Ce que fait la commande :**
- Récupère les taux de change depuis le provider
- Met à jour les devises dans la base de données
- Nettoie le cache automatiquement
- Affiche les taux mis à jour

**Sortie exemple :**
```
🔄 Mise à jour des taux de change (Provider: exchangerate-api, Base: USD)...
🌐 Récupération des taux depuis ExchangeRate-API...
✅ 25 taux de change mis à jour avec succès!

📊 Exemples de taux mis à jour:
  • EUR: 1.1
  • GBP: 1.27
  • XOF: 0.0017
  • MAD: 0.10
  • TND: 0.32
```

---

### `fundry:currencies`

Gère les devises (à compléter selon besoins).

```bash
php artisan fundry:currencies {action?}
```

**Actions possibles :**
- `list` : Liste toutes les devises
- `sync` : Synchronise les devises
- `seed` : Seed les devises de base

---

### `fundry:cash`

Opérations cash (à compléter selon besoins).

```bash
php artisan fundry:cash {action?}
```

---

### `fundry:crypto`

Opérations crypto (sync rates, import data).

```bash
php artisan fundry:crypto {action?}
```

---

### `fundry:report`

Génère des rapports PDF ou Excel pour les données Fundry.

```bash
php artisan fundry:report {type} [options]
```

**Types de rapports :**
- `transactions` : Rapport des transactions
- `wallets` : Rapport des portefeuilles
- `currencies` : Rapport des devises
- `limits` : Rapport des limites configurées

**Options :**
- `--format=pdf` : Format de sortie (pdf|excel)
- `--start-date=` : Date de début (YYYY-MM-DD)
- `--end-date=` : Date de fin (YYYY-MM-DD)
- `--user-id=` : Filtrer par utilisateur
- `--wallet-type=` : Type de portefeuille
- `--currency=` : Devise spécifique
- `--output=` : Fichier de sortie

**Exemples :**

```bash
# Rapport des transactions en PDF
php artisan fundry:report transactions --format=pdf

# Rapport des transactions en Excel avec filtres
php artisan fundry:report transactions \
    --format=excel \
    --start-date=2024-01-01 \
    --end-date=2024-12-31 \
    --user-id=1 \
    --currency=USD

# Rapport des portefeuilles
php artisan fundry:report wallets --format=pdf --wallet-type=personal

# Rapport des devises
php artisan fundry:report currencies --format=excel

# Rapport avec fichier de sortie personnalisé
php artisan fundry:report transactions \
    --format=pdf \
    --output=rapport_janvier_2024
```

**Emplacement des rapports :**
Les rapports sont sauvegardés dans `storage/app/reports/` par défaut.

---

## 📚 API Reference

### Facade Fundry

#### Méthodes de portefeuille

```php
// Créer un portefeuille
Fundry::createWallet($user, array $data): Wallet

// Obtenir le solde
Fundry::getWalletBalance(Wallet $wallet): float

// Historique des transactions
Fundry::getWalletHistory(Wallet $wallet, int $limit = 50): Collection

// Transfert simple
Fundry::transfer(Wallet $from, Wallet $to, float $amount, ?string $description): Transaction

// Transfert avec DTO
Fundry::transferWithDTO(TransferDTO $dto): Transaction
```

#### Méthodes de transaction

```php
// Dépôt simple
Fundry::deposit(Wallet $wallet, float $amount, ?string $description): Transaction

// Dépôt avec DTO
Fundry::depositWithDTO(DepositDTO $dto): Transaction

// Retrait simple
Fundry::withdraw(Wallet $wallet, float $amount, ?string $description): Transaction

// Retrait avec DTO
Fundry::withdrawWithDTO(WithdrawalDTO $dto): Transaction

// Obtenir une transaction par référence
Fundry::getTransactionByReference(string $reference): ?Transaction

// Transactions d'un utilisateur
Fundry::getUserTransactions($userId, array $filters = [], int $limit = 50): Collection

// Volume quotidien
Fundry::calculateDailyVolume($userId, string $currencyCode): float
```

#### Méthodes de devise

```php
// Créer une devise
Fundry::createCurrency(array $data): Currency

// Mettre à jour le taux de change
Fundry::updateExchangeRate(string $currencyCode, float $rate): bool

// Convertir un montant
Fundry::convertAmount(float $amount, string $fromCurrency, string $toCurrency): ?float

// Obtenir les devises supportées
Fundry::getSupportedCurrencies(): array
```

### Modèles

#### Wallet

```php
// Relations
$wallet->user()              // BelongsTo User
$wallet->currency()         // BelongsTo Currency
$wallet->fromTransactions() // HasMany Transaction
$wallet->toTransactions()   // HasMany Transaction

// Scopes
Wallet::active()            // Portefeuilles actifs
Wallet::default()           // Portefeuille par défaut
Wallet::byType($type)       // Par type
Wallet::hasBalance($amount) // Avec solde suffisant

// Méthodes
$wallet->canWithdraw($amount): bool
$wallet->deposit($amount): void
$wallet->withdraw($amount): void
$wallet->belongsToUser($user): bool
$wallet->getBalanceInUsd(): float
$wallet->getFormattedBalance(): string
```

#### Transaction

```php
// Relations
$transaction->user()        // BelongsTo User
$transaction->fromWallet() // BelongsTo Wallet
$transaction->toWallet()    // BelongsTo Wallet
$transaction->currency()    // BelongsTo Currency

// Scopes
Transaction::completed()    // Transactions complétées
Transaction::pending()      // Transactions en attente
Transaction::failed()       // Transactions échouées
Transaction::byType($type)  // Par type
Transaction::recent($days)  // Récentes

// Méthodes
$transaction->markAsCompleted(): void
$transaction->markAsFailed(?string $reason): void
$transaction->isPositive(): bool
$transaction->getFormattedAmount(): string
```

#### Currency

```php
// Relations
$currency->country()        // BelongsTo Country
$currency->wallets()        // HasMany Wallet
$currency->transactions()   // HasMany Transaction

// Scopes
Currency::fiat()            // Devises fiat
Currency::crypto()          // Cryptomonnaies
Currency::device()          // Devises device
Currency::active()           // Devises actives

// Méthodes statiques
Currency::isValidIso4217Code(string $code): bool
Currency::findByIsoCode(string $isoCode): ?Currency

// Méthodes d'instance
$currency->convertTo($amount, Currency $target): float
$currency->convertToSafe($amount, Currency $target): float
$currency->canConvertTo(Currency $target): bool
$currency->getValueInUsd($amount): float
$currency->getValueInEur($amount): float
$currency->getFormattedAmount($amount): string
```

#### Country

```php
// Relations
$country->currencies()      // HasMany Currency

// Scopes
Country::active()           // Pays actifs
Country::byIsoCode($code)   // Par code ISO
Country::byCurrencyCode($code) // Par code devise

// Méthodes statiques
Country::isValidIsoCode(string $code): bool
Country::isValidCurrencyCode(string $code): bool
Country::findByIsoCode(string $isoCode): ?Country
Country::findByCurrencyCode(string $currencyCode): ?Country
```

### DTOs

#### DepositDTO

```php
// Constructeur
new DepositDTO(
    userId: int|string,
    walletId: int,
    amount: float,
    description?: ?string,
    commissionPercentage?: ?float,
    metadata?: ?array
)

// Méthodes
$dto->getNetAmount(): float           // Montant net après commission
$dto->getCommissionAmount(): float     // Montant de la commission
$dto->toArray(): array                 // Convertir en tableau
DepositDTO::fromArray(array): DepositDTO // Créer depuis un tableau
```

#### WithdrawalDTO

```php
// Constructeur
new WithdrawalDTO(
    userId: int|string,
    walletId: int,
    amount: float,
    description?: ?string,
    commissionPercentage?: ?float,
    metadata?: ?array
)

// Méthodes
$dto->getTotalAmount(): float          // Montant total (montant + commission)
$dto->getCommissionAmount(): float     // Montant de la commission
$dto->toArray(): array
WithdrawalDTO::fromArray(array): WithdrawalDTO
```

#### TransferDTO

```php
// Constructeur
new TransferDTO(
    userId: int|string,
    fromWalletId: int,
    toWalletId: int,
    amount: float,
    description?: ?string,
    commissionPercentage?: ?float,
    metadata?: ?array
)

// Méthodes
$dto->getTotalAmount(): float          // Montant total débité
$dto->getNetAmount(): float            // Montant net crédité
$dto->getCommissionAmount(): float     // Montant de la commission
$dto->toArray(): array
TransferDTO::fromArray(array): TransferDTO
```

---

## 🔒 Sécurité

### Vérification d'autorisation

Toutes les opérations vérifient automatiquement que le wallet appartient à l'utilisateur :

```php
// Cette opération lancera une UnauthorizedWalletException
$dto = new DepositDTO(
    userId: $user1->id,
    walletId: $user2Wallet->id, // ❌ Erreur !
    amount: 100.00,
);
```

### Verrous de concurrence

Les opérations critiques utilisent des verrous de base de données (`lockForUpdate()`) pour éviter les race conditions :

```php
// Dans WalletService::deposit()
$wallet = Wallet::lockForUpdate()->findOrFail($walletId);
// ... opération sécurisée
```

### Validation des montants

- ✅ Montants doivent être > 0
- ✅ Précision limitée à 8 décimales
- ✅ Validation des limites (max_balance, min_balance, transaction_limit)

### Gestion des exceptions

```php
use Hamadou\Fundry\Exceptions\{
    InsufficientFundsException,      // Fonds insuffisants
    InvalidAmountException,          // Montant invalide
    UnauthorizedWalletException,    // Wallet n'appartient pas à l'utilisateur
    InvalidCurrencyException,        // Problème de devise
    ConcurrencyException             // Erreur de concurrence
};
```

---

## ⚡ Performance et Cache

### Configuration du cache

Le cache est activé par défaut pour améliorer les performances :

```php
// config/fundry.php
'cache' => [
    'enabled' => true,
    'ttl' => 3600, // 1 heure
    'prefix' => 'fundry',
],
```

### Ce qui est mis en cache

- ✅ Devises par type
- ✅ Liste des devises supportées
- ✅ Recherche de devises par code ISO
- ✅ Conversions de devises

### Nettoyer le cache

```php
use Hamadou\Fundry\Services\CurrencyService;

$currencyService = app(CurrencyService::class);
$currencyService->clearCache();
```

Le cache est automatiquement nettoyé lors de :
- Mise à jour d'une devise
- Synchronisation des taux de change
- Création/modification de devises

### Désactiver le cache

```env
FUNDRY_CACHE_ENABLED=false
```

---

## 🚨 Gestion des erreurs

### Exceptions personnalisées

```php
try {
    $transaction = Fundry::withdrawWithDTO($dto);
} catch (InsufficientFundsException $e) {
    // Fonds insuffisants ou limites dépassées
    return response()->json(['error' => $e->getMessage()], 400);
} catch (InvalidAmountException $e) {
    // Montant invalide (négatif, trop de décimales, etc.)
    return response()->json(['error' => $e->getMessage()], 400);
} catch (UnauthorizedWalletException $e) {
    // Wallet n'appartient pas à l'utilisateur
    return response()->json(['error' => $e->getMessage()], 403);
} catch (InvalidCurrencyException $e) {
    // Problème de devise (inactive, taux invalide, etc.)
    return response()->json(['error' => $e->getMessage()], 400);
} catch (ConcurrencyException $e) {
    // Erreur de concurrence
    return response()->json(['error' => $e->getMessage()], 409);
}
```

### Codes d'erreur HTTP

- `400` : Requête invalide (montant invalide, devise invalide)
- `403` : Non autorisé (wallet n'appartient pas à l'utilisateur)
- `409` : Conflit (erreur de concurrence)

---

## 💡 Exemples complets

### Exemple 1 : Système de paiement e-commerce

```php
use Hamadou\Fundry\Facades\Fundry;
use Hamadou\Fundry\DTOs\DepositDTO;
use Hamadou\Fundry\Enums\WalletType;

// 1. Créer un wallet pour le client
$wallet = Fundry::createWallet($customer, [
    'currency_id' => Currency::findByIsoCode('USD')->id,
    'name' => 'Portefeuille E-commerce',
    'type' => WalletType::PERSONAL,
]);

// 2. Traiter un paiement avec commission
$dto = new DepositDTO(
    userId: $customer->id,
    walletId: $wallet->id,
    amount: 150.00, // Montant de la commande
    description: "Paiement commande #{$orderId}",
    commissionPercentage: 2.9, // Commission de la plateforme
    metadata: [
        'order_id' => $orderId,
        'payment_method' => 'credit_card',
        'gateway' => 'stripe',
    ]
);

$transaction = Fundry::depositWithDTO($dto);

// 3. Vérifier le solde
$balance = Fundry::getWalletBalance($wallet);

// 4. Rembourser si nécessaire
if ($needsRefund) {
    $refundDto = new DepositDTO(
        userId: $customer->id,
        walletId: $wallet->id,
        amount: $transaction->amount,
        description: "Remboursement commande #{$orderId}",
        metadata: ['original_transaction_id' => $transaction->id]
    );
    Fundry::depositWithDTO($refundDto);
}
```

### Exemple 2 : Transfert entre utilisateurs

```php
use Hamadou\Fundry\DTOs\TransferDTO;

// Vérifier que l'expéditeur a suffisamment de fonds
if (!$sender->hasSufficientBalance(50, 'USD')) {
    return response()->json(['error' => 'Solde insuffisant'], 400);
}

// Effectuer le transfert avec commission
$dto = new TransferDTO(
    userId: $sender->id,
    fromWalletId: $senderWallet->id,
    toWalletId: $receiverWallet->id,
    amount: 50.00,
    description: "Paiement de {$sender->name} à {$receiver->name}",
    commissionPercentage: 1.0, // 1% de commission pour la plateforme
    metadata: [
        'payment_type' => 'peer_to_peer',
        'sender_email' => $sender->email,
        'receiver_email' => $receiver->email,
    ]
);

$transaction = Fundry::transferWithDTO($dto);

// Notifier les utilisateurs
event(new PaymentCompleted($transaction));
```

### Exemple 3 : Conversion multi-devises

```php
// Récupérer tous les wallets d'un utilisateur
$wallets = $user->wallets()->with('currency')->active()->get();

// Convertir tous les soldes en USD
$totalUsd = 0;
$usd = Currency::findByIsoCode('USD');

foreach ($wallets as $wallet) {
    if ($wallet->currency->iso_code === 'USD') {
        $totalUsd += $wallet->balance;
    } else {
        try {
            $converted = $wallet->currency->convertToSafe($wallet->balance, $usd);
            $totalUsd += $converted;
        } catch (InvalidCurrencyException $e) {
            \Log::warning("Impossible de convertir {$wallet->currency->iso_code}: {$e->getMessage()}");
        }
    }
}

return response()->json([
    'total_balance_usd' => round($totalUsd, 2),
    'wallets_count' => $wallets->count(),
]);
```

### Exemple 4 : Système de commissions dynamiques

```php
// Calculer la commission en fonction du montant
function calculateCommission(float $amount): float
{
    if ($amount < 100) {
        return 3.0; // 3% pour les petits montants
    } elseif ($amount < 1000) {
        return 2.5; // 2.5% pour les montants moyens
    } else {
        return 1.5; // 1.5% pour les gros montants
    }
}

$amount = 500.00;
$commission = calculateCommission($amount);

$dto = new DepositDTO(
    userId: $user->id,
    walletId: $wallet->id,
    amount: $amount,
    commissionPercentage: $commission,
    description: 'Dépôt avec commission dynamique'
);

$transaction = Fundry::depositWithDTO($dto);
```

### Exemple 5 : Vérification de limites quotidiennes

```php
use Hamadou\Fundry\Facades\Fundry;

// Vérifier le volume quotidien
$dailyVolume = Fundry::calculateDailyVolume($user->id, 'USD');
$dailyLimit = config('fundry.limits.daily_deposit', 10000);

if ($dailyVolume + $amount > $dailyLimit) {
    return response()->json([
        'error' => 'Limite quotidienne dépassée',
        'daily_limit' => $dailyLimit,
        'current_volume' => $dailyVolume,
        'remaining' => $dailyLimit - $dailyVolume,
    ], 400);
}

// Procéder au dépôt
$transaction = Fundry::deposit($wallet, $amount);
```

---

## 🧪 Tests

### Exécuter les tests

```bash
# Tous les tests
vendor/bin/phpunit

# Tests unitaires uniquement
vendor/bin/phpunit --testsuite=Unit

# Tests Feature uniquement
vendor/bin/phpunit --testsuite=Feature

# Avec couverture de code
vendor/bin/phpunit --coverage-html coverage
```

### Structure des tests

```
tests/
├── TestCase.php                    # Classe de base
├── Unit/
│   ├── Models/
│   │   ├── CurrencyTest.php
│   │   └── WalletTest.php
│   ├── DTOs/
│   │   ├── DepositDTOTest.php
│   │   ├── WithdrawalDTOTest.php
│   │   └── TransferDTOTest.php
│   └── Services/
│       ├── CurrencyServiceTest.php
│       ├── WalletServiceTest.php
│       └── TransactionServiceTest.php
└── Feature/
    └── WalletOperationsTest.php
```

---

## 🔧 Dépannage

### Problème : "Currency not found"

**Solution :**
```bash
# Vérifier que les devises sont bien seedées
php artisan fundry:validate-currencies

# Re-seeder si nécessaire
php artisan db:seed --class="Hamadou\Fundry\Seeders\CurrencySeeder"
```

### Problème : "Insufficient funds" même avec un solde suffisant

**Causes possibles :**
- Limite `min_balance` configurée
- Limite `transaction_limit` dépassée
- Commission non prise en compte dans le calcul

**Solution :**
```php
// Vérifier les limites du wallet
$wallet->min_balance;
$wallet->transaction_limit;
$wallet->max_balance;

// Vérifier si le retrait est possible
if ($wallet->canWithdraw($amount)) {
    // OK
}
```

### Problème : Cache non mis à jour

**Solution :**
```php
use Hamadou\Fundry\Services\CurrencyService;

$currencyService = app(CurrencyService::class);
$currencyService->clearCache();
```

### Problème : "UnauthorizedWalletException"

**Cause :** Le wallet n'appartient pas à l'utilisateur spécifié dans le DTO.

**Solution :**
```php
// Vérifier l'appartenance avant l'opération
if (!$wallet->belongsToUser($user)) {
    throw new UnauthorizedWalletException();
}
```

---

## 🤝 Contribution

Les contributions sont les bienvenues ! Pour contribuer :

1. Fork le projet
2. Créer une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Commit vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

### Standards de code

- Suivre les standards PSR-12
- Ajouter des tests pour les nouvelles fonctionnalités
- Documenter le code
- Mettre à jour le README si nécessaire

---

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 👤 Auteur

**Hamadou Tall**
- Email: tallhamadou30@gmail.com
- GitHub: [@hamadou](https://github.com/hamadou)

---

## 🙏 Remerciements

- Laravel Framework
- Orchestra Testbench pour les tests
- Maatwebsite Excel pour les exports
- DomPDF pour la génération PDF

---

## 📞 Support

Pour toute question ou problème :
- Ouvrir une [issue](https://github.com/hamadou/fundry/issues)
- Contacter l'auteur : tallhamadou30@gmail.com

---

**Fait avec ❤️ pour la communauté Laravel**
