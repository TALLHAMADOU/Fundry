<?php

namespace Hamadou\Fundry;

use Hamadou\Fundry\Services\CurrencyService;
use Hamadou\Fundry\Services\WalletService;
use Hamadou\Fundry\Services\TransactionService;
use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Transaction;

class Fundry
{
    protected $currencyService;
    protected $walletService;
    protected $transactionService;

    public function __construct(
        CurrencyService $currencyService,
        WalletService $walletService,
        TransactionService $transactionService
    ) {
        $this->currencyService = $currencyService;
        $this->walletService = $walletService;
        $this->transactionService = $transactionService;
    }

    // Wallet methods
    public function createWallet(array $data): Wallet
    {
        return $this->walletService->createWalletForUser(auth()->user(), $data);
    }

    public function getWalletBalance(Wallet $wallet): float
    {
        return $this->walletService->getWalletBalance($wallet);
    }

    // Transaction methods
    public function transfer($fromWallet, $toWallet, float $amount, string $description = null): Transaction
    {
        return $this->walletService->transfer($fromWallet, $toWallet, $amount, $description);
    }

    public function deposit($wallet, float $amount, string $description = null): Transaction
    {
        return $this->transactionService->processDeposit($wallet, $amount, $description);
    }

    public function withdraw($wallet, float $amount, string $description = null): Transaction
    {
        return $this->transactionService->processWithdrawal($wallet, $amount, $description);
    }

    // Currency methods
    public function convertCurrency(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        return $this->currencyService->convertAmount($amount, $fromCurrency, $toCurrency);
    }

    public function getSupportedCurrencies(): array
    {
        return $this->currencyService->getSupportedCurrencies();
    }

    // Utility methods
    public function getTransaction(string $reference): ?Transaction
    {
        return $this->transactionService->getTransactionByReference($reference);
    }

    public function getUserWallets()
    {
        return auth()->user()->wallets()->active()->with('currency')->get();
    }

    public function getTotalBalance(string $currencyCode = 'USD'): float
    {
        return $this->walletService->calculateTotalBalance(auth()->user(), $currencyCode);
    }
}