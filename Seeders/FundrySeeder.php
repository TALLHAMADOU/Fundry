<?php

namespace Hamadou\Fundry\Seeders;

use Illuminate\Database\Seeder;

class FundrySeeder extends Seeder
{
    /**
     * Run the database seeds for Fundry (Countries + Currencies).
     */
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            CurrencySeeder::class,
        ]);
    }
}
