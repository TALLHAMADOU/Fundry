<?php

namespace Hamadou\Fundry\Seeders;

use Illuminate\Database\Seeder;
use Hamadou\Fundry\Models\Currency;
use Hamadou\Fundry\Models\Country;
use Hamadou\Fundry\Enums\CurrencyType;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Devises Fiat principales liées aux pays
        $fiatCurrencies = [
            ['iso_code' => 'USD', 'code' => 'USD', 'name' => 'Dollar américain', 'symbol' => '$', 'decimals' => 2, 'exchange_rate' => 1.0, 'base_currency' => true, 'country_iso' => 'US'],
            ['iso_code' => 'EUR', 'code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimals' => 2, 'exchange_rate' => 1.1, 'country_iso' => 'FR'],
            ['iso_code' => 'GBP', 'code' => 'GBP', 'name' => 'Livre sterling', 'symbol' => '£', 'decimals' => 2, 'exchange_rate' => 1.27, 'country_iso' => 'GB'],
            ['iso_code' => 'XOF', 'code' => 'XOF', 'name' => 'Franc CFA (BCEAO)', 'symbol' => 'CFA', 'decimals' => 0, 'exchange_rate' => 0.0017, 'country_iso' => 'SN'],
            ['iso_code' => 'MAD', 'code' => 'MAD', 'name' => 'Dirham marocain', 'symbol' => 'د.م', 'decimals' => 2, 'exchange_rate' => 0.10, 'country_iso' => 'MA'],
            ['iso_code' => 'TND', 'code' => 'TND', 'name' => 'Dinar tunisien', 'symbol' => 'د.ت', 'decimals' => 3, 'exchange_rate' => 0.32, 'country_iso' => 'TN'],
            ['iso_code' => 'DZD', 'code' => 'DZD', 'name' => 'Dinar algérien', 'symbol' => 'د.ج', 'decimals' => 2, 'exchange_rate' => 0.0074, 'country_iso' => 'DZ'],
            ['iso_code' => 'NGN', 'code' => 'NGN', 'name' => 'Naira nigérian', 'symbol' => '₦', 'decimals' => 2, 'exchange_rate' => 0.00066, 'country_iso' => 'NG'],
            ['iso_code' => 'ZAR', 'code' => 'ZAR', 'name' => 'Rand sud-africain', 'symbol' => 'R', 'decimals' => 2, 'exchange_rate' => 0.053, 'country_iso' => 'ZA'],
            ['iso_code' => 'CNY', 'code' => 'CNY', 'name' => 'Yuan chinois', 'symbol' => '¥', 'decimals' => 2, 'exchange_rate' => 0.14, 'country_iso' => 'CN'],
            ['iso_code' => 'JPY', 'code' => 'JPY', 'name' => 'Yen japonais', 'symbol' => '¥', 'decimals' => 0, 'exchange_rate' => 0.0067, 'country_iso' => 'JP'],
            ['iso_code' => 'INR', 'code' => 'INR', 'name' => 'Roupie indienne', 'symbol' => '₹', 'decimals' => 2, 'exchange_rate' => 0.012, 'country_iso' => 'IN'],
            ['iso_code' => 'CAD', 'code' => 'CAD', 'name' => 'Dollar canadien', 'symbol' => 'C$', 'decimals' => 2, 'exchange_rate' => 0.74, 'country_iso' => 'CA'],
            ['iso_code' => 'AUD', 'code' => 'AUD', 'name' => 'Dollar australien', 'symbol' => 'A$', 'decimals' => 2, 'exchange_rate' => 0.66, 'country_iso' => 'AU'],
            ['iso_code' => 'BRL', 'code' => 'BRL', 'name' => 'Real brésilien', 'symbol' => 'R$', 'decimals' => 2, 'exchange_rate' => 0.20, 'country_iso' => 'BR'],
        ];

        foreach ($fiatCurrencies as $currencyData) {
            $countryIso = $currencyData['country_iso'];
            unset($currencyData['country_iso']);

            $country = Country::where('iso_code', $countryIso)->first();
            
            $currencyData['type'] = CurrencyType::FIAT->value;
            $currencyData['is_active'] = true;
            
            if ($country) {
                $currencyData['country_id'] = $country->id;
            }

            Currency::updateOrCreate(
                ['iso_code' => $currencyData['iso_code']],
                $currencyData
            );
        }

        // Cryptomonnaies (sans pays)
        $cryptoCurrencies = [
            ['iso_code' => 'BTC', 'code' => 'BTC', 'name' => 'Bitcoin', 'symbol' => '₿', 'decimals' => 8, 'exchange_rate' => 43000.0, 'type' => CurrencyType::CRYPTO->value],
            ['iso_code' => 'ETH', 'code' => 'ETH', 'name' => 'Ethereum', 'symbol' => 'Ξ', 'decimals' => 8, 'exchange_rate' => 2500.0, 'type' => CurrencyType::CRYPTO->value],
            ['iso_code' => 'USDT', 'code' => 'USDT', 'name' => 'Tether', 'symbol' => '₮', 'decimals' => 2, 'exchange_rate' => 1.0, 'type' => CurrencyType::CRYPTO->value],
        ];

        foreach ($cryptoCurrencies as $currencyData) {
            $currencyData['is_active'] = true;
            Currency::updateOrCreate(
                ['iso_code' => $currencyData['iso_code']],
                $currencyData
            );
        }
    }
}
