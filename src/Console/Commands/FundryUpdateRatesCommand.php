<?php

namespace Hamadou\Fundry\Console\Commands;

use Illuminate\Console\Command;
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Services\CurrencyService;
use Illuminate\Support\Facades\Http;

class FundryUpdateRatesCommand extends Command
{
    protected $signature = 'fundry:update-rates 
                            {--provider=manual : Provider à utiliser (manual, exchangerate-api)}
                            {--api-key= : Clé API pour le provider externe}
                            {--base=USD : Devise de base pour les taux}';
    
    protected $description = 'Mettre à jour les taux de change des devises';

    public function handle()
    {
        $provider = $this->option('provider');
        $baseCurrency = $this->option('base');

        $this->info("🔄 Mise à jour des taux de change (Provider: {$provider}, Base: {$baseCurrency})...");
        $this->newLine();

        try {
            $rates = $this->fetchRates($provider, $baseCurrency);
            
            if (empty($rates)) {
                $this->warn('⚠️  Aucun taux de change récupéré.');
                return self::FAILURE;
            }

            $currencyService = app(CurrencyService::class);
            $updated = $currencyService->syncExchangeRates($rates);

            $this->info("✅ {$updated} taux de change mis à jour avec succès!");
            $this->newLine();

            // Afficher quelques exemples
            $this->info('📊 Exemples de taux mis à jour:');
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

    private function fetchRates(string $provider, string $baseCurrency): array
    {
        return match($provider) {
            'exchangerate-api' => $this->fetchFromExchangeRateAPI($baseCurrency),
            'manual' => $this->getManualRates(),
            default => throw new \InvalidArgumentException("Provider '{$provider}' non supporté"),
        };
    }

    private function fetchFromExchangeRateAPI(string $baseCurrency): array
    {
        $apiKey = $this->option('api-key') ?? config('fundry.exchange_rate_api_key');
        
        if (empty($apiKey)) {
            throw new \RuntimeException('Clé API manquante. Utilisez --api-key ou configurez fundry.exchange_rate_api_key');
        }

        $this->info('🌐 Récupération des taux depuis ExchangeRate-API...');

        try {
            $response = Http::timeout(10)->get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$baseCurrency}");
            
            if (!$response->successful()) {
                throw new \RuntimeException('Erreur API: ' . $response->body());
            }

            $data = $response->json();
            
            if (!isset($data['conversion_rates'])) {
                throw new \RuntimeException('Format de réponse invalide');
            }

            return $data['conversion_rates'];
        } catch (\Exception $e) {
            $this->error("Erreur lors de la récupération: {$e->getMessage()}");
            throw $e;
        }
    }

    private function getManualRates(): array
    {
        $this->warn('⚠️  Mode manuel: aucun taux mis à jour automatiquement.');
        $this->info('💡 Utilisez --provider=exchangerate-api pour récupérer les taux automatiquement.');
        
        // Retourner les taux actuels pour information
        return Currency::where('is_active', true)
            ->pluck('exchange_rate', 'iso_code')
            ->toArray();
    }
}
