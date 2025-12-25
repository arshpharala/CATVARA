<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company\Company;
use App\Models\Inventory\InventoryReason;
use App\Models\Inventory\CompanyInventorySetting;
use App\Models\Inventory\InventoryTransferStatus;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        /**
         * 1️⃣ INVENTORY REASONS
         */
        $reasons = [
            // Sales
            ['code' => 'SALE', 'name' => 'Sale', 'is_increase' => false],

            // Transfers
            ['code' => 'TRANSFER_OUT', 'name' => 'Transfer Out', 'is_increase' => false],
            ['code' => 'TRANSFER_IN', 'name' => 'Transfer In', 'is_increase' => true],

            // Adjustments
            ['code' => 'ADJUSTMENT_OUT', 'name' => 'Adjustment Out', 'is_increase' => false],
            ['code' => 'ADJUSTMENT_IN', 'name' => 'Adjustment In', 'is_increase' => true],

            // Purchase / Receiving
            ['code' => 'PURCHASE_IN', 'name' => 'Purchase / Receiving', 'is_increase' => true],

            // Returns
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

        /**
         * 2️⃣ INVENTORY TRANSFER STATUSES
         */
        $transferStatuses = [
            ['code' => 'DRAFT',     'name' => 'Draft',              'is_final' => false],
            ['code' => 'APPROVED',  'name' => 'Approved',           'is_final' => false],
            ['code' => 'SHIPPED',   'name' => 'Shipped',            'is_final' => false],
            ['code' => 'RECEIVED',  'name' => 'Partially Received', 'is_final' => false],
            ['code' => 'CLOSED',    'name' => 'Closed',             'is_final' => true],
            ['code' => 'CANCELLED', 'name' => 'Cancelled',          'is_final' => true],
        ];

        foreach ($transferStatuses as $status) {
            InventoryTransferStatus::updateOrCreate(
                ['code' => $status['code']],
                [
                    'name' => $status['name'],
                    'is_final' => $status['is_final'],
                    'is_active' => true,
                ]
            );
        }

        /**
         * 3️⃣ COMPANY INVENTORY SETTINGS
         */
        Company::all()->each(function (Company $company) {
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
