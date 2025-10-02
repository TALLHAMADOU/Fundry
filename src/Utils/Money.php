<?php

namespace Hamadou\Fundry\Utils;

class Money
{
    /**
     * Convertit un montant (float) en cents (int).
     *
     * @param float|int|string $amount
     * @param int $precision
     * @return int
     */
    public static function toCents($amount, int $precision = 2): int
    {
        // Utilise string math pour éviter les imprécisions float
        $multiplier = 10 ** $precision;
        return (int) round((float) $amount * $multiplier);
    }

    /**
     * Convertit des cents (int) en montant décimal (float).
     *
     * @param int $cents
     * @param int $precision
     * @return float
     */
    public static function fromCents(int $cents, int $precision = 2): float
    {
        $divider = 10 ** $precision;
        return $cents / $divider;
    }

    /**
     * Formatte un montant (float) pour affichage en fonction de la precision.
     *
     * @param float|int $amount
     * @param int $precision
     * @return string
     */
    public static function format($amount, int $precision = 2): string
    {
        return number_format((float) $amount, $precision, '.', '');
    }
}
