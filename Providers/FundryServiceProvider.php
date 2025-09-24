<?php

namespace Hamadou\Fundry\Providers;

use Illuminate\Support\ServiceProvider;
use Hamadou\Fundry\Fundry;
use Hamadou\Fundry\Services\CurrencyService;
use Hamadou\Fundry\Services\WalletService;
use Hamadou\Fundry\Services\TransactionService;
use Hamadou\Fundry\Console\Commands\FundryReportCommand;
use Hamadou\Fundry\Console\Commands\FundryCurrenciesCommand;
use Hamadou\Fundry\Console\Commands\FundryCashCommand;
use Hamadou\Fundry\Console\Commands\FundryCryptoCommand;
use Hamadou\Fundry\Console\Commands\FundryInstallCommand;

class FundryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/fundry.php', 'fundry'
        );

        $this->app->singleton('fundry', function ($app) {
            return new Fundry(
                $app->make(CurrencyService::class),
                $app->make(WalletService::class),
                $app->make(TransactionService::class)
            );
        });

        $this->app->bind(CurrencyService::class);
        $this->app->bind(WalletService::class);
        $this->app->bind(TransactionService::class);
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            // Publier la configuration
            $this->publishes([
                __DIR__.'/../config/fundry.php' => config_path('fundry.php'),
            ], ['fundry', 'fundry-config']);

            // Publier les migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], ['fundry', 'fundry-migrations']);

            // Enregistrer les commandes
            $this->commands([
                FundryInstallCommand::class,
                FundryCurrenciesCommand::class,
                FundryCashCommand::class,
                FundryCryptoCommand::class,
                FundryReportCommand::class,
            ]);
        }

        // Charger les migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}