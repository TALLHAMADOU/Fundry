<?php

namespace Hamadou\Fundry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Hamadou\Fundry\Enums\CurrencyType;
use Hamadou\Fundry\Exceptions\InvalidCurrencyException;

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
        'is_active' => 'boolean',
        'metadata' => 'array',
        'decimals' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($currency) {
            // Normaliser le code ISO en majuscules
            if (isset($currency->iso_code)) {
                $currency->iso_code = strtoupper($currency->iso_code);
            }
            if (isset($currency->code)) {
                $currency->code = strtoupper($currency->code);
            }

            // Valider le code ISO 4217 pour les devises fiat
            if ($currency->type === CurrencyType::FIAT && isset($currency->iso_code)) {
                if (!self::isValidIso4217Code($currency->iso_code)) {
                    throw new InvalidCurrencyException("Le code ISO 4217 '{$currency->iso_code}' n'est pas valide");
                }
            }
        });

        static::updating(function ($currency) {
            // Normaliser le code ISO en majuscules
            if ($currency->isDirty('iso_code')) {
                $currency->iso_code = strtoupper($currency->iso_code);
            }
            if ($currency->isDirty('code')) {
                $currency->code = strtoupper($currency->code);
            }
        });

        static::saved(function ($currency) {
            // Nettoyer le cache après sauvegarde
            if (config('fundry.cache.enabled', true)) {
                $prefix = config('fundry.cache.prefix', 'fundry');
                \Illuminate\Support\Facades\Cache::forget("{$prefix}.currency." . strtoupper($currency->iso_code ?? $currency->code));
                \Illuminate\Support\Facades\Cache::forget("{$prefix}.currencies.supported");
                \Illuminate\Support\Facades\Cache::forget("{$prefix}.currencies.type.{$currency->type->value}");
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

    /**
     * Vérifie si le code ISO 4217 est valide
     */
    public static function isValidIso4217Code(string $code): bool
    {
        // Format ISO 4217 : 3 lettres majuscules
        return preg_match('/^[A-Z]{3}$/', strtoupper($code)) === 1;
    }

    /**
     * Récupère une devise par son code ISO 4217 (avec cache)
     */
    public static function findByIsoCode(string $isoCode): ?self
    {
        if (!config('fundry.cache.enabled', true)) {
            return self::where('iso_code', strtoupper($isoCode))->first();
        }

        $cacheKey = config('fundry.cache.prefix', 'fundry') . '.currency.' . strtoupper($isoCode);
        $ttl = config('fundry.cache.ttl', 3600);

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, $ttl, function () use ($isoCode) {
            return self::where('iso_code', strtoupper($isoCode))->first();
        });
    }

    /**
     * Vérifie si la conversion est possible entre deux devises
     */
    public function canConvertTo(Currency $targetCurrency): bool
    {
        // Vérifier que les deux devises sont actives
        if (!$this->is_active || !$targetCurrency->is_active) {
            return false;
        }

        // Vérifier que les taux de change sont valides
        if ($this->exchange_rate <= 0 || $targetCurrency->exchange_rate <= 0) {
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