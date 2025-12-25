<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

use App\Models\Company\Company;
use App\Models\Catalog\ProductVariant;
use App\Models\Inventory\Store;
use App\Models\Pricing\{
    Currency,
    ExchangeRate,
    PriceChannel,
    VariantPrice,
    StoreVariantPrice
};

class PricingSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * 1️⃣ CURRENCIES
         */
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
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

        /**
         * 2️⃣ EXCHANGE RATES (BASE: USD)
         */
        $usd = Currency::where('code', 'USD')->first();
        $gbp = Currency::where('code', 'GBP')->first();
        $eur = Currency::where('code', 'EUR')->first();

        $rates = [
            ['base' => $usd, 'target' => $gbp, 'rate' => 0.78],
            ['base' => $usd, 'target' => $eur, 'rate' => 0.92],
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

        /**
         * 3️⃣ PRICE CHANNELS
         */
        $channels = [
            ['code' => 'POS', 'name' => 'Point of Sale'],
            ['code' => 'WEBSITE', 'name' => 'Website'],
            ['code' => 'B2B', 'name' => 'B2B Wholesale'],
        ];

        foreach ($channels as $ch) {
            PriceChannel::updateOrCreate(
                ['code' => $ch['code']],
                [
                    'name' => $ch['name'],
                    'is_active' => true,
                ]
            );
        }

        /**
         * 4️⃣ VARIANT PRICES (PER COMPANY)
         */
        $posChannel = PriceChannel::where('code', 'POS')->first();
        $webChannel = PriceChannel::where('code', 'WEBSITE')->first();

        foreach (Company::all() as $company) {

            $variants = ProductVariant::where('company_id', $company->id)->get();
            $stores   = Store::where('company_id', $company->id)->get();

            foreach ($variants as $variant) {

                /**
                 * GLOBAL PRICE (USD, WEBSITE)
                 */
                $globalPrice = VariantPrice::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'product_variant_id' => $variant->id,
                        'price_channel_id' => $webChannel->id,
                        'currency_id' => $usd->id,
                        'country_code' => null,
                        'valid_from' => Carbon::today(),
                    ],
                    [
                        'price' => 999.00,
                        'is_active' => true,
                    ]
                );

                /**
                 * COUNTRY PRICE (UK, POS, GBP)
                 */
                $ukPosPrice = VariantPrice::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'product_variant_id' => $variant->id,
                        'price_channel_id' => $posChannel->id,
                        'currency_id' => $gbp->id,
                        'country_code' => 'UK',
                        'valid_from' => Carbon::today(),
                    ],
                    [
                        'price' => 899.00,
                        'is_active' => true,
                    ]
                );

                /**
                 * 5️⃣ STORE-SPECIFIC OVERRIDE (FIRST STORE ONLY)
                 */
                if ($stores->isNotEmpty()) {
                    StoreVariantPrice::updateOrCreate(
                        [
                            'store_id' => $stores->first()->id,
                            'variant_price_id' => $ukPosPrice->id,
                        ],
                        [
                            'price_override' => 879.00,
                        ]
                    );
                }
            }
        }
    }
}
