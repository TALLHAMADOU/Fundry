<?php

namespace Hamadou\Fundry\Services;

use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Utils\Money;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    /**
     * Récupère les taux de change normalisés (valeur d'1 unité de devise en devise de base).
     *
     * @param string|null $provider
     * @param string $baseCurrency
     * @param string|null $apiKey
     * @return array<string, float>
     */
    public function fetchRates(?string $provider = null, string $baseCurrency = 'USD', ?string $apiKey = null): array
    {
        $provider = $provider ?? config('fundry.exchange_rate_provider', 'manual');
        $baseCurrency = strtoupper($baseCurrency);

        return match ($provider) {
            'exchangerate-api' => $this->fetchFromExchangeRateApi($baseCurrency, $apiKey),
            'manual' => $this->getManualRates(),
            default => throw new \InvalidArgumentException("Provider '{$provider}' non supporté"),
        };
    }

    /**
     * Récupère les taux depuis ExchangeRate-API et les convertit au format interne Fundry
     * (1 unité de devise = X unités de devise de base).
     *
     * @return array<string, float>
     */
    public function fetchFromExchangeRateApi(string $baseCurrency = 'USD', ?string $apiKey = null): array
    {
        $apiKey = $apiKey ?: config('fundry.exchange_rate_api_key');

        if (empty($apiKey)) {
            throw new \RuntimeException('Clé API manquante. Utilisez --api-key ou configurez fundry.exchange_rate_api_key');
        }

        $response = Http::timeout(10)->get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$baseCurrency}");

        if (!$response->successful()) {
            throw new \RuntimeException('Erreur API: ' . $response->body());
        }

        $data = $response->json();

        if (!isset($data['conversion_rates']) || !is_array($data['conversion_rates'])) {
            throw new \RuntimeException('Format de réponse invalide');
        }

        $normalizedRates = [];
        foreach ($data['conversion_rates'] as $code => $unitsPerBase) {
            $unitsPerBase = (float) $unitsPerBase;
            if ($unitsPerBase > 0) {
                // L'API retourne : 1 USD = $unitsPerBase Devise (ex: 1 USD = 600 XOF)
                // Fundry stocke : 1 Devise = X USD (ex: 1 XOF = 1/600 USD)
                $normalizedRates[strtoupper($code)] = Money::div(1, $unitsPerBase, 10);
            }
        }

        return $normalizedRates;
    }

    /**
     * Retourne les taux actuellement enregistrés en base (mode manuel).
     *
     * @return array<string, float>
     */
    public function getManualRates(): array
    {
        return Currency::where('is_active', true)
            ->pluck('exchange_rate', 'iso_code')
            ->map(fn ($rate) => (float) $rate)
            ->toArray();
    }
}
