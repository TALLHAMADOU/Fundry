<?php

namespace Hamadou\Fundry\Traits;

use Hamadou\Fundry\Exceptions\InvalidAmountException;
use Hamadou\Fundry\Utils\Money;

trait FundryHelpers
{
    /**
     * Valide un montant financier selon la précision configurée.
     *
     * @throws InvalidAmountException
     */
    protected function validateMonetaryAmount(float $amount, ?int $maxDecimals = null): void
    {
        if ($amount <= 0) {
            throw new InvalidAmountException('Le montant doit être supérieur à zéro');
        }

        $precision = $maxDecimals ?? (int) config('fundry.precision', 8);

        if (!Money::hasValidScale($amount, $precision)) {
            throw new InvalidAmountException("Le montant ne peut pas avoir plus de {$precision} décimales");
        }
    }

    /**
     * Formate un montant avec le code devise.
     */
    protected function formatCurrencyAmount(float $amount, string $currencyCode = 'USD', int $decimals = 2): string
    {
        return number_format($amount, $decimals, '.', ',') . ' ' . strtoupper($currencyCode);
    }
}
