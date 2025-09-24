<?php

namespace Hamadou\Fundry\Enums;

enum WalletType: string
{
    case PERSONAL = 'personal';
    case BUSINESS = 'business';
    case SAVINGS = 'savings';
    case INVESTMENT = 'investment';
    case GOVERNMENT = 'government';

    public function label(): string
    {
        return match($this) {
            self::PERSONAL => 'Portefeuille Personnel',
            self::BUSINESS => 'Portefeuille Professionnel',
            self::SAVINGS => 'Épargne',
            self::INVESTMENT => 'Investissement',
            self::GOVERNMENT => 'Gouvernemental',
        };
    }

    public function maxBalance(): float
    {
        return match($this) {
            self::PERSONAL => 100000.00,
            self::BUSINESS => 1000000.00,
            self::SAVINGS => 500000.00,
            self::INVESTMENT => 2000000.00,
            self::GOVERNMENT => 5000000.00,
        };
    }

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}