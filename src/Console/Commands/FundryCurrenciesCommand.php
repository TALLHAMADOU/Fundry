<?php

namespace Hamadou\Fundry\Console\Commands;

use Illuminate\Console\Command;
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Seeders\CountrySeeder;
use Hamadou\Fundry\Seeders\CurrencySeeder;
use Hamadou\Fundry\Services\CurrencyService;

class FundryCurrenciesCommand extends Command
{
    protected $signature = 'fundry:currencies
                            {action=list : Action à exécuter (list|seed|sync|clear-cache)}
                            {--type= : Filtrer par type (fiat|crypto|device)}';

    protected $description = 'Gérer les devises Fundry (list, seed, sync, clear-cache)';

    public function handle(): int
    {
        $action = strtolower((string) $this->argument('action'));

        return match ($action) {
            'list' => $this->listCurrencies(),
            'seed', 'sync' => $this->seedCurrencies(),
            'clear-cache' => $this->clearCache(),
            default => $this->invalidAction($action),
        };
    }

    private function listCurrencies(): int
    {
        $query = Currency::query()->orderBy('type')->orderBy('iso_code');

        if ($type = $this->option('type')) {
            $query->where('type', strtolower((string) $type));
        }

        $currencies = $query->get();

        if ($currencies->isEmpty()) {
            $this->warn('Aucune devise trouvée.');
            return self::SUCCESS;
        }

        $rows = $currencies->map(fn (Currency $c) => [
            $c->iso_code,
            $c->name,
            $c->type->value ?? (string) $c->type,
            $c->symbol,
            (float) $c->exchange_rate,
            $c->decimals,
            $c->is_active ? 'Oui' : 'Non',
        ])->toArray();

        $this->table(
            ['Code ISO', 'Nom', 'Type', 'Symbole', 'Taux (USD)', 'Décimales', 'Active'],
            $rows
        );

        return self::SUCCESS;
    }

    private function seedCurrencies(): int
    {
        $this->info('🌱 Synchronisation des pays et devises...');

        (new CountrySeeder())->run();
        (new CurrencySeeder())->run();
        app(CurrencyService::class)->clearCache();

        $count = Currency::count();
        $this->info("✅ {$count} devises disponibles en base de données.");

        return self::SUCCESS;
    }

    private function clearCache(): int
    {
        app(CurrencyService::class)->clearCache();
        $this->info('✅ Cache des devises nettoyé avec succès.');

        return self::SUCCESS;
    }

    private function invalidAction(string $action): int
    {
        $this->error("Action invalide '{$action}'. Actions supportées : list, seed, sync, clear-cache");
        return self::INVALID;
    }
}
