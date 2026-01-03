<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Company\Company;
use App\Models\Catalog\Category;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductVariant;
use App\Models\Catalog\Attribute;
use App\Models\Catalog\AttributeValue;
use App\Models\Pricing\VariantPrice;
use App\Models\Pricing\PriceChannel;
use App\Models\Pricing\Currency;
use App\Models\Attachment;

class VapeShopSeeder extends Seeder
{
    public function run()
    {
        $jsonPath = database_path('seeders/data/products_full.json');
        
        if (!File::exists($jsonPath)) {
            $this->command->error("File not found: $jsonPath");
            return;
        }

        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!isset($data['products'])) {
            $this->command->error("Invalid JSON format");
            return;
        }

        // FIND COMPANY BY CODE
        $company = Company::where('code', 'UK-VAPE')->first();
        if (!$company) {
             $this->command->error("Company 'UK-VAPE' not found. Ensure DatabaseSeeder runs CompanySeeder.");
             return;
        }

        $currency = Currency::first();
        $channel = PriceChannel::where('code', 'WEBSITE')->first() ?? PriceChannel::first();

        // ATTRIBUTE NORMALIZATION MAP
        $attrMap = [
            'choose color' => 'Color',
            'choose colour' => 'Color',
            'choose colors' => 'Color',
            'colour' => 'Color',
            'select color' => 'Color',
            'select colour' => 'Color',
            'choose flavor' => 'Flavor',
            'choose flavour' => 'Flavor',
            'flavour' => 'Flavor',
            'select flavor' => 'Flavor',
            'strength' => 'Nicotine',
            'nicotine strength' => 'Nicotine',
            'mg' => 'Nicotine',
        ];

        foreach ($data['products'] as $item) {
            try {
                $this->command->info("Importing: " . $item['title']);

                // 1. Category
                $categoryName = $item['product_type'] ?: 'Uncategorized';
                $categorySlug = Str::slug($categoryName);
                
                $category = Category::firstOrCreate(
                    ['company_id' => $company->id, 'slug' => $categorySlug],
                    [
                        'name' => $categoryName, 
                        'is_active' => true
                    ]
                );

                // 2. Product
                $product = Product::updateOrCreate(
                    ['company_id' => $company->id, 'name' => $item['title']],
                    [
                        'uuid' => Str::uuid(),
                        'category_id' => $category->id,
                        'slug' => $item['handle'],
                        'description' => $item['body_html'],
                        'is_active' => true
                    ]
                );

                // 3. IMAGES (Download Primary Image)
                // if (!empty($item['images'][0]['src'])) {
                //     $imageUrl = $item['images'][0]['src'];
                //     $ext = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                //     // Remove query params from ext if any
                //     if(strpos($ext, '?') !== false) {
                //         $ext = explode('?', $ext)[0];
                //     }
                    
                //     $fileName = $product->uuid . '.' . $ext;
                //     $diskPath = 'products/' . $fileName;

                //     // Download if not exists
                //     if (!Storage::disk('public')->exists($diskPath)) {
                //         $this->command->warn("Downloading image for {$product->name}...");
                //         try {
                //             $contents = @file_get_contents($imageUrl, false, stream_context_create([
                //                 "http" => ["header" => "User-Agent: Mozilla/5.0\r\n"]
                //             ]));
                            
                //             if ($contents) {
                //                 Storage::disk('public')->put($diskPath, $contents);
                //             }
                //         } catch (\Exception $imgErr) {
                //             $this->command->error("Failed to download image: " . $imgErr->getMessage());
                //         }
                //     }

                //     // Attach Logic
                //     if (Storage::disk('public')->exists($diskPath)) {
                //         Attachment::firstOrCreate(
                //             [
                //                 'attachable_type' => Product::class,
                //                 'attachable_id' => $product->id,
                //                 'company_id' => $company->id,
                //             ],
                //             [
                //                 'disk' => 'public',
                //                 'path' => $diskPath,
                //                 'file_name' => $fileName,
                //                 'mime_type' => 'image/' . $ext,
                //                 'is_primary' => true,
                //             ]
                //         );
                //     }
                // }

                // 4. Attributes & Variants
                foreach ($item['variants'] as $variantData) {
                    $sku = $variantData['sku'] ?: ($product->slug . '-' . $variantData['id']);

                    $variant = ProductVariant::updateOrCreate(
                        ['company_id' => $company->id, 'sku' => $sku],
                        [
                            'uuid' => Str::uuid(),
                            'product_id' => $product->id,
                            'cost_price' => ((float)$variantData['price']) * 0.5,
                            'barcode' => $variantData['barcode'] ?? null,
                            'is_active' => true
                        ]
                    );

                    VariantPrice::updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'product_variant_id' => $variant->id,
                            'price_channel_id' => $channel->id,
                            'currency_id' => $currency->id
                        ],
                        [
                            'price' => $variantData['price'],
                            'valid_from' => now(),
                            'is_active' => true
                        ]
                    );

                    // Options
                    if (isset($item['options'])) {
                        foreach ($item['options'] as $index => $optDefinition) {
                            $rawName = $optDefinition['name'];
                            
                            // Normalize Name
                            $normName = $rawName;
                            $lowerName = strtolower(trim($rawName));
                            if (isset($attrMap[$lowerName])) {
                                $normName = $attrMap[$lowerName];
                            }

                            $optionValue = $variantData['option' . ($index + 1)] ?? null;

                            // Skip "Default Title" or empty
                            if ($optionValue && $optionValue !== 'Default Title') {
                                
                                $attrCode = Str::slug($normName);
                                
                                $attribute = Attribute::firstOrCreate(
                                    ['company_id' => $company->id, 'code' => $attrCode],
                                    [
                                        'name' => $normName, // Use Normalized Name
                                    ]
                                );
                                
                                // Ensure Category Linkage
                                if (!$category->attributes()->where('attribute_id', $attribute->id)->exists()) {
                                    $category->attributes()->attach($attribute->id);
                                }

                                $attrValue = AttributeValue::firstOrCreate(
                                    ['attribute_id' => $attribute->id, 'value' => $optionValue],
                                    []
                                );
                                
                                if (!$variant->attributeValues()->where('attribute_value_id', $attrValue->id)->exists()) {
                                    $variant->attributeValues()->attach($attrValue->id);
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->command->error("Failed importing " . $item['title'] . ": " . $e->getMessage());
            }
        }
    }
}
