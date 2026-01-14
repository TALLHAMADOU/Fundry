<?php

namespace Hamadou\Fundry\Services;

use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Transaction;
use Hamadou\Fundry\DTOs\DepositDTO;
use Hamadou\Fundry\DTOs\WithdrawalDTO;
use Hamadou\Fundry\Enums\TransactionType;
use Hamadou\Fundry\Enums\TransactionStatus;
use Hamadou\Fundry\Events\TransactionCreated;
use Hamadou\Fundry\Exceptions\InsufficientFundsException;
use Hamadou\Fundry\Exceptions\ConcurrencyException;
use Hamadou\Fundry\Exceptions\UnauthorizedWalletException;
use Hamadou\Fundry\Exceptions\InvalidAmountException;
use Hamadou\Fundry\Contracts\TransactionServiceInterface;
use Illuminate\Support\Facades\DB;

class TransactionService implements TransactionServiceInterface
{
    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(fn() => Transaction::create($data));
    }

    public function processDeposit(Wallet $wallet, float $amount, ?string $description = null): Transaction
    {
        // Créer un DTO depuis les paramètres pour compatibilité ascendante
        $dto = new DepositDTO(
            userId: $wallet->user_id,
            walletId: $wallet->id,
            amount: $amount,
            description: $description
        );

        return $this->processDepositWithDTO($dto);
    }

    public function processDepositWithDTO(DepositDTO $dto): Transaction
    {
        return DB::transaction(function () use ($dto) {
            // Vérifier que le wallet appartient à l'utilisateur
            $wallet = Wallet::lockForUpdate()->findOrFail($dto->walletId);
            
            if ($wallet->user_id != $dto->userId) {
                throw new UnauthorizedWalletException('Ce portefeuille ne vous appartient pas');
            }

            // Valider le montant
            $this->validateAmount($dto->amount);

            // Calculer le montant net après commission
            $netAmount = $dto->getNetAmount();
            $commissionAmount = $dto->getCommissionAmount();

            // Vérifier la limite max_balance si définie
            if ($wallet->max_balance !== null && ($wallet->balance + $netAmount) > $wallet->max_balance) {
                throw new InvalidAmountException('Le dépôt dépasse la limite maximale du portefeuille');
            }

            $transaction = Transaction::create([
                'user_id' => $dto->userId,
                'to_wallet_id' => $dto->walletId,
                'currency_id' => $wallet->currency_id,
                'type' => TransactionType::DEPOSIT,
                'amount' => $netAmount,
                'description' => $dto->description,
                'status' => TransactionStatus::PENDING,
                'metadata' => array_merge($dto->metadata ?? [], [
                    'commission_percentage' => $dto->commissionPercentage,
                    'commission_amount' => $commissionAmount,
                    'gross_amount' => $dto->amount,
                ]),
            ]);

            try {
                // Dépôt du montant net
                $wallet->deposit($netAmount);
                $wallet->save();

                $transaction->status = TransactionStatus::COMPLETED;
                $transaction->completed_at = now();
                $transaction->save();

                event(new TransactionCreated($transaction));

                return $transaction;
            } catch (\Exception $e) {
                $transaction->status = TransactionStatus::FAILED;
                $transaction->failed_at = now();
                $transaction->save();

                throw new ConcurrencyException('Impossible de déposer les fonds : ' . $e->getMessage());
            }
        });
    }

    public function processWithdrawal(Wallet $wallet, float $amount, ?string $description = null): Transaction
    {
        // Créer un DTO depuis les paramètres pour compatibilité ascendante
        $dto = new WithdrawalDTO(
            userId: $wallet->user_id,
            walletId: $wallet->id,
            amount: $amount,
            description: $description
        );

        return $this->processWithdrawalWithDTO($dto);
    }

    public function processWithdrawalWithDTO(WithdrawalDTO $dto): Transaction
    {
        return DB::transaction(function () use ($dto) {
            // Vérifier que le wallet appartient à l'utilisateur avec verrou
            $wallet = Wallet::lockForUpdate()->findOrFail($dto->walletId);
            
            if ($wallet->user_id != $dto->userId) {
                throw new UnauthorizedWalletException('Ce portefeuille ne vous appartient pas');
            }

            // Valider le montant
            $this->validateAmount($dto->amount);

            // Calculer le montant total à débiter (montant + commission)
            $totalAmount = $dto->getTotalAmount();
            $commissionAmount = $dto->getCommissionAmount();

            // Vérifier si le retrait est possible
            if (!$wallet->canWithdraw($totalAmount)) {
                throw new InsufficientFundsException('Fonds insuffisants ou limites dépassées');
            }

            $transaction = Transaction::create([
                'user_id' => $dto->userId,
                'from_wallet_id' => $dto->walletId,
                'currency_id' => $wallet->currency_id,
                'type' => TransactionType::WITHDRAWAL,
                'amount' => $dto->amount, // Montant net retiré
                'description' => $dto->description,
                'status' => TransactionStatus::PENDING,
                'metadata' => array_merge($dto->metadata ?? [], [
                    'commission_percentage' => $dto->commissionPercentage,
                    'commission_amount' => $commissionAmount,
                    'total_debited' => $totalAmount,
                ]),
            ]);

            try {
                // Retrait du montant total (montant + commission)
                $wallet->withdraw($totalAmount);
                $wallet->save();

                $transaction->status = TransactionStatus::COMPLETED;
                $transaction->completed_at = now();
                $transaction->save();

                event(new TransactionCreated($transaction));

                return $transaction;
            } catch (\Exception $e) {
                $transaction->status = TransactionStatus::FAILED;
                $transaction->failed_at = now();
                $transaction->save();

                throw new ConcurrencyException('Impossible de retirer les fonds : ' . $e->getMessage());
            }
        });
    }

    /**
     * Valide un montant
     */
    private function validateAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidAmountException('Le montant doit être supérieur à zéro');
        }

        // Vérifier la précision (max 8 décimales)
        $decimals = strlen(substr(strrchr((string) $amount, '.'), 1));
        if ($decimals > 8) {
            throw new InvalidAmountException('Le montant ne peut pas avoir plus de 8 décimales');
        }
    }

    public function getTransactionByReference(string $reference): ?Transaction
    {
        return Transaction::where('reference', $reference)->first();
    }

    public function getUserTransactions($userId, $filters = [], $limit = 50)
    {
        $query = Transaction::where('user_id', $userId);

        if (isset($filters['type'])) $query->where('type', $filters['type']);
        if (isset($filters['status'])) $query->where('status', $filters['status']);
        if (isset($filters['start_date'])) $query->whereDate('created_at', '>=', $filters['start_date']);
        if (isset($filters['end_date'])) $query->whereDate('created_at', '<=', $filters['end_date']);

        return $query->with(['fromWallet', 'toWallet', 'currency'])
                     ->orderBy('created_at', 'desc')
                     ->limit($limit)
                     ->get();
    }

    public function calculateDailyVolume($userId, string $currencyCode): float
    {
        return Transaction::where('user_id', $userId)
            ->whereHas('currency', fn($q) => $q->where('code', $currencyCode))
            ->whereDate('created_at', today())
            ->where('status', TransactionStatus::COMPLETED)
            ->sum('amount');
    }
}
