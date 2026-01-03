<?php

namespace App\Services\Catalog;

use App\Models\Catalog\Product;
use App\Models\Catalog\ProductVariant;
use App\Models\Pricing\VariantPrice;
use App\Models\Pricing\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Update product, variants, and prices in a transaction.
     */
    public function updateProduct(Product $product, array $data)
    {
        return DB::transaction(function () use ($product, $data) {
            // 1. Update Core Product
            $product->update([
                'name' => $data['name'],
                'category_id' => $data['category_id'],
                'description' => $data['description'] ?? $product->description,
            ]);

            // 2. Variants Update
            if (!empty($data['variants'])) {
                foreach ($data['variants'] as $vid => $vData) {
                    $variant = ProductVariant::where('product_id', $product->id)->find($vid);
                    if ($variant) {
                        $variant->update([
                            'sku' => $vData['sku'],
                            'cost_price' => $vData['cost_price'] ?? 0,
                            // 'barcode' => ...
                        ]);
                    }
                }
            }

            // 3. Prices Update
            // data['prices'] = [variant_id => [channel_id => price]]
            if (!empty($data['prices'])) {
                $currency = Currency::where('code', 'GBP')->first(); // Default base currency
                // In a multi-currency system, this would be passed in or resolved from context
                
                foreach ($data['prices'] as $vid => $channelsData) {
                    foreach ($channelsData as $channelId => $priceVal) {
                        if (is_numeric($priceVal)) {
                            VariantPrice::updateOrCreate(
                                [
                                    'company_id' => $product->company_id,
                                    'product_variant_id' => $vid,
                                    'price_channel_id' => $channelId,
                                    'currency_id' => $currency->id,
                                ],
                                [
                                    'price' => $priceVal,
                                    'valid_from' => now(),
                                    'is_active' => true
                                ]
                            );
                        }
                    }
                }
            }

            return $product;
        });
    }

    /**
     * Create product logic (extracted from controller if needed later)
     * For now, focusing on the Requested "Product Edit" refactor.
     */
}
