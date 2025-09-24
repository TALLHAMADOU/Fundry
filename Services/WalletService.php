<?php

namespace Hamadou\Fundry\Services;

use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Transaction;
use Hamadou\Fundry\Enums\TransactionType;
use Hamadou\Fundry\Enums\TransactionStatus;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function createWalletForUser($user, array $data): Wallet
    {
        return DB::transaction(function () use ($user, $data) {
            // S'assurer qu'un seul portefeuille par défaut par utilisateur
            if ($data['is_default'] ?? false) {
                Wallet::where('user_id', $user->id)->update(['is_default' => false]);
            }

            return $user->wallets()->create($data);
        });
    }

    public function transfer($fromWallet, $toWallet, float $amount, string $description = null): Transaction
    {
        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $description) {
            // Vérifier les fonds suffisants
            if (!$fromWallet->canWithdraw($amount)) {
                throw new \Exception('Fonds insuffisants pour effectuer le transfert');
            }

            // Créer la transaction
            $transaction = Transaction::create([
                'user_id' => $fromWallet->user_id,
                'from_wallet_id' => $fromWallet->id,
                'to_wallet_id' => $toWallet->id,
                'currency_id' => $fromWallet->currency_id,
                'type' => TransactionType::TRANSFER,
                'amount' => $amount,
                'description' => $description,
                'status' => TransactionStatus::PENDING,
            ]);

            try {
                // Effectuer le transfert
                $fromWallet->withdraw($amount);
                $toWallet->deposit($amount);

                // Marquer comme complétée
                $transaction->markAsCompleted();

                return $transaction;

            } catch (\Exception $e) {
                $transaction->markAsFailed($e->getMessage());
                throw $e;
            }
        });
    }

    public function getWalletBalance(Wallet $wallet): float
    {
        return $wallet->balance;
    }

    public function getWalletHistory(Wallet $wallet, $limit = 50)
    {
        return Transaction::where(function ($query) use ($wallet) {
            $query->where('from_wallet_id', $wallet->id)
                  ->orWhere('to_wallet_id', $wallet->id);
        })
        ->with(['fromWallet', 'toWallet', 'currency'])
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get();
    }

    public function calculateTotalBalance($user, string $currencyCode = 'USD'): float
    {
        $total = 0;
        $wallets = $user->wallets()->active()->with('currency')->get();

        foreach ($wallets as $wallet) {
            if ($currencyCode === 'USD') {
                $total += $wallet->getBalanceInUsd();
            } else {
                // Conversion vers la devise souhaitée
                $converted = $wallet->currency->convertTo(
                    $wallet->balance, 
                    Currency::where('code', $currencyCode)->first()
                );
                $total += $converted;
            }
        }

        return $total;
    }
}