<?php

namespace Hamadou\Fundry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Hamadou\Fundry\Enums\WalletType;

class Wallet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'currency_id',
        'name',
        'type',
        'balance',
        'max_balance',
        'min_balance',
        'transaction_limit',
        'is_active',
        'is_default',
        'security_rules',
    ];

    protected $casts = [
        'type' => WalletType::class,
        'balance' => 'decimal:8',
        'max_balance' => 'decimal:8',
        'min_balance' => 'decimal:8',
        'transaction_limit' => 'decimal:8',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'security_rules' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function fromTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_wallet_id');
    }

    public function toTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'to_wallet_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByType($query, WalletType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeHasBalance($query, $amount)
    {
        return $query->where('balance', '>=', $amount);
    }

    // Méthodes métier
    public function canWithdraw($amount): bool
    {
        return $this->balance >= $amount && 
               $this->is_active &&
               ($this->min_balance === null || ($this->balance - $amount) >= $this->min_balance) &&
               ($this->transaction_limit === null || $amount <= $this->transaction_limit);
    }

    public function deposit($amount): void
    {
        $this->increment('balance', $amount);
    }

    public function withdraw($amount): void
    {
        $this->decrement('balance', $amount);
    }

    public function getBalanceInUsd(): float
    {
        return $this->currency->getValueInUsd($this->balance);
    }

    public function getFormattedBalance(): string
    {
        return $this->currency->getFormattedAmount($this->balance);
    }

    /**
     * Vérifie si le wallet appartient à un utilisateur
     */
    public function belongsToUser($user): bool
    {
        $userId = is_object($user) ? $user->id : $user;
        return $this->user_id == $userId;
    }
}