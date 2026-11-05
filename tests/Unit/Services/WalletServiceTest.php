<?php

namespace Hamadou\Fundry\Tests\Unit\Services;

use Hamadou\Fundry\Tests\TestCase;
use Hamadou\Fundry\Services\WalletService;
use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\DTOs\TransferDTO;
use Hamadou\Fundry\Enums\WalletType;
use Hamadou\Fundry\Enums\CurrencyType;
use Hamadou\Fundry\Exceptions\InsufficientFundsException;
use Hamadou\Fundry\Exceptions\InvalidAmountException;
use Hamadou\Fundry\Exceptions\InvalidCurrencyException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
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

    public function test_transfer_with_commission_sender_pays_fee()
    {
        $user = $this->createUser();
        $usd = $this->createCurrency();

        $fromWallet = $this->createWallet(2000.00, ['user_id' => $user->id, 'currency_id' => $usd->id]);
        $toWallet = $this->createWallet(0.00, ['user_id' => $user->id, 'currency_id' => $usd->id]);

        $dto = new TransferDTO(
            userId: $user->id,
            fromWalletId: $fromWallet->id,
            toWalletId: $toWallet->id,
            amount: 1000.00,
            commissionPercentage: 1.5
        );

        $transaction = $this->service->transferWithDTO($dto);

        $fromWallet->refresh();
        $toWallet->refresh();

        $this->assertEquals(985.00, (float) $fromWallet->balance); // 2000 - 1015
        $this->assertEquals(1000.00, (float) $toWallet->balance); // 0 + 1000
        $this->assertEquals(1000.00, (float) $transaction->amount);
        $this->assertEquals(15.00, $transaction->metadata['commission_amount']);
    }

    public function test_cross_currency_transfer_option_a_auto_converts()
    {
        config(['fundry.allow_cross_currency_transfers' => true]);

        $user = $this->createUser();
        $usd = $this->createCurrency(['iso_code' => 'USD', 'code' => 'USD', 'exchange_rate' => 1.0]);
        $eur = $this->createCurrency(['iso_code' => 'EUR', 'code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 2.0]);

        $usdWallet = $this->createWallet(1000.00, ['user_id' => $user->id, 'currency_id' => $usd->id]);
        $eurWallet = $this->createWallet(0.00, ['user_id' => $user->id, 'currency_id' => $eur->id]);

        $transaction = $this->service->transfer($usdWallet, $eurWallet, 200.00, 'USD to EUR');

        $usdWallet->refresh();
        $eurWallet->refresh();

        $this->assertEquals(800.00, (float) $usdWallet->balance);
        $this->assertEquals(100.00, (float) $eurWallet->balance); // 200 USD / 2.0 = 100 EUR
        $this->assertEquals(100.00, (float) $transaction->converted_amount);
        $this->assertEquals(0.5, (float) $transaction->exchange_rate);
    }

    public function test_cross_currency_transfer_option_b_blocks_when_disabled()
    {
        config(['fundry.allow_cross_currency_transfers' => false]);

        $user = $this->createUser();
        $usd = $this->createCurrency(['iso_code' => 'USD', 'code' => 'USD', 'exchange_rate' => 1.0]);
        $eur = $this->createCurrency(['iso_code' => 'EUR', 'code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 1.1]);

        $usdWallet = $this->createWallet(1000.00, ['user_id' => $user->id, 'currency_id' => $usd->id]);
        $eurWallet = $this->createWallet(0.00, ['user_id' => $user->id, 'currency_id' => $eur->id]);

        $this->expectException(InvalidCurrencyException::class);

        $this->service->transfer($usdWallet, $eurWallet, 100.00);
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
