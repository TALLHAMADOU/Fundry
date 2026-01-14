<?php

namespace Hamadou\Fundry\Tests\Unit\DTOs;

use Hamadou\Fundry\DTOs\DepositDTO;
use PHPUnit\Framework\TestCase;

class DepositDTOTest extends TestCase
{
    public function test_can_create_deposit_dto()
    {
        $dto = new DepositDTO(
            userId: 1,
            walletId: 1,
            amount: 100.00,
            description: 'Test deposit',
            commissionPercentage: 2.5
        );

        $this->assertEquals(1, $dto->userId);
        $this->assertEquals(1, $dto->walletId);
        $this->assertEquals(100.00, $dto->amount);
        $this->assertEquals(2.5, $dto->commissionPercentage);
    }

    public function test_throws_exception_for_negative_amount()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le montant doit être supérieur à zéro');

        new DepositDTO(
            userId: 1,
            walletId: 1,
            amount: -100.00
        );
    }

    public function test_throws_exception_for_invalid_commission_percentage()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le pourcentage de commission doit être entre 0 et 100');

        new DepositDTO(
            userId: 1,
            walletId: 1,
            amount: 100.00,
            commissionPercentage: 150
        );
    }

    public function test_calculates_net_amount_correctly()
    {
        $dto = new DepositDTO(
            userId: 1,
            walletId: 1,
            amount: 1000.00,
            commissionPercentage: 2.5
        );

        $this->assertEquals(975.00, $dto->getNetAmount());
    }

    public function test_calculates_commission_amount_correctly()
    {
        $dto = new DepositDTO(
            userId: 1,
            walletId: 1,
            amount: 1000.00,
            commissionPercentage: 2.5
        );

        $this->assertEquals(25.00, $dto->getCommissionAmount());
    }

    public function test_net_amount_equals_amount_when_no_commission()
    {
        $dto = new DepositDTO(
            userId: 1,
            walletId: 1,
            amount: 1000.00
        );

        $this->assertEquals(1000.00, $dto->getNetAmount());
        $this->assertEquals(0, $dto->getCommissionAmount());
    }

    public function test_can_create_from_array()
    {
        $dto = DepositDTO::fromArray([
            'user_id' => 1,
            'wallet_id' => 1,
            'amount' => 100.00,
            'description' => 'Test',
            'commission_percentage' => 2.5,
        ]);

        $this->assertEquals(1, $dto->userId);
        $this->assertEquals(1, $dto->walletId);
        $this->assertEquals(100.00, $dto->amount);
        $this->assertEquals(2.5, $dto->commissionPercentage);
    }

    public function test_can_convert_to_array()
    {
        $dto = new DepositDTO(
            userId: 1,
            walletId: 1,
            amount: 1000.00,
            commissionPercentage: 2.5
        );

        $array = $dto->toArray();

        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('wallet_id', $array);
        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('commission_percentage', $array);
        $this->assertArrayHasKey('net_amount', $array);
        $this->assertArrayHasKey('commission_amount', $array);
    }
}
