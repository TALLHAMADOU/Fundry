<?php

namespace Hamadou\Fundry\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Transaction;
use Hamadou\Fundry\Enums\WalletType;

trait HasWallets
{
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function createWallet(array $data): Wallet
    {
        return $this->wallets()->create(array_merge([
            'is_active' => true,
        ], $data));
    }

    public function getWalletByCurrency(string $currencyCode): ?Wallet
    {
        return $this->wallets()
            ->whereHas('currency', function ($query) use ($currencyCode) {
                $query->where('code', $currencyCode);
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
        return $wallet ? $wallet->balance : 0;
    }

    public function getTotalBalanceInUsd(): float
    {
        return $this->wallets()
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