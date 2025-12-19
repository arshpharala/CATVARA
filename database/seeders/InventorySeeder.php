<?php

namespace Database\Seeders;

use App\Models\Company\Company;
use Illuminate\Database\Seeder;
use App\Models\Inventory\InventoryReason;
use App\Models\Inventory\CompanyInventorySetting;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            // Sales
            ['code' => 'SALE', 'name' => 'Sale', 'is_increase' => false],

            // Transfers
            ['code' => 'TRANSFER_OUT', 'name' => 'Transfer Out', 'is_increase' => false],
            ['code' => 'TRANSFER_IN', 'name' => 'Transfer In', 'is_increase' => true],

            // Adjustments
            ['code' => 'ADJUSTMENT_OUT', 'name' => 'Adjustment Out', 'is_increase' => false],
            ['code' => 'ADJUSTMENT_IN', 'name' => 'Adjustment In', 'is_increase' => true],

            // Purchase / Receiving (future)
            ['code' => 'PURCHASE_IN', 'name' => 'Purchase / Receiving', 'is_increase' => true],

            // Returns (future)
            ['code' => 'RETURN_IN', 'name' => 'Customer Return', 'is_increase' => true],
            ['code' => 'RETURN_OUT', 'name' => 'Supplier Return', 'is_increase' => false],
        ];

        foreach ($reasons as $r) {
            InventoryReason::updateOrCreate(
                ['code' => $r['code']],
                [
                    'name' => $r['name'],
                    'is_increase' => $r['is_increase'],
                    'is_active' => true,
                ]
            );
        }


        Company::all()->each(function ($company) {
            CompanyInventorySetting::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'allow_negative_stock' => false,
                    'block_sale_if_no_stock' => true,
                    'require_transfer_approval' => true,
                    'auto_receive_transfer' => false,
                    'allow_partial_transfer_receive' => true,
                ]
            );
        });
    }
}
