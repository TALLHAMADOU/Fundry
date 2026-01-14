<?php

namespace Hamadou\Fundry\Tests\Feature;

use Hamadou\Fundry\Tests\TestCase;
use Hamadou\Fundry\Facades\Fundry;
use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\DTOs\DepositDTO;
use Hamadou\Fundry\DTOs\TransferDTO;
use Hamadou\Fundry\Enums\WalletType;
use Hamadou\Fundry\Enums\CurrencyType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class WalletOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // La table users est créée dans TestCase::setUp()
    }

    public function test_complete_wallet_workflow()
    {
        $user = $this->createUser();
        $currency = $this->createCurrency();

        // 1. Créer un wallet
        $wallet = Fundry::createWallet($user, [
            'currency_id' => $currency->id,
            'name' => 'Main Wallet',
            'type' => WalletType::PERSONAL,
            'balance' => 0,
        ]);

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals(0, $wallet->balance);

        // 2. Effectuer un dépôt
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

        // 3. Vérifier le solde
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
