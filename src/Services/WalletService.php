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
use Hamadou\Fundry\Contracts\WalletServiceInterface;
use Illuminate\Support\Facades\DB;

class WalletService implements WalletServiceInterface
{
    public function createWalletForUser($user, array $data): Wallet
    {
        return DB::transaction(function () use ($user, $data) {
            $wallet = Wallet::create(array_merge($data, [
                'user_id' => $user->id,
                'balance' => $data['balance'] ?? 0
            ]));

            event(new WalletCreated($wallet));

            return $wallet;
        });
    }

    public function deposit(Wallet $wallet, float $amount): void
    {
        // Valider le montant
        $this->validateAmount($amount);

        // Vérifier la limite max_balance si définie
        if ($wallet->max_balance !== null && ($wallet->balance + $amount) > $wallet->max_balance) {
            throw new InvalidAmountException('Le dépôt dépasse la limite maximale du portefeuille');
        }

        try {
            $wallet->deposit($amount);
            $wallet->save();
        } catch (\Exception $e) {
            throw new ConcurrencyException('Impossible de déposer les fonds : ' . $e->getMessage());
        }
    }

    public function withdraw(Wallet $wallet, float $amount): void
    {
        // Valider le montant
        $this->validateAmount($amount);

        if (!$this->canWithdraw($wallet, $amount)) {
            throw new InsufficientFundsException('Fonds insuffisants ou limite dépassée');
        }

        try {
            $wallet->withdraw($amount);
            $wallet->save();
        } catch (\Exception $e) {
            throw new ConcurrencyException('Impossible de retirer les fonds : ' . $e->getMessage());
        }
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

    public function canWithdraw(Wallet $wallet, float $amount): bool
    {
        return $wallet->canWithdraw($amount);
    }

    public function transfer(Wallet $fromWallet, Wallet $toWallet, float $amount, ?string $description = null): Transaction
    {
        // Créer un DTO depuis les paramètres pour compatibilité ascendante
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
            // Verrouiller les deux wallets pour éviter les race conditions
            $fromWallet = Wallet::lockForUpdate()->findOrFail($dto->fromWalletId);
            $toWallet = Wallet::lockForUpdate()->findOrFail($dto->toWalletId);

            // Vérifier que le wallet source appartient à l'utilisateur
            if ($fromWallet->user_id != $dto->userId) {
                throw new UnauthorizedWalletException('Le portefeuille source ne vous appartient pas');
            }

            // Valider le montant
            $this->validateAmount($dto->amount);

            // Vérifier que les wallets sont différents
            if ($fromWallet->id === $toWallet->id) {
                throw new InvalidAmountException('Le portefeuille source et destination ne peuvent pas être identiques');
            }

            // Calculer le montant total à débiter et le montant net à créditer
            $totalAmount = $dto->getTotalAmount();
            $netAmount = $dto->getNetAmount();
            $commissionAmount = $dto->getCommissionAmount();

            // Vérifier si le transfert est possible
            if (!$fromWallet->canWithdraw($totalAmount)) {
                throw new InsufficientFundsException('Fonds insuffisants ou limites dépassées');
            }

            // Vérifier la limite max_balance du wallet destination si définie
            if ($toWallet->max_balance !== null && ($toWallet->balance + $netAmount) > $toWallet->max_balance) {
                throw new InvalidAmountException('Le transfert dépasse la limite maximale du portefeuille destination');
            }

            // Effectuer les opérations
            $fromWallet->withdraw($totalAmount);
            $toWallet->deposit($netAmount);
            
            $fromWallet->save();
            $toWallet->save();

            // Créer la transaction
            $transaction = Transaction::create([
                'user_id' => $dto->userId,
                'from_wallet_id' => $dto->fromWalletId,
                'to_wallet_id' => $dto->toWalletId,
                'currency_id' => $fromWallet->currency_id,
                'type' => TransactionType::TRANSFER,
                'amount' => $netAmount, // Montant net transféré
                'description' => $dto->description,
                'status' => TransactionStatus::COMPLETED,
                'completed_at' => now(),
                'metadata' => array_merge($dto->metadata ?? [], [
                    'commission_percentage' => $dto->commissionPercentage,
                    'commission_amount' => $commissionAmount,
                    'total_debited' => $totalAmount,
                    'gross_amount' => $dto->amount,
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
        return Transaction::where('from_wallet_id', $wallet->id)
            ->orWhere('to_wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function calculateTotalBalance($user, string $currencyCode = 'USD'): float
    {
        $wallets = Wallet::where('user_id', $user->id)
            ->with('currency')
            ->get();
        
        $total = 0;
        $targetCurrency = Currency::where('iso_code', strtoupper($currencyCode))
            ->orWhere('code', strtoupper($currencyCode))
            ->first();

        if (!$targetCurrency) {
            throw new \RuntimeException("Currency {$currencyCode} not found");
        }

        foreach ($wallets as $wallet) {
            if ($wallet->currency->iso_code === $currencyCode || $wallet->currency->code === $currencyCode) {
                $total += (float) $wallet->balance;
            } else {
                // Convert to target currency avec validation
                try {
                    $convertedAmount = $wallet->currency->convertToSafe($wallet->balance, $targetCurrency);
                    $total += $convertedAmount;
                } catch (\Exception $e) {
                    // Logger l'erreur mais continuer avec les autres wallets
                    Log::warning("Impossible de convertir {$wallet->currency->iso_code} vers {$currencyCode}: " . $e->getMessage());
                }
            }
        }

        return $total;
    }
}
