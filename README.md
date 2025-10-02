##Fundry

Fundry est un package Laravel destiné à simplifier la gestion de portefeuilles virtuels, de transactions et de devises. Il fournit des services et des commandes Artisan pour gérer les portefeuilles de type cash ou crypto, et permet d’exporter les données financières en PDF ou Excel. Après installation, vous disposerez notamment de modèles Eloquent (Wallet, Transaction, Currency) pour gérer vos utilisateurs et leurs portefeuilles, ainsi que de limites par défaut configurables (soldes max, plafonds journaliers, etc.) via le fichier de configuration config/fundry.php.
Installation

Suivez ces étapes pour installer Fundry dans votre projet Laravel :
 Ajouter le package via Composer et charger le provider :

composer require hamadou/fundry

Publier les fichiers de configuration et de migration fournis par Fundry. Utilisez les tags fundry-config et fundry-migrations de votre FundryServiceProvider :

php artisan vendor:publish \
  --provider="Hamadou\Fundry\Providers\FundryServiceProvider" \
  --tag="fundry-config"

php artisan vendor:publish \
  --provider="Hamadou\Fundry\Providers\FundryServiceProvider" \
  --tag="fundry-migrations"

Ces commandes copient les fichiers fundry.php de configuration et les migrations nécessaires dans votre projet. La publication de fichiers de configuration et de migrations via des tags se fait de cette façon
laravel.com
laravel.com
.

Exécuter les migrations :

    php artisan migrate

    Cela crée les tables wallets, transactions, currencies, etc., dans votre base de données.

    (Optionnel) Configurer Fundry : Ouvrez config/fundry.php pour ajuster les paramètres par défaut. Vous pourrez y définir les types de portefeuilles (cash et crypto par défaut), les limites financières (par exemple max_balance, daily_limit, etc.) ainsi que les paramètres de génération de rapports (format par défaut, emplacement de stockage, options PDF, etc.). Modifiez ces valeurs selon vos besoins avant de commencer à utiliser le package.

Commandes Artisan fournies

Fundry fournit plusieurs commandes Artisan pour gérer les portefeuilles et générer des rapports :

    fundry:install – Installe Fundry : cette commande (à lancer une seule fois après le composer require) peut publier automatiquement la configuration et les migrations si elle est implémentée (dans l’exemple actuel elle affiche un message d’information). En pratique, vous pouvez exécuter manuellement les vendor:publish listées ci-dessus avant ou après composer require.

    fundry:currencies {action?} – Gère les devises prises en charge par Fundry. Par exemple, vous pouvez prévoir les actions suivantes (selon votre implémentation) :

        php artisan fundry:currencies list – Liste les devises existantes dans la base (code, nom, type).

        php artisan fundry:currencies sync – Synchronise le cours des devises (obtenir des taux actuels depuis une API externe).

        php artisan fundry:currencies seed – Importe ou met à jour les devises de base (remplit la table currencies avec des valeurs prédéfinies).

    fundry:cash {action?} – Opérations liées aux portefeuilles de type cash. Par exemple :

        php artisan fundry:cash report – Génère un rapport sur les portefeuilles cash (sous forme de PDF ou Excel).

        php artisan fundry:cash cleanup – Nettoie les données de tests (par ex. supprime les portefeuilles factices).

        D’autres actions personnalisées peuvent être ajoutées selon les besoins (le stub actuel affiche juste un message d’info).

    fundry:crypto {action?} – Opérations liées aux portefeuilles de type crypto. Par exemple :

        php artisan fundry:crypto sync-rates – Récupère et met à jour les cours des cryptomonnaies (via une API externe).

        php artisan fundry:crypto import – Importe des transactions ou soldes de crypto depuis un fichier CSV ou une source externe.

        Comme pour fundry:cash, ce commandement est extensible : dans l’exemple actuel il affiche un message par défaut.

    fundry:report {type} – Génère un rapport financier sous forme de PDF ou Excel. Cette commande prend plusieurs options :
    Option	Description
    type	Type de rapport : transactions, wallets, currencies ou limits (obligatoire).
    --format=	Format de sortie : pdf (par défaut) ou excel.
    --start-date=	Date de début de la plage (format YYYY-MM-DD, filtre les transactions).
    --end-date=	Date de fin de la plage (YYYY-MM-DD).
    --user-id=	Filtrer par identifiant d’utilisateur (pour transactions ou wallets).
    --wallet-type=	Filtrer par type de portefeuille (cash ou crypto, pour wallets).
    --currency=	Filtrer par code devise (ISO) pour wallets ou transactions.
    --output=	Chemin/fichier de sortie (optionnel, sinon utilise un nom par défaut).

