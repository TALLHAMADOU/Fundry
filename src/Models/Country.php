<?php

namespace Hamadou\Fundry\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'name_en',
        'iso_code',
        'iso_code_3',
        'numeric_code',
        'phone_code',
        'continent',
        'capital',
        'currency_code',
        'currency_name',
        'currency_symbol',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Relation avec les currencies
     */
    public function currencies(): HasMany
    {
        return $this->hasMany(Currency::class, 'country_id');
    }

    /**
     * Scope pour les pays actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour rechercher par code ISO
     */
    public function scopeByIsoCode($query, string $isoCode)
    {
        return $query->where('iso_code', strtoupper($isoCode));
    }

    /**
     * Scope pour rechercher par code devise
     */
    public function scopeByCurrencyCode($query, string $currencyCode)
    {
        return $query->where('currency_code', strtoupper($currencyCode));
    }

    /**
     * Vérifie si le code ISO est valide (ISO 3166-1 alpha-2)
     */
    public static function isValidIsoCode(string $isoCode): bool
    {
        return preg_match('/^[A-Z]{2}$/', strtoupper($isoCode)) === 1;
    }

    /**
     * Vérifie si le code devise est valide (ISO 4217)
     */
    public static function isValidCurrencyCode(string $currencyCode): bool
    {
        return preg_match('/^[A-Z]{3}$/', strtoupper($currencyCode)) === 1;
    }

    /**
     * Récupère le pays par code ISO
     */
    public static function findByIsoCode(string $isoCode): ?self
    {
        return self::byIsoCode($isoCode)->first();
    }

    /**
     * Récupère le pays par code devise
     */
    public static function findByCurrencyCode(string $currencyCode): ?self
    {
        return self::byCurrencyCode($currencyCode)->first();
    }
}
