<?php

namespace Hamadou\Fundry\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Hamadou\Fundry\Models\Wallet createWallet(mixed $user, array $data)
 * @method static float getWalletBalance(\Hamadou\Fundry\Models\Wallet $wallet)
 * @method static \Illuminate\Support\Collection getWalletHistory(\Hamadou\Fundry\Models\Wallet $wallet, int $limit = 50)
 * @method static float calculateTotalBalance(mixed $user, string $currencyCode = 'USD')
 * @method static \Hamadou\Fundry\Models\Transaction transfer(\Hamadou\Fundry\Models\Wallet $fromWallet, \Hamadou\Fundry\Models\Wallet $toWallet, float $amount, ?string $description = null)
 * @method static \Hamadou\Fundry\Models\Transaction transferWithDTO(\Hamadou\Fundry\DTOs\TransferDTO $dto)
 * @method static \Hamadou\Fundry\Models\Transaction deposit(\Hamadou\Fundry\Models\Wallet $wallet, float $amount, ?string $description = null)
 * @method static \Hamadou\Fundry\Models\Transaction depositWithDTO(\Hamadou\Fundry\DTOs\DepositDTO $dto)
 * @method static \Hamadou\Fundry\Models\Transaction withdraw(\Hamadou\Fundry\Models\Wallet $wallet, float $amount, ?string $description = null)
 * @method static \Hamadou\Fundry\Models\Transaction withdrawWithDTO(\Hamadou\Fundry\DTOs\WithdrawalDTO $dto)
 * @method static \Hamadou\Fundry\Models\Transaction|null getTransactionByReference(string $reference)
 * @method static \Illuminate\Support\Collection getUserTransactions(int|string $userId, array $filters = [], int $limit = 50)
 * @method static float calculateDailyVolume(int|string $userId, string $currencyCode)
 * @method static \Hamadou\Fundry\Models\Currency createCurrency(array $data)
 * @method static bool updateExchangeRate(string $currencyCode, float $rate)
 * @method static float|null convertAmount(float $amount, string $fromCurrency, string $toCurrency)
 * @method static float|null convertCurrency(float $amount, string $fromCurrency, string $toCurrency)
 * @method static array getSupportedCurrencies()
 * @method static void clearCurrencyCache()
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
