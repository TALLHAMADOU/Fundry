<?php

namespace Hamadou\Fundry\Utils;

class Money
{
    /**
     * Normalise un montant en chaîne décimale compatible bcmath.
     *
     * @param float|int|string $amount
     * @param int $scale
     * @return string
     */
    public static function normalize(float|int|string $amount, int $scale = 10): string
    {
        if (is_int($amount)) {
            return (string) $amount;
        }

        if (is_string($amount) && preg_match('/^-?\d+(\.\d+)?$/', trim($amount))) {
            return trim($amount);
        }

        return number_format((float) $amount, $scale, '.', '');
    }

    /**
     * Convertit un montant en cents (plus petite unité entière) via bcmath.
     *
     * @param float|int|string $amount
     * @param int $precision
     * @return int
     */
    public static function toCents(float|int|string $amount, int $precision = 2): int
    {
        $multiplier = bcpow('10', (string) $precision, 0);
        $normalized = self::normalize($amount, $precision + 4);
        $scaled = bcmul($normalized, $multiplier, 4);

        return (int) round((float) $scaled, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * Convertit des cents (int) en montant décimal (float) via bcmath.
     *
     * @param int $cents
     * @param int $precision
     * @return float
     */
    public static function fromCents(int $cents, int $precision = 2): float
    {
        $divider = bcpow('10', (string) $precision, 0);
        return (float) bcdiv((string) $cents, $divider, $precision);
    }

    /**
     * Additionne deux montants avec précision bcmath.
     */
    public static function add(float|int|string $a, float|int|string $b, int $scale = 8): float
    {
        return (float) bcadd(self::normalize($a, $scale + 2), self::normalize($b, $scale + 2), $scale);
    }

    /**
     * Soustrait deux montants avec précision bcmath.
     */
    public static function sub(float|int|string $a, float|int|string $b, int $scale = 8): float
    {
        return (float) bcsub(self::normalize($a, $scale + 2), self::normalize($b, $scale + 2), $scale);
    }

    /**
     * Multiplie deux montants avec précision bcmath.
     */
    public static function mul(float|int|string $a, float|int|string $b, int $scale = 8): float
    {
        return (float) bcmul(self::normalize($a, $scale + 4), self::normalize($b, $scale + 4), $scale);
    }

    /**
     * Divise deux montants avec précision bcmath.
     */
    public static function div(float|int|string $a, float|int|string $b, int $scale = 8): float
    {
        $divisor = self::normalize($b, $scale + 4);
        if (bccomp($divisor, '0', $scale + 4) === 0) {
            throw new \DivisionByZeroError('Division par zéro dans Money::div');
        }

        return round((float) bcdiv(self::normalize($a, $scale + 4), $divisor, $scale + 4), $scale);
    }

    /**
     * Compare deux montants avec précision bcmath (-1, 0, 1).
     */
    public static function comp(float|int|string $a, float|int|string $b, int $scale = 8): int
    {
        return bccomp(self::normalize($a, $scale), self::normalize($b, $scale), $scale);
    }

    /**
     * Vérifie qu'un montant ne dépasse pas le nombre maximal de décimales autorisées.
     */
    public static function hasValidScale(float|int|string $amount, int $maxDecimals = 8): bool
    {
        $formatted = sprintf('%.' . ($maxDecimals + 4) . 'F', abs((float) $amount));
        $parts = explode('.', $formatted);
        if (!isset($parts[1])) {
            return true;
        }

        $decimals = rtrim($parts[1], '0');
        return strlen($decimals) <= $maxDecimals;
    }

    /**
     * Formatte un montant (float) pour affichage en fonction de la précision.
     *
     * @param float|int|string $amount
     * @param int $precision
     * @return string
     */
    public static function format(float|int|string $amount, int $precision = 2): string
    {
        return number_format((float) $amount, $precision, '.', '');
    }
}
