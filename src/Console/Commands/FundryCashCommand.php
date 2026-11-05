<?php

namespace Hamadou\Fundry\Console\Commands;

use Illuminate\Console\Command;
use Hamadou\Fundry\Enums\CurrencyType;
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Transaction;

class FundryCashCommand extends Command
{
    protected $signature = 'fundry:cash {action=stats : Action à exécuter (stats|list)}';
    protected $description = 'Opérations et statistiques sur les devises fiduciaires (fiat / cash)';

    public function handle(): int
    {
        $action = strtolower((string) $this->argument('action'));

        return match ($action) {
            'stats' => $this->showStats(),
            'list' => $this->listFiatCurrencies(),
            default => $this->invalidAction($action),
        };
    }

    private function showStats(): int
    {
        $fiatCurrenciesCount = Currency::fiat()->active()->count();
        $fiatWalletsCount = Wallet::whereHas('currency', fn ($q) => $q->where('type', CurrencyType::FIAT))->count();
        $fiatTransactionsCount = Transaction::whereHas('currency', fn ($q) => $q->where('type', CurrencyType::FIAT))->count();

        $this->info('💵 Statistiques Cash / Fiat Fundry');
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Devises Fiat actives', $fiatCurrenciesCount],
                ['Portefeuilles Fiat', $fiatWalletsCount],
                ['Transactions Fiat', $fiatTransactionsCount],
            ]
        );

        return self::SUCCESS;
    }

    private function listFiatCurrencies(): int
    {
        $currencies = Currency::fiat()->orderBy('iso_code')->get();

        if ($currencies->isEmpty()) {
            $this->warn('Aucune devise fiat enregistrée.');
            return self::SUCCESS;
        }

        $this->table(
            ['Code ISO', 'Nom', 'Symbole', 'Taux (USD)', 'Active'],
            $currencies->map(fn (Currency $c) => [
                $c->iso_code,
                $c->name,
                $c->symbol,
                (float) $c->exchange_rate,
                $c->is_active ? 'Oui' : 'Non',
            ])->toArray()
        );

        return self::SUCCESS;
    }

    private function invalidAction(string $action): int
    {
        $this->error("Action invalide '{$action}'. Actions supportées : stats, list");
        return self::INVALID;
    }
}
