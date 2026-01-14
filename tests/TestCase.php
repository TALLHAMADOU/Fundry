<?php

namespace Hamadou\Fundry\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Hamadou\Fundry\Providers\FundryServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer la table users pour les tests uniquement
        // Dans un projet Laravel réel, cette table existe déjà
        if (!\Illuminate\Support\Facades\Schema::hasTable('users')) {
            \Illuminate\Support\Facades\Schema::create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }
        
        // Charger les migrations depuis le package
        $this->loadMigrationsFrom(realpath(__DIR__ . '/../database/migrations'));
    }

    protected function getPackageProviders($app)
    {
        return [
            FundryServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Configuration de la base de données pour les tests
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // Utiliser notre modèle User de test
        $app['config']->set('auth.providers.users.model', \Hamadou\Fundry\Tests\Helpers\TestUser::class);
    }
}
