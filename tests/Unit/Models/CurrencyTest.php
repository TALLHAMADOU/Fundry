<?php

namespace Hamadou\Fundry\Tests\Unit\Models;

use Hamadou\Fundry\Tests\TestCase;
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Models\Country;
use Hamadou\Fundry\Enums\CurrencyType;
use Hamadou\Fundry\Exceptions\InvalidCurrencyException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_currency()
    {
        $currency = Currency::create([
            'iso_code' => 'USD',
            'code' => 'USD',
            'name' => 'US Dollar',
            'type' => CurrencyType::FIAT,
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'decimals' => 2,
        ]);

        $this->assertDatabaseHas('currencies', [
            'iso_code' => 'USD',
            'name' => 'US Dollar',
        ]);
    }

    public function test_iso_code_is_normalized_to_uppercase()
    {
        $currency = Currency::create([
            'iso_code' => 'usd',
            'code' => 'USD',
            'name' => 'US Dollar',
            'type' => CurrencyType::FIAT,
            'symbol' => '$',
            'exchange_rate' => 1.0,
        ]);

        $this->assertEquals('USD', $currency->iso_code);
    }

    public function test_validates_iso_4217_code_for_fiat()
    {
        $this->expectException(InvalidCurrencyException::class);

        Currency::create([
            'iso_code' => 'INVALID',
            'code' => 'INVALID',
            'name' => 'Invalid Currency',
            'type' => CurrencyType::FIAT,
            'symbol' => 'I',
            'exchange_rate' => 1.0,
        ]);
    }

    public function test_is_valid_iso_4217_code()
    {
        $this->assertTrue(Currency::isValidIso4217Code('USD'));
        $this->assertTrue(Currency::isValidIso4217Code('EUR'));
        $this->assertFalse(Currency::isValidIso4217Code('US'));
        $this->assertFalse(Currency::isValidIso4217Code('USDD'));
        $this->assertFalse(Currency::isValidIso4217Code('123'));
    }

    public function test_find_by_iso_code()
    {
        Currency::create([
            'iso_code' => 'EUR',
            'code' => 'EUR',
            'name' => 'Euro',
            'type' => CurrencyType::FIAT,
            'symbol' => '€',
            'exchange_rate' => 1.1,
        ]);

        $currency = Currency::findByIsoCode('EUR');

        $this->assertNotNull($currency);
        $this->assertEquals('EUR', $currency->iso_code);
    }

    public function test_can_convert_to_another_currency()
    {
        $usd = Currency::create([
            'iso_code' => 'USD',
            'code' => 'USD',
            'name' => 'US Dollar',
            'type' => CurrencyType::FIAT,
            'symbol' => '$',
            'exchange_rate' => 1.0,
        ]);

        $eur = Currency::create([
            'iso_code' => 'EUR',
            'code' => 'EUR',
            'name' => 'Euro',
            'type' => CurrencyType::FIAT,
            'symbol' => '€',
            'exchange_rate' => 1.1,
        ]);

        $converted = $usd->convertTo(100, $eur);
        $this->assertEquals(90.9090909091, round($converted, 10));
    }

    public function test_can_convert_to_safe_validates_conversion()
    {
        $usd = Currency::create([
            'iso_code' => 'USD',
            'code' => 'USD',
            'name' => 'US Dollar',
            'type' => CurrencyType::FIAT,
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'is_active' => true,
        ]);

        $eur = Currency::create([
            'iso_code' => 'EUR',
            'code' => 'EUR',
            'name' => 'Euro',
            'type' => CurrencyType::FIAT,
            'symbol' => '€',
            'exchange_rate' => 1.1,
            'is_active' => true,
        ]);

        $converted = $usd->convertToSafe(100, $eur);
        $this->assertIsFloat($converted);
    }

    public function test_convert_to_safe_throws_exception_for_inactive_currency()
    {
        $this->expectException(InvalidCurrencyException::class);

        $usd = Currency::create([
            'iso_code' => 'USD',
            'code' => 'USD',
            'name' => 'US Dollar',
            'type' => CurrencyType::FIAT,
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'is_active' => false,
        ]);

        $eur = Currency::create([
            'iso_code' => 'EUR',
            'code' => 'EUR',
            'name' => 'Euro',
            'type' => CurrencyType::FIAT,
            'symbol' => '€',
            'exchange_rate' => 1.1,
            'is_active' => true,
        ]);

        $usd->convertToSafe(100, $eur);
    }

    public function test_belongs_to_country()
    {
        $country = Country::create([
            'name' => 'United States',
            'name_en' => 'United States',
            'iso_code' => 'US',
            'currency_code' => 'USD',
        ]);

        $currency = Currency::create([
            'iso_code' => 'USD',
            'code' => 'USD',
            'name' => 'US Dollar',
            'type' => CurrencyType::FIAT,
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'country_id' => $country->id,
        ]);

        $this->assertNotNull($currency->country);
        $this->assertEquals($country->id, $currency->country->id);
    }
}
