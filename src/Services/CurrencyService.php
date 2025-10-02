<?php

namespace Hamadou\Fundry\Services;

use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Enums\CurrencyType;

class CurrencyService
{
    public function createCurrency(array $data): Currency
    {
        return Currency::create($data);
    }

    public function updateExchangeRate(string $currencyCode, float $rate): bool
    {
        $currency = Currency::where('code', $currencyCode)->first();
        
        if (!$currency) {
            return false;
        }

        return $currency->update(['exchange_rate' => $rate]);
    }

    public function getActiveCurrenciesByType(CurrencyType $type)
    {
        return Currency::where('type', $type)->active()->get();
    }

    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        $from = Currency::where('code', $fromCurrency)->first();
        $to = Currency::where('code', $toCurrency)->first();

        if (!$from || !$to) {
            return null;
        }

        return $from->convertTo($amount, $to);
    }

    public function getSupportedCurrencies(): array
    {
        return Currency::active()->get()->groupBy('type')->toArray();
    }

    public function syncExchangeRates(array $rates): int
    {
        $updated = 0;
        
        foreach ($rates as $currencyCode => $rate) {
            if ($this->updateExchangeRate($currencyCode, $rate)) {
                $updated++;
            }
        }

        return $updated;
    }
}