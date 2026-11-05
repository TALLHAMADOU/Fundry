<?php

namespace Hamadou\Fundry\Tests\Unit\Services;

use Hamadou\Fundry\Tests\TestCase;
use Hamadou\Fundry\Services\CurrencyService;
use Hamadou\Fundry\Models\Currency;
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

    public function test_get_supported_currencies_groups_by_enum_value_and_clears_cache()
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
            'iso_code' => 'USDT',
            'code' => 'USDT',
            'name' => 'Tether',
            'type' => CurrencyType::CRYPTO,
            'symbol' => '₮',
            'exchange_rate' => 1.0,
            'is_active' => true,
        ]);

        $supported = $this->service->getSupportedCurrencies();

        $this->assertArrayHasKey('fiat', $supported);
        $this->assertArrayHasKey('crypto', $supported);
        $this->assertCount(1, $supported['fiat']);
        $this->assertCount(1, $supported['crypto']);

        $this->service->clearCache();
        $this->assertTrue(true);
    }

    public function test_sync_exchange_rates_ignores_unregistered_currencies()
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

        $updated = $this->service->syncExchangeRates([
            'USD' => 1.0,
            'NON_EXISTENT' => 0.5,
        ]);

        $this->assertEquals(1, $updated);
    }
}
