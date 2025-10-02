<?php

namespace Hamadou\Fundry\Contracts;

use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Transaction;

interface WalletServiceInterface
{
    /**
     * Crée un portefeuille pour un utilisateur.
     *
     * @param mixed $user
     * @param array $data
     * @return Wallet
     */
    public function createWalletForUser($user, array $data): Wallet;

    /**
     * Effectue un transfert entre deux portefeuilles.
     *
     * @param Wallet $fromWallet
     * @param Wallet $toWallet
     * @param float $amount
     * @param string|null $description
     * @return Transaction
     */
    public function transfer(Wallet $fromWallet, Wallet $toWallet, float $amount, string $description = null): Transaction;

    /**
     * Retourne le solde actuel d'un portefeuille.
     *
     * @param Wallet $wallet
     * @return float
     */
    public function getWalletBalance(Wallet $wallet): float;

    /**
     * Récupère l'historique des transactions d'un portefeuille.
     *
     * @param Wallet $wallet
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getWalletHistory(Wallet $wallet, int $limit = 50);

    /**
     * Calcule le solde total d'un utilisateur dans une devise donnée.
     *
     * @param mixed $user
     * @param string $currencyCode
     * @return float
     */
    public function calculateTotalBalance($user, string $currencyCode = 'USD'): float;
}
