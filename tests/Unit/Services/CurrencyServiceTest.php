<?php

namespace Hamadou\Fundry\Tests\Unit\Services;

use Hamadou\Fundry\Tests\TestCase;
use Hamadou\Fundry\Services\CurrencyService;
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Models\Country;
use Hamadou\Fundry\Enums\CurrencyType;
use Hamadou\Fundry\Exceptions\InvalidCurrencyException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CurrencyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CurrencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CurrencyService::class);
    }

    public function test_can_create_currency()
    {
        $currency = $this->service->createCurrency([
            'iso_code' => 'USD',
            'code' => 'USD',
            'name' => 'US Dollar',
            'type' => CurrencyType::FIAT,
            'symbol' => '$',
            'exchange_rate' => 1.0,
        ]);

        $this->assertInstanceOf(Currency::class, $currency);
        $this->assertEquals('USD', $currency->iso_code);
    }

    public function test_throws_exception_for_invalid_iso_code()
    {
        $this->expectException(InvalidCurrencyException::class);

        $this->service->createCurrency([
            'iso_code' => 'INVALID',
            'code' => 'INVALID',
            'name' => 'Invalid',
            'type' => CurrencyType::FIAT,
            'symbol' => 'I',
            'exchange_rate' => 1.0,
        ]);
    }

    public function test_can_update_exchange_rate()
    {
        Currency::create([
            'iso_code' => 'USD',
            'code' => 'USD',
            'name' => 'US Dollar',
            'type' => CurrencyType::FIAT,
            'symbol' => '$',
            'exchange_rate' => 1.0,
        ]);

        $result = $this->service->updateExchangeRate('USD', 1.5);

        $this->assertTrue($result);
        $this->assertDatabaseHas('currencies', [
            'iso_code' => 'USD',
            'exchange_rate' => 1.5,
        ]);
    }

    public function test_throws_exception_for_invalid_rate()
    {
        Currency::create([
            'iso_code' => 'USD',
            'code' => 'USD',
            'name' => 'US Dollar',
            'type' => CurrencyType::FIAT,
            'symbol' => '$',
            'exchange_rate' => 1.0,
        ]);

        $this->expectException(InvalidCurrencyException::class);
        $this->expectExceptionMessage('Le taux de change doit être supérieur à zéro');

        $this->service->updateExchangeRate('USD', -1);
    }

    public function test_can_convert_amount()
    {
        Currency::create([
            'iso_code' => 'USD',
            'code' => 'USD',
            'name' => 'US Dollar',
            'type' => CurrencyType::FIAT,
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'is_active' => true,
        ]);

        Currency::create([
            'iso_code' => 'EUR',
            'code' => 'EUR',
            'name' => 'Euro',
            'type' => CurrencyType::FIAT,
            'symbol' => '€',
            'exchange_rate' => 1.1,
            'is_active' => true,
        ]);

        $converted = $this->service->convertAmount(100, 'USD', 'EUR');

        $this->assertIsFloat($converted);
        $this->assertGreaterThan(0, $converted);
    }

    public function test_throws_exception_for_nonexistent_currency()
    {
        $this->expectException(InvalidCurrencyException::class);

        $this->service->convertAmount(100, 'USD', 'INVALID');
    }
}
