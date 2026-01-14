<?php

namespace Hamadou\Fundry\Tests\Unit\DTOs;

use Hamadou\Fundry\DTOs\WithdrawalDTO;
use PHPUnit\Framework\TestCase;

class WithdrawalDTOTest extends TestCase
{
    public function test_calculates_total_amount_correctly()
    {
        $dto = new WithdrawalDTO(
            userId: 1,
            walletId: 1,
            amount: 500.00,
            commissionPercentage: 1.0
        );

        $this->assertEquals(505.00, $dto->getTotalAmount());
    }

    public function test_calculates_commission_amount_correctly()
    {
        $dto = new WithdrawalDTO(
            userId: 1,
            walletId: 1,
            amount: 500.00,
            commissionPercentage: 1.0
        );

        $this->assertEquals(5.00, $dto->getCommissionAmount());
    }

    public function test_total_amount_equals_amount_when_no_commission()
    {
        $dto = new WithdrawalDTO(
            userId: 1,
            walletId: 1,
            amount: 500.00
        );

        $this->assertEquals(500.00, $dto->getTotalAmount());
        $this->assertEquals(0, $dto->getCommissionAmount());
    }
}