Par exemple, pour générer un rapport PDF des transactions d’un utilisateur entre deux dates :

    php artisan fundry:report transactions \
      --format=pdf \
      --start-date=2024-01-01 \
      --end-date=2024-12-31 \
      --user-id=5 \
      --output=rapport_transactions_2024.pdf

    Cette commande va extraire les données demandées, générer le rapport et l’enregistrer. Fundry utilise pour cela [Laravel Excel] pour les fichiers .xlsx et [Dompdf] pour les .pdf. Par exemple, elle appelle Excel::store(new InvoicesExport(...), 'file.xlsx') pour sauvegarder l’export Excel
    docs.laravel-excel.com
    et Pdf::loadView(...)->save('file.pdf') pour produire le PDF
    github.com
    . Ces bibliothèques permettent de manipuler facilement les vues Blade comme base des rapports et de spécifier les options (taille de page, orientation, etc.).

Exemples d’utilisation

Voici quelques exemples de commandes et d’usages typiques avec Fundry :

    Publier les configurations et effectuer les migrations (installation) :

php artisan fundry:install
php artisan migrate

Gérer les devises :

php artisan fundry:currencies list
php artisan fundry:currencies sync
php artisan fundry:currencies seed

Rapport de portefeuilles (tous utilisateurs, type cash) :

php artisan fundry:report wallets \
  --format=excel \
  --wallet-type=cash \
  --output=mes_wallets.xlsx

Rapport des limites de Fundry (pas de filtre) :

php artisan fundry:report limits --format=pdf --output=limites_fundry.pdf

Création d’un portefeuille (exemple) : vous pouvez utiliser le modèle Eloquent Wallet pour créer un compte utilisateur. Par exemple :

    use Hamadou\Fundry\Models\Wallet;
    use Hamadou\Fundry\Models\Currency;

    // Récupère la devise par code (ex: USD)
    $currency = Currency::where('code', 'USD')->first();

    // Crée un portefeuille cash pour l'utilisateur courant
    Wallet::create([
        'user_id'    => auth()->id(),
        'type'       => 'cash',
        'name'       => 'Mon Portefeuille Cash',
        'currency_id'=> $currency->id,
        'balance'    => 1000.00,
    ]);

    Cet exemple crée un portefeuille de type cash en dollars pour l’utilisateur authentifié. De même, vous pouvez créer des transactions en utilisant le modèle Transaction (en passant les clés étrangères appropriées pour from_wallet_id et to_wallet_id, etc.).

Fundry est conçu pour être simple à étendre : vous pouvez ajouter vos propres méthodes de synchronisation ou de génération de rapports en complétant les commandes Artisan fournies. Pour en savoir plus sur la gestion des commandes personnalisées, consultez la documentation Laravel sur les Artisan commands
github.com
docs.laravel-excel.com
.

Sources : Package Fundry (code source du provider et des commandes), documentation Laravel sur la publication de fichiers (vendor:publish)
laravel.com
laravel.com
, documentation de Laravel Excel et Dompdf pour la génération d’exports
docs.laravel-excel.com
github.com
.
Citations

Package Development - Laravel 12.x - The PHP Framework For Web Artisans
https://laravel.com/docs/12.x/packages

Package Development - Laravel 12.x - The PHP Framework For Web Artisans
https://laravel.com/docs/12.x/packages

Storing exports on disk | Laravel Excel
https://docs.laravel-excel.com/3.1/exports/store.html

GitHub - barryvdh/laravel-dompdf: A DOMPDF Wrapper for Laravel
https://github.com/barryvdh/laravel-dompdf

GitHub - barryvdh/laravel-dompdf: A DOMPDF Wrapper for Laravel
https://github.com/barryvdh/laravel-dompdf
Toutes les sources
laravel
docs.laravel-excel
github
