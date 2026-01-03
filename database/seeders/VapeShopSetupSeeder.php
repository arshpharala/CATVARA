<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Company\Company;
use App\Models\Company\CompanyDetail;
use App\Models\Company\CompanyStatus;
use App\Models\Accounting\PaymentTerm;
use App\Models\Accounting\CompanyPaymentTerm;
use App\Models\Pricing\Currency;
use App\Models\Pricing\ExchangeRate;
use App\Models\Pricing\PriceChannel;
use App\Models\Inventory\Store;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\InventoryLocation;
use App\Models\Auth\Role;

class VapeShopSetupSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * 1️⃣ COMPANY STATUSES & PAYMENT TERMS
         */
        $this->command->info('Creating Statuses & Payment Terms...');
        
        $activeStatus = CompanyStatus::updateOrCreate(
            ['code' => 'ACTIVE'],
            ['name' => 'Active', 'is_active' => true]
        );
        CompanyStatus::updateOrCreate(['code' => 'SUSPENDED'], ['name' => 'Suspended', 'is_active' => true]);
        CompanyStatus::updateOrCreate(['code' => 'DISSOLVED'], ['name' => 'Dissolved', 'is_active' => true]);

        $paymentTerms = [
            ['code' => 'IMMEDIATE', 'name' => 'Immediate Payment', 'due_days' => 0],
            ['code' => 'NET_7',     'name' => 'Net 7 Days',        'due_days' => 7],
            ['code' => 'NET_15',    'name' => 'Net 15 Days',       'due_days' => 15],
            ['code' => 'NET_30',    'name' => 'Net 30 Days',       'due_days' => 30],
            ['code' => 'NET_60',    'name' => 'Net 60 Days',       'due_days' => 60],
        ];

        foreach ($paymentTerms as $term) {
            PaymentTerm::updateOrCreate(
                ['code' => $term['code']],
                array_merge($term, ['is_active' => true])
            );
        }

        /**
         * 2️⃣ CORE PRICING (Currencies, Rates, Channels)
         */
        $this->command->info('Creating Currencies & Channels...');

        $currencies = [
          ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        ];

        foreach ($currencies as $cur) {
            Currency::updateOrCreate(
                ['code' => $cur['code']],
                [
                    'name' => $cur['name'],
                    'symbol' => $cur['symbol'],
                    'decimal_places' => 2,
                    'is_active' => true,
                ]
            );
        }

        // Exchange Rates (Base GBP)
        $gbp = Currency::where('code', 'GBP')->first();
        $usd = Currency::where('code', 'USD')->first();
        $eur = Currency::where('code', 'EUR')->first();

        $rates = [
            ['base' => $gbp, 'target' => $usd, 'rate' => 1.28],
            ['base' => $gbp, 'target' => $eur, 'rate' => 1.12],
        ];

        foreach ($rates as $rate) {
            ExchangeRate::updateOrCreate(
                [
                    'base_currency_id' => $rate['base']->id,
                    'target_currency_id' => $rate['target']->id,
                    'effective_date' => Carbon::today(),
                ],
                [
                    'rate' => $rate['rate'],
                    'source' => 'MANUAL',
                ]
            );
        }

        // Price Channels
        $channels = [
            ['code' => 'POS', 'name' => 'Point of Sale'],
            ['code' => 'WEBSITE', 'name' => 'Website'],
            ['code' => 'B2B', 'name' => 'B2B Wholesale'],
        ];

        foreach ($channels as $ch) {
            PriceChannel::updateOrCreate(
                ['code' => $ch['code']],
                ['name' => $ch['name'], 'is_active' => true]
            );
        }

        /**
         * 3️⃣ SINGLE COMPANY: Vape Shop Distro
         */
        $this->command->info('Creating Company: Vape Shop Distro...');

        $company = Company::updateOrCreate(
            ['code' => 'UK-VAPE'],
            [
                'uuid' => Str::uuid(),
                'name' => 'Vape Shop Distro',
                'legal_name' => 'Vape Shop Distro UK',
                'website_url' => 'https://vapeshopdistro.co.uk',
                'company_status_id' => $activeStatus->id,
            ]
        );

        CompanyDetail::updateOrCreate(
            ['company_id' => $company->id],
            [
                'address' => '111-113 Great Bridge St, West Bromwich B70 0DA, United Kingdom',
                'tax_number' => 'GB348681169',
                'invoice_prefix' => 'UK',
                'quote_prefix' => 'QT',
                'invoice_postfix' => date('Y'),
                'quote_postfix' => date('Y'),
            ]
        );

        // Assign Payment Terms
        $defaultTerm = PaymentTerm::where('code', 'IMMEDIATE')->first();
        PaymentTerm::all()->each(function ($term) use ($company, $defaultTerm) {
            CompanyPaymentTerm::updateOrCreate(
                ['company_id' => $company->id, 'payment_term_id' => $term->id],
                ['is_default' => $term->id === $defaultTerm->id]
            );
        });
        
        // Create Admin Role (Requested Change)
        Role::updateOrCreate(
            ['company_id' => $company->id, 'slug' => 'admin'],
            ['name' => 'Admin', 'is_active' => true]
        );

        /**
         * 4️⃣ INVENTORY LOCATIONS (Stores & Warehouses)
         */
        $this->command->info('Creating Stores & Warehouses...');

        // Stores
        $stores = [
            [
                'code' => 'STORE-MAIN',
                'name' => 'Main Store',
                'phone' => '+44 7000 000000',
                'address' => '111-113 Great Bridge St, West Bromwich',
            ]
        ];

        foreach ($stores as $storeData) {
            $store = Store::updateOrCreate(
                ['company_id' => $company->id, 'code' => $storeData['code']],
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

        // Warehouses
        $warehouses = [
            [
                'code' => 'WH-MAIN',
                'name' => 'Main Warehouse',
                'phone' => '+44 7000 111111',
                'address' => 'Rear of 111-113 Great Bridge St',
            ]
        ];

        foreach ($warehouses as $whData) {
            $wh = Warehouse::updateOrCreate(
                ['company_id' => $company->id, 'code' => $whData['code']],
                [
                    'uuid' => Str::uuid(),
                    'name' => $whData['name'],
                    'phone' => $whData['phone'],
                    'address' => $whData['address'],
                    'is_active' => true,
                ]
            );

            InventoryLocation::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'locatable_type' => Warehouse::class,
                    'locatable_id' => $wh->id,
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
