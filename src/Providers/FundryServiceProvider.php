<?php

namespace Hamadou\Fundry\Providers;

use Illuminate\Support\ServiceProvider;
use Hamadou\Fundry\Console\Commands\FundryReportCommand;
use Hamadou\Fundry\Console\Commands\FundryCurrenciesCommand;
use Hamadou\Fundry\Console\Commands\FundryCashCommand;
use Hamadou\Fundry\Console\Commands\FundryCryptoCommand;
use Hamadou\Fundry\Console\Commands\FundryInstallCommand;
use Hamadou\Fundry\Console\Commands\FundrySyncCountriesCommand;
use Hamadou\Fundry\Console\Commands\FundryValidateCurrenciesCommand;
use Hamadou\Fundry\Console\Commands\FundryUpdateRatesCommand;

class FundryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/fundry.php',
            'fundry'
        );

        // Bind interfaces to implementations
        $this->app->bind(\Hamadou\Fundry\Contracts\CurrencyServiceInterface::class, \Hamadou\Fundry\Services\CurrencyService::class);
        $this->app->bind(\Hamadou\Fundry\Contracts\WalletServiceInterface::class, \Hamadou\Fundry\Services\WalletService::class);
        $this->app->bind(\Hamadou\Fundry\Contracts\TransactionServiceInterface::class, \Hamadou\Fundry\Services\TransactionService::class);

        // Register Fundry as singleton
        $this->app->singleton(\Hamadou\Fundry\Fundry::class, function ($app) {
            return new \Hamadou\Fundry\Fundry(
                $app->make(\Hamadou\Fundry\Contracts\WalletServiceInterface::class),
                $app->make(\Hamadou\Fundry\Contracts\TransactionServiceInterface::class),
                $app->make(\Hamadou\Fundry\Contracts\CurrencyServiceInterface::class)
            );
        });

        $this->app->alias(\Hamadou\Fundry\Fundry::class, 'fundry');
    }

    public function boot(): void
    {
        $migrationsPath = __DIR__ . '/../../database/migrations';

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/fundry.php' => config_path('fundry.php'),
            ], ['fundry', 'fundry-config']);

            $this->publishes([
                "{$migrationsPath}/create_countries_table.php" => database_path('migrations/2024_01_01_000001_create_countries_table.php'),
                "{$migrationsPath}/create_currencies_table.php" => database_path('migrations/2024_01_01_000002_create_currencies_table.php'),
                "{$migrationsPath}/create_wallets_table.php" => database_path('migrations/2024_01_01_000003_create_wallets_table.php'),
                "{$migrationsPath}/create_transactions_table.php" => database_path('migrations/2024_01_01_000004_create_transactions_table.php'),
            ], ['fundry', 'fundry-migrations']);

            $this->commands([
                FundryInstallCommand::class,
                FundryCurrenciesCommand::class,
                FundryCashCommand::class,
                FundryCryptoCommand::class,
                FundryReportCommand::class,
                FundrySyncCountriesCommand::class,
                FundryValidateCurrenciesCommand::class,
                FundryUpdateRatesCommand::class,
            ]);
        }

        // Load migrations in strict foreign-key dependency order
        $this->loadMigrationsFrom([
            "{$migrationsPath}/create_countries_table.php",
            "{$migrationsPath}/create_currencies_table.php",
            "{$migrationsPath}/create_wallets_table.php",
            "{$migrationsPath}/create_transactions_table.php",
        ]);

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'fundry');
    }
}
