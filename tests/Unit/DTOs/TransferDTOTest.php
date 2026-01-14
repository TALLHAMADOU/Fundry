<?php

namespace Hamadou\Fundry\Tests\Unit\DTOs;

use Hamadou\Fundry\DTOs\TransferDTO;
use PHPUnit\Framework\TestCase;

class TransferDTOTest extends TestCase
{
    public function test_throws_exception_for_same_wallet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le portefeuille source et destination ne peuvent pas être identiques');

        new TransferDTO(
            userId: 1,
            fromWalletId: 1,
            toWalletId: 1,
            amount: 100.00
        );
    }

    public function test_calculates_total_amount_correctly()
    {
        $dto = new TransferDTO(
            userId: 1,
            fromWalletId: 1,
            toWalletId: 2,
            amount: 1000.00,
            commissionPercentage: 1.5
        );

        $this->assertEquals(1015.00, $dto->getTotalAmount());
    }

    public function test_calculates_net_amount_correctly()
    {
        $dto = new TransferDTO(
            userId: 1,
            fromWalletId: 1,
            toWalletId: 2,
            amount: 1000.00,
            commissionPercentage: 1.5
        );

        $this->assertEquals(985.00, $dto->getNetAmount());
    }

    public function test_calculates_commission_amount_correctly()
    {
        $dto = new TransferDTO(
            userId: 1,
            fromWalletId: 1,
            toWalletId: 2,
            amount: 1000.00,
            commissionPercentage: 1.5
        );

        $this->assertEquals(15.00, $dto->getCommissionAmount());
    }
}
