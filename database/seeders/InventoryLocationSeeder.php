<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

use App\Models\Company\Company;
use App\Models\Inventory\Store;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\InventoryLocation;

class InventoryLocationSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * MASTER LOCATION DATA
         */
        $locations = [
            'stores' => [
                [
                    'code' => 'STORE-MALL',
                    'name' => 'Mall Store',
                    'phone' => '+44 20 1111 2222',
                    'address' => 'Westfield Mall, London, UK',
                ],
                [
                    'code' => 'STORE-HIGHST',
                    'name' => 'High Street Store',
                    'phone' => '+44 20 3333 4444',
                    'address' => 'Oxford Street, London, UK',
                ],
            ],
            'warehouses' => [
                [
                    'code' => 'WH-CENTRAL',
                    'name' => 'Central Warehouse',
                    'phone' => '+44 20 9999 8888',
                    'address' => 'Park Royal, London, UK',
                ],
                [
                    'code' => 'WH-SECONDARY',
                    'name' => 'Secondary Warehouse',
                    'phone' => '+44 20 7777 6666',
                    'address' => 'Croydon, London, UK',
                ],
            ],
        ];

        /**
         * SEED PER COMPANY
         */
        foreach (Company::all() as $company) {

            /**
             * STORES
             */
            foreach ($locations['stores'] as $storeData) {

                /** @var Store $store */
                $store = Store::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'code' => $storeData['code'],
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'name' => $storeData['name'],
                        'phone' => $storeData['phone'],
                        'address' => $storeData['address'],
                        'is_active' => true,
                    ]
                );

                InventoryLocation::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'locatable_type' => Store::class,
                        'locatable_id' => $store->id,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'type' => 'store',
                        'is_active' => true,
                    ]
                );
            }

            /**
             * WAREHOUSES
             */
            foreach ($locations['warehouses'] as $warehouseData) {

                /** @var Warehouse $warehouse */
                $warehouse = Warehouse::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'code' => $warehouseData['code'],
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'name' => $warehouseData['name'],
                        'phone' => $warehouseData['phone'],
                        'address' => $warehouseData['address'],
                        'is_active' => true,
                    ]
                );

                InventoryLocation::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'locatable_type' => Warehouse::class,
                        'locatable_id' => $warehouse->id,
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'type' => 'warehouse',
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
