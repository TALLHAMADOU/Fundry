<?php

namespace Hamadou\Fundry\Contracts;

use Hamadou\Fundry\Models\Transaction;
use Hamadou\Fundry\Models\Wallet;

interface TransactionServiceInterface
{
    /**
     * Crée une transaction à partir d'un tableau de données.
     *
     * @param array $data
     * @return Transaction
     */
    public function createTransaction(array $data): Transaction;

    /**
     * Traite un dépôt sur un portefeuille.
     *
     * @param Wallet $wallet
     * @param float $amount
     * @param string|null $description
     * @return Transaction
     */
    public function processDeposit(Wallet $wallet, float $amount, string $description = null): Transaction;

    /**
     * Traite un retrait depuis un portefeuille.
     *
     * @param Wallet $wallet
     * @param float $amount
     * @param string|null $description
     * @return Transaction
     */
    public function processWithdrawal(Wallet $wallet, float $amount, string $description = null): Transaction;

    /**
     * Récupère une transaction par sa référence unique.
     *
     * @param string $reference
     * @return Transaction|null
     */
    public function getTransactionByReference(string $reference): ?Transaction;

    /**
     * Récupère les transactions d'un utilisateur avec filtres et limite.
     *
     * @param int|string $userId
     * @param array $filters
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getUserTransactions($userId, array $filters = [], int $limit = 50);

    /**
     * Calcule le volume des transactions d'un utilisateur pour aujourd'hui dans une devise donnée.
     *
     * @param int|string $userId
     * @param string $currencyCode
     * @return float
     */
    public function calculateDailyVolume($userId, string $currencyCode): float;
}
