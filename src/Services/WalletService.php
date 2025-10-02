<?php

namespace Hamadou\Fundry\Services;

use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Transaction;
use Hamadou\Fundry\Enums\TransactionType;
use Hamadou\Fundry\Enums\TransactionStatus;
use Hamadou\Fundry\Events\WalletCreated;
use Hamadou\Fundry\Events\TransactionCreated;
use Hamadou\Fundry\Exceptions\InsufficientFundsException;
use Hamadou\Fundry\Exceptions\ConcurrencyException;
use Hamadou\Fundry\Utils\Money;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function createWalletForUser($user, array $data): Wallet
    {
        return DB::transaction(function () use ($user, $data) {
            $wallet = Wallet::create(array_merge($data, [
                'user_id' => $user->id,
                'balance' => Money::toCents($data['balance'] ?? 0)
            ]));

            event(new WalletCreated($wallet));

            return $wallet;
        });
    }

    public function deposit(Wallet $wallet, float $amount): void
    {
        try {
            $cents = Money::toCents($amount);
            $wallet->balance += $cents;
            $wallet->save();
        } catch (\Exception $e) {
            throw new ConcurrencyException('Impossible de déposer les fonds : ' . $e->getMessage());
        }
    }

    public function withdraw(Wallet $wallet, float $amount): void
    {
        $cents = Money::toCents($amount);

        if (!$this->canWithdraw($wallet, $amount)) {
            throw new InsufficientFundsException('Fonds insuffisants ou limite dépassée');
        }

        try {
            $wallet->balance -= $cents;
            $wallet->save();
        } catch (\Exception $e) {
            throw new ConcurrencyException('Impossible de retirer les fonds : ' . $e->getMessage());
        }
    }

    public function canWithdraw(Wallet $wallet, float $amount): bool
    {
        $cents = Money::toCents($amount);
        return $wallet->balance >= $cents;
    }

    public function transfer(Wallet $fromWallet, Wallet $toWallet, float $amount, ?string $description = null): Transaction
    {
        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $description) {
            $this->withdraw($fromWallet, $amount);
            $this->deposit($toWallet, $amount);

            $transaction = Transaction::create([
                'user_id' => $fromWallet->user_id,
                'from_wallet_id' => $fromWallet->id,
                'to_wallet_id' => $toWallet->id,
                'currency_id' => $fromWallet->currency_id,
                'type' => TransactionType::TRANSFER,
                'amount' => $amount,
                'description' => $description,
                'status' => TransactionStatus::COMPLETED,
            ]);

            event(new TransactionCreated($transaction));

            return $transaction;
        });
    }

    public function getWalletBalance(Wallet $wallet): float
    {
        return Money::fromCents($wallet->balance);
    }

    public function getWalletHistory(Wallet $wallet, int $limit = 50)
    {
        return Transaction::where('from_wallet_id', $wallet->id)
            ->orWhere('to_wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function calculateTotalBalance($user, string $currencyCode = 'USD'): float
    {
        $wallets = Wallet::where('user_id', $user->id)->get();
        $total = 0;

        foreach ($wallets as $wallet) {
            $total += Money::fromCents($wallet->balance);
        }

        return $total;
    }
}
