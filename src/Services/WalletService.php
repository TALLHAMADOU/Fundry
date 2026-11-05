<?php

namespace Hamadou\Fundry\Services;

use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Transaction;
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\DTOs\TransferDTO;
use Hamadou\Fundry\Enums\TransactionType;
use Hamadou\Fundry\Enums\TransactionStatus;
use Hamadou\Fundry\Events\WalletCreated;
use Hamadou\Fundry\Events\TransactionCreated;
use Hamadou\Fundry\Exceptions\InsufficientFundsException;
use Hamadou\Fundry\Exceptions\ConcurrencyException;
use Hamadou\Fundry\Exceptions\UnauthorizedWalletException;
use Hamadou\Fundry\Exceptions\InvalidAmountException;
use Hamadou\Fundry\Exceptions\InvalidCurrencyException;
use Hamadou\Fundry\Contracts\WalletServiceInterface;
use Hamadou\Fundry\Traits\FundryHelpers;
use Hamadou\Fundry\Utils\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService implements WalletServiceInterface
{
    use FundryHelpers;

    public function createWalletForUser($user, array $data): Wallet
    {
        return DB::transaction(function () use ($user, $data) {
            $userId = is_object($user) ? $user->id : $user;

            if (!empty($data['is_default'])) {
                Wallet::where('user_id', $userId)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $wallet = Wallet::create(array_merge([
                'is_active' => true,
            ], $data, [
                'user_id' => $userId,
                'balance' => $data['balance'] ?? 0,
            ]));

            event(new WalletCreated($wallet));

            return $wallet;
        });
    }

    public function deposit(Wallet $wallet, float $amount): void
    {
        $this->validateAmount($amount);

        if ($wallet->is_active === false || $wallet->is_active === 0) {
            throw new InvalidAmountException('Impossible de déposer sur un portefeuille inactif');
        }

        if ($wallet->max_balance !== null && Money::comp(Money::add($wallet->balance, $amount), $wallet->max_balance) > 0) {
            throw new InvalidAmountException('Le dépôt dépasse la limite maximale du portefeuille');
        }

        try {
            $wallet->deposit($amount);
        } catch (\Exception $e) {
            throw new ConcurrencyException('Impossible de déposer les fonds : ' . $e->getMessage());
        }
    }

    public function withdraw(Wallet $wallet, float $amount): void
    {
        $this->validateAmount($amount);

        if (!$this->canWithdraw($wallet, $amount)) {
            throw new InsufficientFundsException('Fonds insuffisants ou limite dépassée');
        }

        try {
            $wallet->withdraw($amount);
        } catch (\Exception $e) {
            throw new ConcurrencyException('Impossible de retirer les fonds : ' . $e->getMessage());
        }
    }

    /**
     * Valide un montant
     */
    private function validateAmount(float $amount): void
    {
        $this->validateMonetaryAmount($amount);
    }

    public function canWithdraw(Wallet $wallet, float $amount): bool
    {
        return $wallet->canWithdraw($amount);
    }

    public function transfer(Wallet $fromWallet, Wallet $toWallet, float $amount, ?string $description = null): Transaction
    {
        $dto = new TransferDTO(
            userId: $fromWallet->user_id,
            fromWalletId: $fromWallet->id,
            toWalletId: $toWallet->id,
            amount: $amount,
            description: $description
        );

        return $this->transferWithDTO($dto);
    }

    public function transferWithDTO(TransferDTO $dto): Transaction
    {
        return DB::transaction(function () use ($dto) {
            if ($dto->fromWalletId === $dto->toWalletId) {
                throw new InvalidAmountException('Le portefeuille source et destination ne peuvent pas être identiques');
            }

            // Verrouiller les deux wallets par ordre croissant d'ID pour éviter tout deadlock SQL
            $firstId = min($dto->fromWalletId, $dto->toWalletId);
            $secondId = max($dto->fromWalletId, $dto->toWalletId);

            $firstWallet = Wallet::with('currency')->lockForUpdate()->findOrFail($firstId);
            $secondWallet = Wallet::with('currency')->lockForUpdate()->findOrFail($secondId);

            $fromWallet = $dto->fromWalletId === $firstId ? $firstWallet : $secondWallet;
            $toWallet = $dto->toWalletId === $firstId ? $firstWallet : $secondWallet;

            // Vérifier que le wallet source appartient à l'utilisateur
            if (!$fromWallet->belongsToUser($dto->userId)) {
                throw new UnauthorizedWalletException('Le portefeuille source ne vous appartient pas');
            }

            if ($toWallet->is_active === false || $toWallet->is_active === 0) {
                throw new InvalidAmountException('Le portefeuille destination est inactif');
            }

            // Valider le montant
            $this->validateAmount($dto->amount);

            // Calculer le montant total à débiter et le montant net à transférer
            $totalAmount = $dto->getTotalAmount();
            $netAmount = $dto->getNetAmount();
            $commissionAmount = $dto->getCommissionAmount();

            // Vérifier si le retrait est possible sur le wallet source
            if (!$fromWallet->canWithdraw($totalAmount)) {
                throw new InsufficientFundsException('Fonds insuffisants ou limites dépassées');
            }

            // Gestion des transferts inter-devises (Option A : conversion auto / Option B : blocage)
            $isCrossCurrency = (int) $fromWallet->currency_id !== (int) $toWallet->currency_id;
            $creditedAmount = $netAmount;
            $convertedAmount = null;
            $exchangeRate = 1.0;

            if ($isCrossCurrency) {
                $allowCrossCurrency = $dto->allowCrossCurrency ?? (bool) config('fundry.allow_cross_currency_transfers', true);

                if (!$allowCrossCurrency) {
                    throw new InvalidCurrencyException(
                        "Les transferts entre devises différentes ({$fromWallet->currency->iso_code} -> {$toWallet->currency->iso_code}) sont désactivés."
                    );
                }

                $creditedAmount = $fromWallet->currency->convertToSafe($netAmount, $toWallet->currency);
                $convertedAmount = $creditedAmount;
                $exchangeRate = $fromWallet->currency->getExchangeRateTo($toWallet->currency);
            }

            // Vérifier la limite max_balance du wallet destination si définie
            if ($toWallet->max_balance !== null && Money::comp(Money::add($toWallet->balance, $creditedAmount), $toWallet->max_balance) > 0) {
                throw new InvalidAmountException('Le transfert dépasse la limite maximale du portefeuille destination');
            }

            // Effectuer les opérations atomiques
            $fromWallet->withdraw($totalAmount);
            $toWallet->deposit($creditedAmount);

            // Créer la transaction
            $transaction = Transaction::create([
                'user_id' => $dto->userId,
                'from_wallet_id' => $dto->fromWalletId,
                'to_wallet_id' => $dto->toWalletId,
                'currency_id' => $fromWallet->currency_id,
                'type' => TransactionType::TRANSFER,
                'amount' => $netAmount,
                'converted_amount' => $convertedAmount,
                'exchange_rate' => $exchangeRate,
                'description' => $dto->description,
                'status' => TransactionStatus::COMPLETED,
                'completed_at' => now(),
                'metadata' => array_merge($dto->metadata ?? [], [
                    'commission_percentage' => $dto->commissionPercentage,
                    'commission_amount' => $commissionAmount,
                    'total_debited' => $totalAmount,
                    'gross_amount' => $dto->amount,
                    'credited_amount' => $creditedAmount,
                    'from_currency' => $fromWallet->currency->iso_code ?? null,
                    'to_currency' => $toWallet->currency->iso_code ?? null,
                    'cross_currency' => $isCrossCurrency,
                ]),
            ]);

            event(new TransactionCreated($transaction));

            return $transaction;
        });
    }

    public function getWalletBalance(Wallet $wallet): float
    {
        return (float) $wallet->balance;
    }

    public function getWalletHistory(Wallet $wallet, int $limit = 50)
    {
        return Transaction::where(function ($query) use ($wallet) {
            $query->where('from_wallet_id', $wallet->id)
                  ->orWhere('to_wallet_id', $wallet->id);
        })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function calculateTotalBalance($user, string $currencyCode = 'USD'): float
    {
        $userId = is_object($user) ? $user->id : $user;
        $normalizedCode = strtoupper($currencyCode);

        $wallets = Wallet::where('user_id', $userId)
            ->active()
            ->with('currency')
            ->get();

        $total = 0.0;
        $targetCurrency = Currency::where('iso_code', $normalizedCode)
            ->orWhere('code', $normalizedCode)
            ->first();

        if (!$targetCurrency) {
            throw new InvalidCurrencyException("Currency {$currencyCode} not found");
        }

        foreach ($wallets as $wallet) {
            if ($wallet->currency->iso_code === $normalizedCode || $wallet->currency->code === $normalizedCode) {
                $total = Money::add($total, $wallet->balance);
            } else {
                try {
                    $convertedAmount = $wallet->currency->convertToSafe($wallet->balance, $targetCurrency);
                    $total = Money::add($total, $convertedAmount);
                } catch (\Exception $e) {
                    Log::warning("Impossible de convertir {$wallet->currency->iso_code} vers {$normalizedCode}: " . $e->getMessage());
                }
            }
        }

        return $total;
    }
}
