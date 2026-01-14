<?php

namespace Hamadou\Fundry\Services;

use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Models\Country;
use Hamadou\Fundry\Enums\CurrencyType;
use Hamadou\Fundry\Contracts\CurrencyServiceInterface;
use Hamadou\Fundry\Exceptions\InvalidCurrencyException;
use Illuminate\Support\Facades\Cache;

class CurrencyService implements CurrencyServiceInterface
{
    public function createCurrency(array $data): Currency
    {
        // Si un country_id est fourni, vérifier qu'il existe
        if (isset($data['country_id'])) {
            $country = Country::find($data['country_id']);
            if (!$country) {
                throw new InvalidCurrencyException("Le pays avec l'ID {$data['country_id']} n'existe pas");
            }
        }

        // Si un iso_code est fourni pour une devise fiat, valider le format
        if (isset($data['type']) && $data['type'] === CurrencyType::FIAT->value && isset($data['iso_code'])) {
            if (!Currency::isValidIso4217Code($data['iso_code'])) {
                throw new InvalidCurrencyException("Le code ISO 4217 '{$data['iso_code']}' n'est pas valide");
            }
        }

        return Currency::create($data);
    }

    public function updateExchangeRate(string $currencyCode, float $rate): bool
    {
        if ($rate <= 0) {
            throw new InvalidCurrencyException("Le taux de change doit être supérieur à zéro");
        }

        $currency = Currency::where('iso_code', strtoupper($currencyCode))
            ->orWhere('code', strtoupper($currencyCode))
            ->first();
        
        if (!$currency) {
            throw new InvalidCurrencyException("La devise '{$currencyCode}' n'existe pas");
        }

        return $currency->update(['exchange_rate' => $rate]);
    }

    public function getActiveCurrenciesByType(CurrencyType $type)
    {
        $cacheKey = $this->getCacheKey("currencies.type.{$type->value}");
        
        return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($type) {
            return Currency::where('type', $type)->active()->get();
        });
    }

    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        $from = Currency::where('iso_code', strtoupper($fromCurrency))
            ->orWhere('code', strtoupper($fromCurrency))
            ->first();
        
        $to = Currency::where('iso_code', strtoupper($toCurrency))
            ->orWhere('code', strtoupper($toCurrency))
            ->first();

        if (!$from) {
            throw new InvalidCurrencyException("La devise source '{$fromCurrency}' n'existe pas");
        }

        if (!$to) {
            throw new InvalidCurrencyException("La devise cible '{$toCurrency}' n'existe pas");
        }

        // Utiliser la méthode sécurisée avec validation
        return $from->convertToSafe($amount, $to);
    }

    public function getSupportedCurrencies(): array
    {
        $cacheKey = $this->getCacheKey('currencies.supported');
        
        return Cache::remember($cacheKey, $this->getCacheTtl(), function () {
            return Currency::active()->get()->groupBy('type')->toArray();
        });
    }

    public function syncExchangeRates(array $rates): int
    {
        $updated = 0;
        
        foreach ($rates as $currencyCode => $rate) {
            if ($this->updateExchangeRate($currencyCode, $rate)) {
                $updated++;
            }
        }

        // Nettoyer le cache après mise à jour
        $this->clearCache();

        return $updated;
    }

    /**
     * Nettoie le cache des devises
     */
    public function clearCache(): void
    {
        if (!config('fundry.cache.enabled', true)) {
            return;
        }

        $prefix = config('fundry.cache.prefix', 'fundry');
        Cache::tags(["{$prefix}.currencies"])->flush();
    }

    /**
     * Génère une clé de cache
     */
    private function getCacheKey(string $key): string
    {
        $prefix = config('fundry.cache.prefix', 'fundry');
        return "{$prefix}.{$key}";
    }

    /**
     * Récupère le TTL du cache
     */
    private function getCacheTtl(): int
    {
        if (!config('fundry.cache.enabled', true)) {
            return 0; // Pas de cache
        }

        return config('fundry.cache.ttl', 3600);
    }
}