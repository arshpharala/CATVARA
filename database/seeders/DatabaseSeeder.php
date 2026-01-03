<?php

namespace Database\Seeders;

use App\Models\Company\Company;
use App\Models\Pricing\Currency;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Setup Company, Currency, Locations, Roles
        $this->call(VapeShopSetupSeeder::class);

        // 2. Import Products
        $this->call(VapeShopSeeder::class);

        $this->call(InventorySeeder::class);

        $this->call(AuthenticationSeeder::class);
    }
}
