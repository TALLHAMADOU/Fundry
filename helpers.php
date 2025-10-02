<?php

use Hamadou\Fundry\Facades\Fundry;

/**
 * Accès rapide à l'instance Fundry.
 *
 * @return \Hamadou\Fundry\Fundry
 */
if (!function_exists('fundry')) {
    function fundry(): \Hamadou\Fundry\Fundry
    {
        return Fundry::getFacadeRoot();
    }
}

/**
 * Formate un montant avec sa devise.
 *
 * @param float $amount
 * @param string $currencyCode
 * @return string
 */
if (!function_exists('format_currency')) {
    function format_currency(float $amount, string $currencyCode = 'USD'): string
    {
        return number_format($amount, 2) . ' ' . strtoupper($currencyCode);
    }
}

/**
 * Vérifie si un portefeuille peut effectuer un retrait.
 *
 * @param \Hamadou\Fundry\Models\Wallet $wallet
 * @param float $amount
 * @return bool
 */
if (!function_exists('can_withdraw')) {
    function can_withdraw(\Hamadou\Fundry\Models\Wallet $wallet, float $amount): bool
    {
        return $wallet->canWithdraw($amount);
    }
}
