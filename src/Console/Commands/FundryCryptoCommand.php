<?php

namespace Hamadou\Fundry\Console\Commands;

use Illuminate\Console\Command;
use Hamadou\Fundry\Enums\CurrencyType;
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Transaction;

class FundryCryptoCommand extends Command
{
    protected $signature = 'fundry:crypto {action=list : Action à exécuter (list|stats)}';
    protected $description = 'Opérations et statistiques sur les cryptomonnaies';

    public function handle(): int
    {
        $action = strtolower((string) $this->argument('action'));

        return match ($action) {
            'list' => $this->listCryptoCurrencies(),
            'stats' => $this->showStats(),
            default => $this->invalidAction($action),
        };
    }

    private function listCryptoCurrencies(): int
    {
        $cryptos = Currency::crypto()->orderBy('iso_code')->get();

        if ($cryptos->isEmpty()) {
            $this->warn('Aucune cryptomonnaie enregistrée.');
            return self::SUCCESS;
        }

        $this->table(
            ['Ticker', 'Nom', 'Symbole', 'Valeur USD', 'Décimales', 'Active'],
            $cryptos->map(fn (Currency $c) => [
                $c->iso_code,
                $c->name,
                $c->symbol,
                (float) $c->exchange_rate,
                $c->decimals,
                $c->is_active ? 'Oui' : 'Non',
            ])->toArray()
        );

        return self::SUCCESS;
    }

    private function showStats(): int
    {
        $cryptoCount = Currency::crypto()->active()->count();
        $cryptoWalletsCount = Wallet::whereHas('currency', fn ($q) => $q->where('type', CurrencyType::CRYPTO))->count();
        $cryptoTxCount = Transaction::whereHas('currency', fn ($q) => $q->where('type', CurrencyType::CRYPTO))->count();

        $this->info('₿ Statistiques Crypto Fundry');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Cryptomonnaies actives', $cryptoCount],
                ['Portefeuilles Crypto', $cryptoWalletsCount],
                ['Transactions Crypto', $cryptoTxCount],
            ]
        );

        return self::SUCCESS;
    }

    private function invalidAction(string $action): int
    {
        $this->error("Action invalide '{$action}'. Actions supportées : list, stats");
        return self::INVALID;
    }
}
