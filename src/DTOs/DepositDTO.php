<?php

namespace Hamadou\Fundry\DTOs;

class DepositDTO
{
    public function __construct(
        public readonly int|string $userId,
        public readonly int $walletId,
        public readonly float $amount,
        public readonly ?string $description = null,
        public readonly ?float $commissionPercentage = null,
        public readonly ?array $metadata = null
    ) {
        $this->validate();
    }

    /**
     * Valide les données du DTO
     */
    private function validate(): void
    {
        if ($this->amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être supérieur à zéro');
        }

        if ($this->commissionPercentage !== null && ($this->commissionPercentage < 0 || $this->commissionPercentage > 100)) {
            throw new \InvalidArgumentException('Le pourcentage de commission doit être entre 0 et 100');
        }
    }

    /**
     * Calcule le montant net après commission
     */
    public function getNetAmount(): float
    {
        if ($this->commissionPercentage === null || $this->commissionPercentage === 0) {
            return $this->amount;
        }

        return $this->amount * (1 - $this->commissionPercentage / 100);
    }

    /**
     * Calcule le montant de la commission
     */
    public function getCommissionAmount(): float
    {
        if ($this->commissionPercentage === null || $this->commissionPercentage === 0) {
            return 0;
        }

        return $this->amount * ($this->commissionPercentage / 100);
    }

    /**
     * Crée une instance depuis un tableau
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'] ?? $data['userId'],
            walletId: $data['wallet_id'] ?? $data['walletId'],
            amount: (float) $data['amount'],
            description: $data['description'] ?? null,
            commissionPercentage: isset($data['commission_percentage']) || isset($data['commissionPercentage']) 
                ? (float) ($data['commission_percentage'] ?? $data['commissionPercentage']) 
                : null,
            metadata: $data['metadata'] ?? null
        );
    }

    /**
     * Convertit en tableau
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'wallet_id' => $this->walletId,
            'amount' => $this->amount,
            'description' => $this->description,
            'commission_percentage' => $this->commissionPercentage,
            'commission_amount' => $this->getCommissionAmount(),
            'net_amount' => $this->getNetAmount(),
            'metadata' => $this->metadata,
        ];
    }
}
