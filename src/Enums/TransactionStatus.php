<?php

namespace Hamadou\Fundry\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REVERSED = 'reversed';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::COMPLETED => 'Complétée',
            self::FAILED => 'Échouée',
            self::CANCELLED => 'Annulée',
            self::REVERSED => 'Inversée',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED]);
    }

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}