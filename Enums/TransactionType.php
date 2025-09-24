<?php

namespace Hamadou\Fundry\Enums;

enum TransactionType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case TRANSFER = 'transfer';
    case EXCHANGE = 'exchange';
    case FEE = 'fee';
    case REFUND = 'refund';

    public function label(): string
    {
        return match($this) {
            self::DEPOSIT => 'Dépôt',
            self::WITHDRAWAL => 'Retrait',
            self::TRANSFER => 'Transfert',
            self::EXCHANGE => 'Échange',
            self::FEE => 'Frais',
            self::REFUND => 'Remboursement',
        };
    }

    public function isPositive(): bool
    {
        return in_array($this, [self::DEPOSIT, self::REFUND]);
    }

    public function isNegative(): bool
    {
        return in_array($this, [self::WITHDRAWAL, self::FEE]);
    }

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}