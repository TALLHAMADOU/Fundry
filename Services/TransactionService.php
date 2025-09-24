<?php

namespace Hamadou\Fundry\Services;

use Hamadou\Fundry\Models\Transaction;
use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Enums\TransactionType;
use Hamadou\Fundry\Enums\TransactionStatus;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            return Transaction::create($data);
        });
    }

    public function processDeposit(Wallet $wallet, float $amount, string $description = null): Transaction
    {
        return DB::transaction(function () use ($wallet, $amount, $description) {
            $transaction = Transaction::create([
                'user_id' => $wallet->user_id,
                'to_wallet_id' => $wallet->id,
                'currency_id' => $wallet->currency_id,
                'type' => TransactionType::DEPOSIT,
                'amount' => $amount,
                'description' => $description,
                'status' => TransactionStatus::PENDING,
            ]);

            try {
                $wallet->deposit($amount);
                $transaction->markAsCompleted();
                return $transaction;

            } catch (\Exception $e) {
                $transaction->markAsFailed($e->getMessage());
                throw $e;
            }
        });
    }

    public function processWithdrawal(Wallet $wallet, float $amount, string $description = null): Transaction
    {
        return DB::transaction(function () use ($wallet, $amount, $description) {
            if (!$wallet->canWithdraw($amount)) {
                throw new \Exception('Fonds insuffisants ou limites dépassées');
            }

            $transaction = Transaction::create([
                'user_id' => $wallet->user_id,
                'from_wallet_id' => $wallet->id,
                'currency_id' => $wallet->currency_id,
                'type' => TransactionType::WITHDRAWAL,
                'amount' => $amount,
                'description' => $description,
                'status' => TransactionStatus::PENDING,
            ]);

            try {
                $wallet->withdraw($amount);
                $transaction->markAsCompleted();
                return $transaction;

            } catch (\Exception $e) {
                $transaction->markAsFailed($e->getMessage());
                throw $e;
            }
        });
    }

    public function getTransactionByReference(string $reference): ?Transaction
    {
        return Transaction::where('reference', $reference)->first();
    }

    public function getUserTransactions($userId, $filters = [], $limit = 50)
    {
        $query = Transaction::where('user_id', $userId);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return $query->with(['fromWallet', 'toWallet', 'currency'])
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public function calculateDailyVolume($userId, string $currencyCode): float
    {
        return Transaction::where('user_id', $userId)
            ->whereHas('currency', function ($query) use ($currencyCode) {
                $query->where('code', $currencyCode);
            })
            ->whereDate('created_at', today())
            ->completed()
            ->sum('amount');
    }
}