<?php

namespace Hamadou\Fundry\Seeders;

use Illuminate\Database\Seeder;
use Hamadou\Fundry\Models\Country;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            // Europe
            ['name' => 'France', 'name_en' => 'France', 'iso_code' => 'FR', 'iso_code_3' => 'FRA', 'numeric_code' => '250', 'phone_code' => '+33', 'continent' => 'Europe', 'capital' => 'Paris', 'currency_code' => 'EUR', 'currency_name' => 'Euro', 'currency_symbol' => '€'],
            ['name' => 'Allemagne', 'name_en' => 'Germany', 'iso_code' => 'DE', 'iso_code_3' => 'DEU', 'numeric_code' => '276', 'phone_code' => '+49', 'continent' => 'Europe', 'capital' => 'Berlin', 'currency_code' => 'EUR', 'currency_name' => 'Euro', 'currency_symbol' => '€'],
            ['name' => 'Espagne', 'name_en' => 'Spain', 'iso_code' => 'ES', 'iso_code_3' => 'ESP', 'numeric_code' => '724', 'phone_code' => '+34', 'continent' => 'Europe', 'capital' => 'Madrid', 'currency_code' => 'EUR', 'currency_name' => 'Euro', 'currency_symbol' => '€'],
            ['name' => 'Italie', 'name_en' => 'Italy', 'iso_code' => 'IT', 'iso_code_3' => 'ITA', 'numeric_code' => '380', 'phone_code' => '+39', 'continent' => 'Europe', 'capital' => 'Rome', 'currency_code' => 'EUR', 'currency_name' => 'Euro', 'currency_symbol' => '€'],
            ['name' => 'Royaume-Uni', 'name_en' => 'United Kingdom', 'iso_code' => 'GB', 'iso_code_3' => 'GBR', 'numeric_code' => '826', 'phone_code' => '+44', 'continent' => 'Europe', 'capital' => 'London', 'currency_code' => 'GBP', 'currency_name' => 'British Pound', 'currency_symbol' => '£'],
            
            // Amérique du Nord
            ['name' => 'États-Unis', 'name_en' => 'United States', 'iso_code' => 'US', 'iso_code_3' => 'USA', 'numeric_code' => '840', 'phone_code' => '+1', 'continent' => 'North America', 'capital' => 'Washington', 'currency_code' => 'USD', 'currency_name' => 'US Dollar', 'currency_symbol' => '$'],
            ['name' => 'Canada', 'name_en' => 'Canada', 'iso_code' => 'CA', 'iso_code_3' => 'CAN', 'numeric_code' => '124', 'phone_code' => '+1', 'continent' => 'North America', 'capital' => 'Ottawa', 'currency_code' => 'CAD', 'currency_name' => 'Canadian Dollar', 'currency_symbol' => 'C$'],
            ['name' => 'Mexique', 'name_en' => 'Mexico', 'iso_code' => 'MX', 'iso_code_3' => 'MEX', 'numeric_code' => '484', 'phone_code' => '+52', 'continent' => 'North America', 'capital' => 'Mexico City', 'currency_code' => 'MXN', 'currency_name' => 'Mexican Peso', 'currency_symbol' => '$'],
            
            // Afrique
            ['name' => 'Sénégal', 'name_en' => 'Senegal', 'iso_code' => 'SN', 'iso_code_3' => 'SEN', 'numeric_code' => '686', 'phone_code' => '+221', 'continent' => 'Africa', 'capital' => 'Dakar', 'currency_code' => 'XOF', 'currency_name' => 'West African CFA Franc', 'currency_symbol' => 'CFA'],
            ['name' => 'Côte d\'Ivoire', 'name_en' => 'Ivory Coast', 'iso_code' => 'CI', 'iso_code_3' => 'CIV', 'numeric_code' => '384', 'phone_code' => '+225', 'continent' => 'Africa', 'capital' => 'Yamoussoukro', 'currency_code' => 'XOF', 'currency_name' => 'West African CFA Franc', 'currency_symbol' => 'CFA'],
            ['name' => 'Maroc', 'name_en' => 'Morocco', 'iso_code' => 'MA', 'iso_code_3' => 'MAR', 'numeric_code' => '504', 'phone_code' => '+212', 'continent' => 'Africa', 'capital' => 'Rabat', 'currency_code' => 'MAD', 'currency_name' => 'Moroccan Dirham', 'currency_symbol' => 'د.م'],
            ['name' => 'Tunisie', 'name_en' => 'Tunisia', 'iso_code' => 'TN', 'iso_code_3' => 'TUN', 'numeric_code' => '788', 'phone_code' => '+216', 'continent' => 'Africa', 'capital' => 'Tunis', 'currency_code' => 'TND', 'currency_name' => 'Tunisian Dinar', 'currency_symbol' => 'د.ت'],
            ['name' => 'Algérie', 'name_en' => 'Algeria', 'iso_code' => 'DZ', 'iso_code_3' => 'DZA', 'numeric_code' => '012', 'phone_code' => '+213', 'continent' => 'Africa', 'capital' => 'Algiers', 'currency_code' => 'DZD', 'currency_name' => 'Algerian Dinar', 'currency_symbol' => 'د.ج'],
            ['name' => 'Nigeria', 'name_en' => 'Nigeria', 'iso_code' => 'NG', 'iso_code_3' => 'NGA', 'numeric_code' => '566', 'phone_code' => '+234', 'continent' => 'Africa', 'capital' => 'Abuja', 'currency_code' => 'NGN', 'currency_name' => 'Nigerian Naira', 'currency_symbol' => '₦'],
            ['name' => 'Afrique du Sud', 'name_en' => 'South Africa', 'iso_code' => 'ZA', 'iso_code_3' => 'ZAF', 'numeric_code' => '710', 'phone_code' => '+27', 'continent' => 'Africa', 'capital' => 'Cape Town', 'currency_code' => 'ZAR', 'currency_name' => 'South African Rand', 'currency_symbol' => 'R'],
            
            // Asie
            ['name' => 'Chine', 'name_en' => 'China', 'iso_code' => 'CN', 'iso_code_3' => 'CHN', 'numeric_code' => '156', 'phone_code' => '+86', 'continent' => 'Asia', 'capital' => 'Beijing', 'currency_code' => 'CNY', 'currency_name' => 'Chinese Yuan', 'currency_symbol' => '¥'],
            ['name' => 'Japon', 'name_en' => 'Japan', 'iso_code' => 'JP', 'iso_code_3' => 'JPN', 'numeric_code' => '392', 'phone_code' => '+81', 'continent' => 'Asia', 'capital' => 'Tokyo', 'currency_code' => 'JPY', 'currency_name' => 'Japanese Yen', 'currency_symbol' => '¥'],
            ['name' => 'Inde', 'name_en' => 'India', 'iso_code' => 'IN', 'iso_code_3' => 'IND', 'numeric_code' => '356', 'phone_code' => '+91', 'continent' => 'Asia', 'capital' => 'New Delhi', 'currency_code' => 'INR', 'currency_name' => 'Indian Rupee', 'currency_symbol' => '₹'],
            ['name' => 'Arabie Saoudite', 'name_en' => 'Saudi Arabia', 'iso_code' => 'SA', 'iso_code_3' => 'SAU', 'numeric_code' => '682', 'phone_code' => '+966', 'continent' => 'Asia', 'capital' => 'Riyadh', 'currency_code' => 'SAR', 'currency_name' => 'Saudi Riyal', 'currency_symbol' => 'ر.س'],
            ['name' => 'Émirats Arabes Unis', 'name_en' => 'United Arab Emirates', 'iso_code' => 'AE', 'iso_code_3' => 'ARE', 'numeric_code' => '784', 'phone_code' => '+971', 'continent' => 'Asia', 'capital' => 'Abu Dhabi', 'currency_code' => 'AED', 'currency_name' => 'UAE Dirham', 'currency_symbol' => 'د.إ'],
            
            // Océanie
            ['name' => 'Australie', 'name_en' => 'Australia', 'iso_code' => 'AU', 'iso_code_3' => 'AUS', 'numeric_code' => '036', 'phone_code' => '+61', 'continent' => 'Oceania', 'capital' => 'Canberra', 'currency_code' => 'AUD', 'currency_name' => 'Australian Dollar', 'currency_symbol' => 'A$'],
            
            // Amérique du Sud
            ['name' => 'Brésil', 'name_en' => 'Brazil', 'iso_code' => 'BR', 'iso_code_3' => 'BRA', 'numeric_code' => '076', 'phone_code' => '+55', 'continent' => 'South America', 'capital' => 'Brasília', 'currency_code' => 'BRL', 'currency_name' => 'Brazilian Real', 'currency_symbol' => 'R$'],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['iso_code' => $country['iso_code']],
                $country
            );
        }
    }
}
