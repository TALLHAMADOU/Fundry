<?php

namespace Hamadou\Fundry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Hamadou\Fundry\Enums\CurrencyType;
use Hamadou\Fundry\Exceptions\InvalidCurrencyException;
use Hamadou\Fundry\Utils\Money;

class Currency extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'iso_code',
        'country_id',
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
        'base_currency' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'decimals' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($currency) {
            if (isset($currency->iso_code)) {
                $currency->iso_code = strtoupper($currency->iso_code);
            }
            if (isset($currency->code)) {
                $currency->code = strtoupper($currency->code);
            }
            if (empty($currency->iso_code) && !empty($currency->code)) {
                $currency->iso_code = $currency->code;
            }

            $type = $currency->type instanceof CurrencyType
                ? $currency->type
                : CurrencyType::tryFrom((string) $currency->type);

            // Valider le code ISO 4217 pour les devises fiat
            if ($type === CurrencyType::FIAT && isset($currency->iso_code)) {
                if (!self::isValidIso4217Code($currency->iso_code)) {
                    throw new InvalidCurrencyException("Le code ISO 4217 '{$currency->iso_code}' n'est pas valide");
                }
            }
        });

        static::updating(function ($currency) {
            if ($currency->isDirty('iso_code')) {
                $currency->iso_code = strtoupper($currency->iso_code);
            }
            if ($currency->isDirty('code')) {
                $currency->code = strtoupper($currency->code);
            }
        });

        static::saved(function ($currency) {
            if (config('fundry.cache.enabled', true)) {
                $prefix = config('fundry.cache.prefix', 'fundry');
                $code = strtoupper($currency->iso_code ?? $currency->code ?? '');
                if ($code !== '') {
                    \Illuminate\Support\Facades\Cache::forget("{$prefix}.currency.{$code}");
                }
                \Illuminate\Support\Facades\Cache::forget("{$prefix}.currencies.supported");
                $typeValue = $currency->type instanceof CurrencyType ? $currency->type->value : (string) $currency->type;
                \Illuminate\Support\Facades\Cache::forget("{$prefix}.currencies.type.{$typeValue}");
            }
        });
    }

    /**
     * Relation avec le pays
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

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

    /**
     * Calcule le taux de change relatif vers une devise cible
     */
    public function getExchangeRateTo(Currency $targetCurrency): float
    {
        if ($this->id === $targetCurrency->id) {
            return 1.0;
        }

        return Money::div($this->exchange_rate, $targetCurrency->exchange_rate, 10);
    }

    // Méthodes de conversion
    public function convertTo($amount, Currency $targetCurrency): float
    {
        if ($this->id === $targetCurrency->id) {
            return (float) $amount;
        }

        $amountInUsd = Money::mul($amount, $this->exchange_rate, 10);
        return Money::div($amountInUsd, $targetCurrency->exchange_rate, 10);
    }

    public function getValueInUsd($amount): float
    {
        return Money::mul($amount, $this->exchange_rate, 8);
    }

    public function getValueInEur($amount): float
    {
        $eur = self::where('iso_code', 'EUR')->orWhere('code', 'EUR')->first();
        if (!$eur) {
            throw new InvalidCurrencyException("La devise EUR n'est pas configurée");
        }

        return $this->convertTo($amount, $eur);
    }

    public function getFormattedAmount($amount): string
    {
        return number_format((float) $amount, $this->decimals ?? 2) . ' ' . $this->symbol;
    }

    /**
     * Vérifie si le code ISO 4217 est valide
     */
    public static function isValidIso4217Code(string $code): bool
    {
        return preg_match('/^[A-Z]{3}$/', strtoupper($code)) === 1;
    }

    /**
     * Récupère une devise par son code ISO 4217 (avec cache)
     */
    public static function findByIsoCode(string $isoCode): ?self
    {
        $normalized = strtoupper($isoCode);

        if (!config('fundry.cache.enabled', true)) {
            return self::where('iso_code', $normalized)->orWhere('code', $normalized)->first();
        }

        $cacheKey = config('fundry.cache.prefix', 'fundry') . '.currency.' . $normalized;
        $ttl = config('fundry.cache.ttl', 3600);

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, $ttl, function () use ($normalized) {
            return self::where('iso_code', $normalized)->orWhere('code', $normalized)->first();
        });
    }

    /**
     * Vérifie si la conversion est possible entre deux devises
     */
    public function canConvertTo(Currency $targetCurrency): bool
    {
        if (!$this->is_active || !$targetCurrency->is_active) {
            return false;
        }

        if ((float) $this->exchange_rate <= 0 || (float) $targetCurrency->exchange_rate <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Convertit un montant vers une autre devise avec validation
     */
    public function convertToSafe($amount, Currency $targetCurrency): float
    {
        if (!$this->canConvertTo($targetCurrency)) {
            throw new InvalidCurrencyException(
                "Impossible de convertir de {$this->iso_code} vers {$targetCurrency->iso_code}. " .
                "Vérifiez que les devises sont actives et que les taux de change sont valides."
            );
        }

        return $this->convertTo($amount, $targetCurrency);
    }
}
