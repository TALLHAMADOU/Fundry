<?php

namespace Hamadou\Fundry\Console\Commands;

use Illuminate\Console\Command;
use Hamadou\Fundry\Services\CurrencyService;
use Hamadou\Fundry\Services\ExchangeRateService;

class FundryUpdateRatesCommand extends Command
{
    protected $signature = 'fundry:update-rates 
                            {--provider=manual : Provider à utiliser (manual, exchangerate-api)}
                            {--api-key= : Clé API pour le provider externe}
                            {--base=USD : Devise de base pour les taux}';

    protected $description = 'Mettre à jour les taux de change des devises';

    public function handle(ExchangeRateService $exchangeRateService, CurrencyService $currencyService): int
    {
        $provider = (string) ($this->option('provider') ?: config('fundry.exchange_rate_provider', 'manual'));
        $baseCurrency = strtoupper((string) ($this->option('base') ?: config('fundry.default_currency', 'USD')));
        $apiKey = $this->option('api-key') ?: config('fundry.exchange_rate_api_key');

        $this->info("🔄 Mise à jour des taux de change (Provider: {$provider}, Base: {$baseCurrency})...");
        $this->newLine();

        try {
            if ($provider === 'exchangerate-api') {
                $this->info('🌐 Récupération des taux depuis ExchangeRate-API...');
            } elseif ($provider === 'manual') {
                $this->warn('⚠️  Mode manuel: vérification des taux existants en base.');
                $this->info('💡 Utilisez --provider=exchangerate-api pour récupérer les taux automatiquement.');
            }

            $rates = $exchangeRateService->fetchRates($provider, $baseCurrency, $apiKey);

            if (empty($rates)) {
                $this->warn('⚠️  Aucun taux de change récupéré.');
                return self::FAILURE;
            }

            $updated = $currencyService->syncExchangeRates($rates);

            $this->info("✅ {$updated} taux de change mis à jour avec succès!");
            $this->newLine();

            $this->info('📊 Exemples de taux mis à jour (valeur en ' . $baseCurrency . '):');
            $examples = array_slice($rates, 0, 5, true);
            foreach ($examples as $code => $rate) {
                $this->line("  • {$code}: {$rate}");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la mise à jour: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
