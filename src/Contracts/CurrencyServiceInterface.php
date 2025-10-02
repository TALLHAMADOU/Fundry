<?php

namespace Hamadou\Fundry\Contracts;

use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Enums\CurrencyType;

interface CurrencyServiceInterface
{
    /**
     * Crée une nouvelle devise.
     *
     * @param array $data
     * @return Currency
     */
    public function createCurrency(array $data): Currency;

    /**
     * Met à jour le taux de change d'une devise existante.
     *
     * @param string $currencyCode
     * @param float $rate
     * @return bool
     */
    public function updateExchangeRate(string $currencyCode, float $rate): bool;

    /**
     * Récupère toutes les devises actives d'un type donné.
     *
     * @param CurrencyType $type
     * @return \Illuminate\Support\Collection
     */
    public function getActiveCurrenciesByType(CurrencyType $type);

    /**
     * Convertit un montant d'une devise à une autre.
     *
     * @param float $amount
     * @param string $fromCurrency
     * @param string $toCurrency
     * @return float|null
     */
    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency): ?float;

    /**
     * Récupère la liste des devises supportées, groupées par type.
     *
     * @return array
     */
    public function getSupportedCurrencies(): array;

    /**
     * Synchronise plusieurs taux de change.
     *
     * @param array $rates
     * @return int Nombre de devises mises à jour
     */
    public function syncExchangeRates(array $rates): int;
}
