<?php

namespace Hamadou\Fundry\Tests\Unit\Models;

use Hamadou\Fundry\Tests\TestCase;
use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Models\Country;
use Hamadou\Fundry\Enums\WalletType;
use Hamadou\Fundry\Enums\CurrencyType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer la table users si elle n'existe pas
        if (!Schema::hasTable('users')) {
            Schema::create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->timestamps();
            });
        }
        
        $this->app['config']->set('auth.providers.users.model', \Illuminate\Foundation\Auth\User::class);
    }

    public function test_can_create_wallet()
    {
        $user = $this->createUser();
        $currency = $this->createCurrency();

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'name' => 'Test Wallet',
            'type' => WalletType::PERSONAL,
            'balance' => 1000.00,
        ]);

        $this->assertDatabaseHas('wallets', [
            'name' => 'Test Wallet',
            'balance' => 1000.00,
        ]);
    }

    public function test_can_withdraw_if_sufficient_balance()
    {
        $wallet = $this->createWallet(1000.00);

        $this->assertTrue($wallet->canWithdraw(500));
        $this->assertFalse($wallet->canWithdraw(1500));
    }

    public function test_can_withdraw_respects_min_balance()
    {
        $wallet = $this->createWallet(1000.00, ['min_balance' => 200]);

        $this->assertTrue($wallet->canWithdraw(700)); // 1000 - 700 = 300 > 200
        $this->assertFalse($wallet->canWithdraw(900)); // 1000 - 900 = 100 < 200
    }

    public function test_can_withdraw_respects_transaction_limit()
    {
        $wallet = $this->createWallet(1000.00, ['transaction_limit' => 500]);

        $this->assertTrue($wallet->canWithdraw(400));
        $this->assertFalse($wallet->canWithdraw(600));
    }

    public function test_deposit_increments_balance()
    {
        $wallet = $this->createWallet(1000.00);
        $initialBalance = $wallet->balance;

        $wallet->deposit(500);
        $wallet->refresh();

        $this->assertEquals($initialBalance + 500, $wallet->balance);
    }

    public function test_withdraw_decrements_balance()
    {
        $wallet = $this->createWallet(1000.00);
        $initialBalance = $wallet->balance;

        $wallet->withdraw(300);
        $wallet->refresh();

        $this->assertEquals($initialBalance - 300, $wallet->balance);
    }

    public function test_belongs_to_user()
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $wallet = $this->createWallet(1000.00, ['user_id' => $user1->id]);

        $this->assertTrue($wallet->belongsToUser($user1));
        $this->assertFalse($wallet->belongsToUser($user2));
    }

    protected function createUser()
    {
        return \Hamadou\Fundry\Tests\Helpers\TestUser::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    protected function createCurrency(array $attributes = [])
    {
        return Currency::create(array_merge([
            'iso_code' => 'USD',
            'code' => 'USD',
            'name' => 'US Dollar',
            'type' => CurrencyType::FIAT,
            'symbol' => '$',
            'exchange_rate' => 1.0,
        ], $attributes));
    }

    protected function createWallet(float $balance = 0, array $attributes = [])
    {
        $user = $attributes['user_id'] ?? $this->createUser()->id;
        $currency = $attributes['currency_id'] ?? $this->createCurrency()->id;

        return Wallet::create(array_merge([
            'user_id' => $user,
            'currency_id' => $currency,
            'name' => 'Test Wallet',
            'type' => WalletType::PERSONAL,
            'balance' => $balance,
        ], $attributes));
    }
}
