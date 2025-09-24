<?php

namespace Hamadou\Fundry\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Hamadou\Fundry\Models\Wallet createWallet(array $data)
 * @method static \Hamadou\Fundry\Models\Transaction transfer($fromWallet, $toWallet, float $amount, string $description = null)
 * @method static \Hamadou\Fundry\Models\Transaction deposit($wallet, float $amount, string $description = null)
 * @method static \Hamadou\Fundry\Models\Transaction withdraw(\Hamadou\Fundry\Models\Wallet $wallet, float $amount, string $description = null)
 * @method static float getWalletBalance(\Hamadou\Fundry\Models\Wallet $wallet)
 * @method static float convertCurrency(float $amount, string $fromCurrency, string $toCurrency)
 * @method static array getSupportedCurrencies()
 * @method static \Hamadou\Fundry\Models\Transaction getTransaction(string $reference)
 * 
 * @see \Hamadou\Fundry\Fundry
 */
class Fundry extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'fundry';
    }
}