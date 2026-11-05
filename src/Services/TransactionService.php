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
use Hamadou\Fundry\Traits\FundryHelpers;
use Hamadou\Fundry\Utils\Money;
use Illuminate\Support\Facades\DB;

class TransactionService implements TransactionServiceInterface
{
    use FundryHelpers;

    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(fn () => Transaction::create($data));
    }

    public function processDeposit(Wallet $wallet, float $amount, ?string $description = null): Transaction
    {
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
            $wallet = Wallet::lockForUpdate()->findOrFail($dto->walletId);

            if (!$wallet->belongsToUser($dto->userId)) {
                throw new UnauthorizedWalletException('Ce portefeuille ne vous appartient pas');
            }

            if ($wallet->is_active === false || $wallet->is_active === 0) {
                throw new InvalidAmountException('Impossible de déposer sur un portefeuille inactif');
            }

            $this->validateAmount($dto->amount);

            $netAmount = $dto->getNetAmount();
            $commissionAmount = $dto->getCommissionAmount();

            if ($wallet->max_balance !== null && Money::comp(Money::add($wallet->balance, $netAmount), $wallet->max_balance) > 0) {
                throw new InvalidAmountException('Le dépôt dépasse la limite maximale du portefeuille');
            }

            try {
                $wallet->deposit($netAmount);

                $transaction = Transaction::create([
                    'user_id' => $dto->userId,
                    'to_wallet_id' => $dto->walletId,
                    'currency_id' => $wallet->currency_id,
                    'type' => TransactionType::DEPOSIT,
                    'amount' => $netAmount,
                    'description' => $dto->description,
                    'status' => TransactionStatus::COMPLETED,
                    'completed_at' => now(),
                    'metadata' => array_merge($dto->metadata ?? [], [
                        'commission_percentage' => $dto->commissionPercentage,
                        'commission_amount' => $commissionAmount,
                        'gross_amount' => $dto->amount,
                    ]),
                ]);

                event(new TransactionCreated($transaction));

                return $transaction;
            } catch (\Exception $e) {
                throw new ConcurrencyException('Impossible de déposer les fonds : ' . $e->getMessage());
            }
        });
    }

    public function processWithdrawal(Wallet $wallet, float $amount, ?string $description = null): Transaction
    {
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
            $wallet = Wallet::lockForUpdate()->findOrFail($dto->walletId);

            if (!$wallet->belongsToUser($dto->userId)) {
                throw new UnauthorizedWalletException('Ce portefeuille ne vous appartient pas');
            }

            $this->validateAmount($dto->amount);

            $totalAmount = $dto->getTotalAmount();
            $commissionAmount = $dto->getCommissionAmount();

            if (!$wallet->canWithdraw($totalAmount)) {
                throw new InsufficientFundsException('Fonds insuffisants ou limites dépassées');
            }

            try {
                $wallet->withdraw($totalAmount);

                $transaction = Transaction::create([
                    'user_id' => $dto->userId,
                    'from_wallet_id' => $dto->walletId,
                    'currency_id' => $wallet->currency_id,
                    'type' => TransactionType::WITHDRAWAL,
                    'amount' => $dto->amount,
                    'description' => $dto->description,
                    'status' => TransactionStatus::COMPLETED,
                    'completed_at' => now(),
                    'metadata' => array_merge($dto->metadata ?? [], [
                        'commission_percentage' => $dto->commissionPercentage,
                        'commission_amount' => $commissionAmount,
                        'total_debited' => $totalAmount,
                    ]),
                ]);

                event(new TransactionCreated($transaction));

                return $transaction;
            } catch (\Exception $e) {
                throw new ConcurrencyException('Impossible de retirer les fonds : ' . $e->getMessage());
            }
        });
    }

    /**
     * Valide un montant
     */
    private function validateAmount(float $amount): void
    {
        $this->validateMonetaryAmount($amount);
    }

    public function getTransactionByReference(string $reference): ?Transaction
    {
        return Transaction::where('reference', $reference)->first();
    }

    public function getUserTransactions($userId, array $filters = [], int $limit = 50)
    {
        $query = Transaction::where('user_id', $userId);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        return $query->with(['fromWallet', 'toWallet', 'currency'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function calculateDailyVolume($userId, string $currencyCode): float
    {
        $normalized = strtoupper($currencyCode);

        return (float) Transaction::where('user_id', $userId)
            ->whereHas('currency', function ($q) use ($normalized) {
                $q->where('iso_code', $normalized)
                  ->orWhere('code', $normalized);
            })
            ->whereDate('created_at', today())
            ->where('status', TransactionStatus::COMPLETED)
            ->sum('amount');
    }
}
