<?php

namespace Hamadou\Fundry\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Hamadou\Fundry\Contracts\WalletServiceInterface;
use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Transaction;
use Hamadou\Fundry\Enums\WalletType;

trait HasWallets
{
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    public function createWallet(array $data): Wallet
    {
        return app(WalletServiceInterface::class)->createWalletForUser($this, array_merge([
            'is_active' => true,
        ], $data));
    }

    public function getWalletByCurrency(string $currencyCode): ?Wallet
    {
        $normalized = strtoupper($currencyCode);

        return $this->wallets()
            ->whereHas('currency', function ($query) use ($normalized) {
                $query->where('iso_code', $normalized)
                      ->orWhere('code', $normalized);
            })
            ->active()
            ->first();
    }

    public function getDefaultWallet(): ?Wallet
    {
        return $this->wallets()->active()->default()->first()
            ?? $this->wallets()->active()->first();
    }

    public function getWalletBalance(string $currencyCode): float
    {
        $wallet = $this->getWalletByCurrency($currencyCode);
        return $wallet ? (float) $wallet->balance : 0.0;
    }

    public function getTotalBalanceInUsd(): float
    {
        return (float) $this->wallets()
            ->active()
            ->with('currency')
            ->get()
            ->sum(fn($wallet) => $wallet->getBalanceInUsd());
    }

    public function hasSufficientBalance($amount, string $currencyCode): bool
    {
        $wallet = $this->getWalletByCurrency($currencyCode);
        return $wallet && $wallet->canWithdraw($amount);
    }

    public function getWalletsByType(WalletType $type)
    {
        return $this->wallets()->byType($type)->active()->get();
    }
}
