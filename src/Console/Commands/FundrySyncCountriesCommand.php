<?php

namespace Hamadou\Fundry\Console\Commands;

use Illuminate\Console\Command;
use Hamadou\Fundry\Seeders\CountrySeeder;

class FundrySyncCountriesCommand extends Command
{
    protected $signature = 'fundry:sync-countries 
                            {--force : Forcer la mise à jour même si les pays existent déjà}';
    
    protected $description = 'Synchroniser les pays et leurs devises depuis le seeder';

    public function handle()
    {
        $this->info('🔄 Synchronisation des pays...');

        try {
            $seeder = new CountrySeeder();
            $seeder->run();

            $this->info('✅ Synchronisation terminée avec succès!');
            $this->newLine();
            
            $count = \Hamadou\Fundry\Models\Country::count();
            $this->info("📊 Total de pays dans la base: {$count}");
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la synchronisation: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
