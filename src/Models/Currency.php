<?php

namespace Hamadou\Fundry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Hamadou\Fundry\Enums\CurrencyType;

class Currency extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'symbol',
        'exchange_rate',
        'base_currency',
        'is_active',
        'decimals',
        'icon',
        'description',
        'metadata',
    ];

    protected $casts = [
        'type' => CurrencyType::class,
        'exchange_rate' => 'decimal:10',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'decimals' => 'integer',
    ];

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes
    public function scopeFiat($query)
    {
        return $query->where('type', CurrencyType::FIAT);
    }

    public function scopeCrypto($query)
    {
        return $query->where('type', CurrencyType::CRYPTO);
    }

    public function scopeDevice($query)
    {
        return $query->where('type', CurrencyType::DEVICE);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Méthodes de conversion
    public function convertTo($amount, Currency $targetCurrency): float
    {
        if ($this->id === $targetCurrency->id) {
            return $amount;
        }

        $amountInUsd = $amount * $this->exchange_rate;
        return $amountInUsd / $targetCurrency->exchange_rate;
    }

    public function getValueInUsd($amount): float
    {
        return $amount * $this->exchange_rate;
    }

    public function getValueInEur($amount): float
    {
        $eur = self::where('code', 'EUR')->first();
        return $this->convertTo($amount, $eur);
    }

    public function getFormattedAmount($amount): string
    {
        return number_format($amount, $this->decimals) . ' ' . $this->symbol;
    }
}