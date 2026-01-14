<?php

namespace Hamadou\Fundry\Tests\Unit\Services;

use Hamadou\Fundry\Tests\TestCase;
use Hamadou\Fundry\Services\TransactionService;
use Hamadou\Fundry\Models\Wallet;
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\DTOs\DepositDTO;
use Hamadou\Fundry\DTOs\WithdrawalDTO;
use Hamadou\Fundry\Enums\WalletType;
use Hamadou\Fundry\Enums\CurrencyType;
use Hamadou\Fundry\Enums\TransactionStatus;
use Hamadou\Fundry\Exceptions\InsufficientFundsException;
use Hamadou\Fundry\Exceptions\UnauthorizedWalletException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // La table users est créée dans TestCase::setUp()
        $this->service = app(TransactionService::class);
    }

    public function test_can_process_deposit_with_dto()
    {
        $user = $this->createUser();
        $wallet = $this->createWallet(1000.00, ['user_id' => $user->id]);

        $dto = new DepositDTO(
            userId: $user->id,
            walletId: $wallet->id,
            amount: 500.00,
            description: 'Test deposit'
        );

        $transaction = $this->service->processDepositWithDTO($dto);

        $this->assertNotNull($transaction);
        $this->assertEquals(TransactionStatus::COMPLETED, $transaction->status);
        $this->assertEquals(500.00, $transaction->amount);
        
        $wallet->refresh();
        $this->assertEquals(1500.00, $wallet->balance);
    }

    public function test_deposit_with_commission()
    {
        $user = $this->createUser();
        $wallet = $this->createWallet(1000.00, ['user_id' => $user->id]);

        $dto = new DepositDTO(
            userId: $user->id,
            walletId: $wallet->id,
            amount: 1000.00,
            commissionPercentage: 2.5
        );

        $transaction = $this->service->processDepositWithDTO($dto);

        $wallet->refresh();
        $this->assertEquals(1975.00, $wallet->balance); // 1000 + (1000 - 25)
        $this->assertEquals(975.00, $transaction->amount); // Montant net
    }

    public function test_can_process_withdrawal_with_dto()
    {
        $user = $this->createUser();
        $wallet = $this->createWallet(1000.00, ['user_id' => $user->id]);

        $dto = new WithdrawalDTO(
            userId: $user->id,
            walletId: $wallet->id,
            amount: 300.00,
            description: 'Test withdrawal'
        );

        $transaction = $this->service->processWithdrawalWithDTO($dto);

        $this->assertNotNull($transaction);
        $this->assertEquals(TransactionStatus::COMPLETED, $transaction->status);
        
        $wallet->refresh();
        $this->assertEquals(700.00, $wallet->balance);
    }

    public function test_throws_exception_for_unauthorized_wallet()
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $wallet = $this->createWallet(1000.00, ['user_id' => $user1->id]);

        $dto = new DepositDTO(
            userId: $user2->id, // Mauvais utilisateur
            walletId: $wallet->id,
            amount: 500.00
        );

        $this->expectException(UnauthorizedWalletException::class);

        $this->service->processDepositWithDTO($dto);
    }

    public function test_throws_exception_for_insufficient_funds_on_withdrawal()
    {
        $user = $this->createUser();
        $wallet = $this->createWallet(100.00, ['user_id' => $user->id]);

        $dto = new WithdrawalDTO(
            userId: $user->id,
            walletId: $wallet->id,
            amount: 500.00
        );

        $this->expectException(InsufficientFundsException::class);

        $this->service->processWithdrawalWithDTO($dto);
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
