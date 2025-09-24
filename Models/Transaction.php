<?php

namespace Hamadou\Fundry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Hamadou\Fundry\Enums\TransactionType;
use Hamadou\Fundry\Enums\TransactionStatus;

class Transaction extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'user_id',
        'from_wallet_id',
        'to_wallet_id',
        'currency_id',
        'type',
        'status',
        'amount',
        'converted_amount',
        'exchange_rate',
        'reference',
        'description',
        'metadata',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'amount' => 'decimal:8',
        'converted_amount' => 'decimal:8',
        'exchange_rate' => 'decimal:10',
        'metadata' => 'array',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->reference)) {
                $model->reference = 'TXN' . now()->format('YmdHis') . rand(1000, 9999);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function fromWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', TransactionStatus::COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', TransactionStatus::PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', TransactionStatus::FAILED);
    }

    public function scopeByType($query, TransactionType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Méthodes métier
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => TransactionStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => TransactionStatus::FAILED,
            'failed_at' => now(),
            'description' => $reason ?: $this->description,
        ]);
    }

    public function isPositive(): bool
    {
        return $this->type->isPositive();
    }

    public function getFormattedAmount(): string
    {
        $sign = $this->isPositive() ? '+' : '-';
        return $sign . $this->currency->getFormattedAmount(abs($this->amount));
    }
}