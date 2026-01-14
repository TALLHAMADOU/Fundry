<?php

namespace Hamadou\Fundry\Tests\Unit\Services;

use Hamadou\Fundry\Tests\TestCase;
use Hamadou\Fundry\Services\WalletService;
use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Models\Country;
use Hamadou\Fundry\Enums\WalletType;
use Hamadou\Fundry\Enums\CurrencyType;
use Hamadou\Fundry\Exceptions\InsufficientFundsException;
use Hamadou\Fundry\Exceptions\InvalidAmountException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // La table users est créée dans TestCase::setUp()
        $this->service = app(WalletService::class);
    }

    public function test_can_create_wallet_for_user()
    {
        $user = $this->createUser();
        $currency = $this->createCurrency();

        $wallet = $this->service->createWalletForUser($user, [
            'currency_id' => $currency->id,
            'name' => 'Test Wallet',
            'type' => WalletType::PERSONAL,
            'balance' => 1000.00,
        ]);

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals($user->id, $wallet->user_id);
        $this->assertEquals(1000.00, $wallet->balance);
    }

    public function test_can_get_wallet_balance()
    {
        $wallet = $this->createWallet(1500.00);

        $balance = $this->service->getWalletBalance($wallet);

        $this->assertEquals(1500.00, $balance);
    }

    public function test_deposit_increments_balance()
    {
        $wallet = $this->createWallet(1000.00);
        $initialBalance = $wallet->balance;

        $this->service->deposit($wallet, 500);
        $wallet->refresh();

        $this->assertEquals($initialBalance + 500, $wallet->balance);
    }

    public function test_withdraw_decrements_balance()
    {
        $wallet = $this->createWallet(1000.00);
        $initialBalance = $wallet->balance;

        $this->service->withdraw($wallet, 300);
        $wallet->refresh();

        $this->assertEquals($initialBalance - 300, $wallet->balance);
    }

    public function test_throws_exception_for_insufficient_funds()
    {
        $wallet = $this->createWallet(100.00);

        $this->expectException(InsufficientFundsException::class);

        $this->service->withdraw($wallet, 500);
    }

    public function test_throws_exception_for_negative_amount()
    {
        $wallet = $this->createWallet(1000.00);

        $this->expectException(InvalidAmountException::class);

        $this->service->deposit($wallet, -100);
    }

    protected function createUser()
    {
        return \Hamadou\Fundry\Tests\Helpers\TestUser::create([
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@example.com',
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
        $user = isset($attributes['user_id']) 
            ? \Hamadou\Fundry\Tests\Helpers\TestUser::find($attributes['user_id'])
            : $this->createUser();
        
        $currency = $attributes['currency_id'] ?? $this->createCurrency()->id;

        return Wallet::create(array_merge([
            'user_id' => $user->id,
            'currency_id' => $currency,
            'name' => 'Test Wallet',
            'type' => WalletType::PERSONAL,
            'balance' => $balance,
        ], $attributes));
    }
}
