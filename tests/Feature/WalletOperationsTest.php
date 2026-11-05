<?php

namespace Hamadou\Fundry\Tests\Feature;

use Hamadou\Fundry\Tests\TestCase;
use Hamadou\Fundry\Facades\Fundry;
use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Models\Country;
use Hamadou\Fundry\DTOs\DepositDTO;
use Hamadou\Fundry\DTOs\TransferDTO;
use Hamadou\Fundry\Enums\WalletType;
use Hamadou\Fundry\Enums\CurrencyType;
use Hamadou\Fundry\Seeders\FundrySeeder;
use Hamadou\Fundry\Utils\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_wallet_workflow()
    {
        $user = $this->createUser();
        $currency = $this->createCurrency();

        $wallet = Fundry::createWallet($user, [
            'currency_id' => $currency->id,
            'name' => 'Main Wallet',
            'type' => WalletType::PERSONAL,
            'balance' => 0,
        ]);

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals(0, $wallet->balance);

        $dto = new DepositDTO(
            userId: $user->id,
            walletId: $wallet->id,
            amount: 1000.00,
            description: 'Initial deposit'
        );

        $transaction = Fundry::depositWithDTO($dto);
        $wallet->refresh();

        $this->assertEquals(1000.00, $wallet->balance);
        $this->assertNotNull($transaction);

        $balance = Fundry::getWalletBalance($wallet);
        $this->assertEquals(1000.00, $balance);
    }

    public function test_transfer_between_wallets()
    {
        $user = $this->createUser();
        $currency = $this->createCurrency();

        $wallet1 = Fundry::createWallet($user, [
            'currency_id' => $currency->id,
            'name' => 'Wallet 1',
            'type' => WalletType::PERSONAL,
            'balance' => 1000.00,
        ]);

        $wallet2 = Fundry::createWallet($user, [
            'currency_id' => $currency->id,
            'name' => 'Wallet 2',
            'type' => WalletType::PERSONAL,
            'balance' => 0,
        ]);

        $dto = new TransferDTO(
            userId: $user->id,
            fromWalletId: $wallet1->id,
            toWalletId: $wallet2->id,
            amount: 500.00,
            description: 'Transfer test'
        );

        $transaction = Fundry::transferWithDTO($dto);

        $wallet1->refresh();
        $wallet2->refresh();

        $this->assertEquals(500.00, $wallet1->balance);
        $this->assertEquals(500.00, $wallet2->balance);
        $this->assertNotNull($transaction);
    }

    public function test_seeders_and_artisan_commands_work_end_to_end()
    {
        (new FundrySeeder())->run();

        $this->assertGreaterThan(10, Country::count());
        $this->assertGreaterThan(10, Currency::count());
        $this->assertNotNull(Currency::findByIsoCode('USDT'));

        $this->artisan('fundry:sync-countries')->assertExitCode(0);
        $this->artisan('fundry:currencies', ['action' => 'list'])->assertExitCode(0);
        $this->artisan('fundry:currencies', ['action' => 'clear-cache'])->assertExitCode(0);
        $this->artisan('fundry:cash', ['action' => 'stats'])->assertExitCode(0);
        $this->artisan('fundry:crypto', ['action' => 'list'])->assertExitCode(0);
        $this->artisan('fundry:validate-currencies')->assertExitCode(0);
    }

    public function test_money_bcmath_precision()
    {
        $this->assertEquals(1999, Money::toCents(19.99, 2));
        $this->assertEquals(19.99, Money::fromCents(1999, 2));
        $this->assertEquals(0.3, Money::add(0.1, 0.2, 8));
        $this->assertTrue(Money::hasValidScale(0.00000001, 8));
        $this->assertFalse(Money::hasValidScale(0.123456789, 8));
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
}
