<?php

namespace Hamadou\Fundry;

use Hamadou\Fundry\Contracts\WalletServiceInterface;
use Hamadou\Fundry\Contracts\TransactionServiceInterface;
use Hamadou\Fundry\Contracts\CurrencyServiceInterface;
use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Transaction;
use Hamadou\Fundry\DTOs\DepositDTO;
use Hamadou\Fundry\DTOs\WithdrawalDTO;
use Hamadou\Fundry\DTOs\TransferDTO;
use Hamadou\Fundry\Exceptions\InsufficientFundsException;
use Hamadou\Fundry\Exceptions\ConcurrencyException;

class Fundry
{
    protected WalletServiceInterface $walletService;
    protected TransactionServiceInterface $transactionService;
    protected CurrencyServiceInterface $currencyService;

    public function __construct(
        WalletServiceInterface $walletService,
        TransactionServiceInterface $transactionService,
        CurrencyServiceInterface $currencyService
    ) {
        $this->walletService = $walletService;
        $this->transactionService = $transactionService;
        $this->currencyService = $currencyService;
    }

    // =====================
    // Wallet operations
    // =====================
    
    public function createWallet($user, array $data): Wallet
    {
        return $this->walletService->createWalletForUser($user, $data);
    }

    public function getWalletBalance(Wallet $wallet): float
    {
        return $this->walletService->getWalletBalance($wallet);
    }

    public function getWalletHistory(Wallet $wallet, int $limit = 50)
    {
        return $this->walletService->getWalletHistory($wallet, $limit);
    }

    public function transfer(Wallet $fromWallet, Wallet $toWallet, float $amount, ?string $description = null): Transaction
    {
        return $this->walletService->transfer($fromWallet, $toWallet, $amount, $description);
    }

    public function transferWithDTO(TransferDTO $dto): Transaction
    {
        return $this->walletService->transferWithDTO($dto);
    }

    // =====================
    // Transactions
    // =====================
    
    public function deposit(Wallet $wallet, float $amount, ?string $description = null): Transaction
    {
        return $this->transactionService->processDeposit($wallet, $amount, $description);
    }

    public function depositWithDTO(DepositDTO $dto): Transaction
    {
        return $this->transactionService->processDepositWithDTO($dto);
    }

    public function withdraw(Wallet $wallet, float $amount, ?string $description = null): Transaction
    {
        return $this->transactionService->processWithdrawal($wallet, $amount, $description);
    }

    public function withdrawWithDTO(WithdrawalDTO $dto): Transaction
    {
        return $this->transactionService->processWithdrawalWithDTO($dto);
    }

    public function getTransactionByReference(string $reference): ?Transaction
    {
        return $this->transactionService->getTransactionByReference($reference);
    }

    public function getUserTransactions($userId, array $filters = [], int $limit = 50)
    {
        return $this->transactionService->getUserTransactions($userId, $filters, $limit);
    }

    public function calculateDailyVolume($userId, string $currencyCode): float
    {
        return $this->transactionService->calculateDailyVolume($userId, $currencyCode);
    }

    // =====================
    // Currency operations
    // =====================
    
    public function createCurrency(array $data)
    {
        return $this->currencyService->createCurrency($data);
    }

    public function updateExchangeRate(string $currencyCode, float $rate): bool
    {
        return $this->currencyService->updateExchangeRate($currencyCode, $rate);
    }

    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        return $this->currencyService->convertAmount($amount, $fromCurrency, $toCurrency);
    }

    public function getSupportedCurrencies(): array
    {
        return $this->currencyService->getSupportedCurrencies();
    }
}
