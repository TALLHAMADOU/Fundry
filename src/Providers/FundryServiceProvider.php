<?php

namespace Hamadou\Fundry\Providers;

use Illuminate\Support\ServiceProvider;
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

    $this->app->singleton(\Hamadou\Fundry\Fundry::class, function ($app) {
        return new \Hamadou\Fundry\Fundry(
            $app->make(\Hamadou\Fundry\Contracts\CurrencyServiceInterface::class),
            $app->make(\Hamadou\Fundry\Contracts\WalletServiceInterface::class),
            $app->make(\Hamadou\Fundry\Contracts\TransactionServiceInterface::class)
        );
    });


    $this->app->alias(\Hamadou\Fundry\Fundry::class, 'fundry');
    $this->app->bind(\Hamadou\Fundry\Contracts\CurrencyServiceInterface::class, \Hamadou\Fundry\Services\CurrencyService::class);
    $this->app->bind(\Hamadou\Fundry\Contracts\WalletServiceInterface::class, \Hamadou\Fundry\Services\WalletService::class);
    $this->app->bind(\Hamadou\Fundry\Contracts\TransactionServiceInterface::class, \Hamadou\Fundry\Services\TransactionService::class);
      
}

    public function boot()
    {
        if ($this->app->runningInConsole()) {
           
           
            $this->publishes([
                __DIR__.'/../config/fundry.php' => config_path('fundry.php'),
            ], ['fundry', 'fundry-config']);

          
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], ['fundry', 'fundry-migrations']);

           
           
            $this->commands([
                FundryInstallCommand::class,
                FundryCurrenciesCommand::class,
                FundryCashCommand::class,
                FundryCryptoCommand::class,
                FundryReportCommand::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}