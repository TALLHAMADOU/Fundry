<?php

use Hamadou\Fundry\Facades\Fundry;
use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Currency;

if (!function_exists('fundry')) {
    function fundry(): Hamadou\Fundry\Fundry
    {
        return app('fundry');
    }
}

if (!function_exists('currency')) {
    function currency(string $code): ?Currency
    {
        return Currency::where('code', $code)->active()->first();
    }
}

if (!function_exists('format_money')) {
    function format_money(float $amount, string $currencyCode): string
    {
        $currency = currency($currencyCode);
        return $currency ? $currency->getFormattedAmount($amount) : number_format($amount, 2);
    }
}

if (!function_exists('convert_currency')) {
    function convert_currency(float $amount, string $from, string $to): ?float
    {
        return fundry()->convertCurrency($amount, $from, $to);
    }
}

if (!function_exists('get_wallet')) {
    function get_wallet($user, string $currencyCode): ?Wallet
    {
        return $user->getWalletByCurrency($currencyCode);
    }
}