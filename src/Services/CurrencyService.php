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
        if (isset($data['country_id'])) {
            $country = Country::find($data['country_id']);
            if (!$country) {
                throw new InvalidCurrencyException("Le pays avec l'ID {$data['country_id']} n'existe pas");
            }
        }

        $type = $data['type'] ?? null;
        $typeValue = $type instanceof CurrencyType ? $type->value : (string) $type;

        if ($typeValue === CurrencyType::FIAT->value && isset($data['iso_code'])) {
            if (!Currency::isValidIso4217Code($data['iso_code'])) {
                throw new InvalidCurrencyException("Le code ISO 4217 '{$data['iso_code']}' n'est pas valide");
            }
        }

        return Currency::create($data);
    }

    public function updateExchangeRate(string $currencyCode, float $rate): bool
    {
        if ($rate <= 0) {
            throw new InvalidCurrencyException('Le taux de change doit être supérieur à zéro');
        }

        $normalized = strtoupper($currencyCode);
        $currency = Currency::where('iso_code', $normalized)
            ->orWhere('code', $normalized)
            ->first();

        if (!$currency) {
            throw new InvalidCurrencyException("La devise '{$currencyCode}' n'existe pas");
        }

        return $currency->update(['exchange_rate' => $rate]);
    }

    public function getActiveCurrenciesByType(CurrencyType $type)
    {
        if (!config('fundry.cache.enabled', true)) {
            return Currency::where('type', $type)->active()->get();
        }

        $cacheKey = $this->getCacheKey("currencies.type.{$type->value}");

        return Cache::remember($cacheKey, $this->getCacheTtl(), function () use ($type) {
            return Currency::where('type', $type)->active()->get();
        });
    }

    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        $fromCode = strtoupper($fromCurrency);
        $toCode = strtoupper($toCurrency);

        $from = Currency::where('iso_code', $fromCode)
            ->orWhere('code', $fromCode)
            ->first();

        $to = Currency::where('iso_code', $toCode)
            ->orWhere('code', $toCode)
            ->first();

        if (!$from) {
            throw new InvalidCurrencyException("La devise source '{$fromCurrency}' n'existe pas");
        }

        if (!$to) {
            throw new InvalidCurrencyException("La devise cible '{$toCurrency}' n'existe pas");
        }

        return $from->convertToSafe($amount, $to);
    }

    public function getSupportedCurrencies(): array
    {
        $fetch = function () {
            return Currency::active()
                ->get()
                ->groupBy(fn (Currency $currency) => $currency->type instanceof CurrencyType ? $currency->type->value : (string) $currency->type)
                ->toArray();
        };

        if (!config('fundry.cache.enabled', true)) {
            return $fetch();
        }

        $cacheKey = $this->getCacheKey('currencies.supported');

        return Cache::remember($cacheKey, $this->getCacheTtl(), $fetch);
    }

    public function syncExchangeRates(array $rates): int
    {
        $updated = 0;

        foreach ($rates as $currencyCode => $rate) {
            $rate = (float) $rate;
            if ($rate <= 0) {
                continue;
            }

            $normalized = strtoupper((string) $currencyCode);
            $exists = Currency::where('iso_code', $normalized)
                ->orWhere('code', $normalized)
                ->exists();

            if ($exists && $this->updateExchangeRate($normalized, $rate)) {
                $updated++;
            }
        }

        $this->clearCache();

        return $updated;
    }

    /**
     * Nettoie le cache des devises (compatible avec tous les drivers : file, database, array, redis)
     */
    public function clearCache(): void
    {
        if (!config('fundry.cache.enabled', true)) {
            return;
        }

        Cache::forget($this->getCacheKey('currencies.supported'));

        foreach (CurrencyType::cases() as $type) {
            Cache::forget($this->getCacheKey("currencies.type.{$type->value}"));
        }

        $codes = Currency::withTrashed()->pluck('iso_code')
            ->merge(Currency::withTrashed()->pluck('code'))
            ->filter()
            ->unique();

        foreach ($codes as $code) {
            Cache::forget($this->getCacheKey('currency.' . strtoupper($code)));
        }
    }

    private function getCacheKey(string $key): string
    {
        $prefix = config('fundry.cache.prefix', 'fundry');
        return "{$prefix}.{$key}";
    }

    private function getCacheTtl(): int
    {
        if (!config('fundry.cache.enabled', true)) {
            return 0;
        }

        return (int) config('fundry.cache.ttl', 3600);
    }
}
