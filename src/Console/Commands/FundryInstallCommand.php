<?php

namespace Hamadou\Fundry\Console\Commands;

use Illuminate\Console\Command;
use Hamadou\Fundry\Seeders\CountrySeeder;
use Hamadou\Fundry\Seeders\CurrencySeeder;

class FundryInstallCommand extends Command
{
    protected $signature = 'fundry:install
                            {--force : Écraser les fichiers de configuration et migrations existants}
                            {--seed : Initialiser les pays et devises de base en base de données}';

    protected $description = 'Installer et configurer Fundry (config, migrations, seeders)';

    public function handle(): int
    {
        $this->info('🚀 Installation de Fundry...');
        $force = (bool) $this->option('force');

        $this->call('vendor:publish', [
            '--tag' => 'fundry-config',
            '--force' => $force,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'fundry-migrations',
            '--force' => $force,
        ]);

        if ($this->option('seed')) {
            $this->info('🌱 Initialisation des pays et devises...');
            (new CountrySeeder())->run();
            (new CurrencySeeder())->run();
            $this->info('✅ Pays et devises initialisés avec succès.');
        }

        $this->newLine();
        $this->info('✅ Fundry a été installé avec succès !');
        $this->line('👉 Prochaines étapes :');
        $this->line('   1. Ajoutez le trait Hamadou\\Fundry\\Traits\\HasWallets à votre modèle User');
        $this->line('   2. Exécutez : php artisan migrate');
        $this->line('   3. Synchronisez les devises : php artisan fundry:currencies seed');

        return self::SUCCESS;
    }
}
