<?php

namespace Hamadou\Fundry\Enums;

enum CurrencyType: string
{
    case FIAT = 'fiat';
    case CRYPTO = 'crypto';
    case DEVICE = 'device';

    public function label(): string
    {
        return match($this) {
            self::FIAT => 'Monnaie Fiat',
            self::CRYPTO => 'Cryptomonnaie',
            self::DEVICE => 'Device Gouvernemental',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::FIAT => '💵',
            self::CRYPTO => '₿',
            self::DEVICE => '🆔',
        };
    }

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}