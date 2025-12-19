<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

use App\Models\Company\Company;
use App\Models\Catalog\Category;
use App\Models\Catalog\Product;
use App\Models\Catalog\ProductVariant;
use App\Models\Catalog\Attribute;
use App\Models\Catalog\AttributeValue;
use App\Models\Catalog\CategoryAttribute;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * MASTER CATALOG DATA (STRUCTURED)
         */
        $catalog = [
            'categories' => [
                [
                    'slug' => 'electronics',
                    'name' => 'Electronics',
                    'attributes' => [
                        'color' => [
                            'name' => 'Color',
                            'values' => ['Black', 'White'],
                        ],
                        'storage' => [
                            'name' => 'Storage',
                            'values' => ['128GB', '256GB'],
                        ],
                    ],
                    'products' => [
                        [
                            'slug' => 'iphone-15',
                            'name' => 'iPhone 15',
                            'description' => 'Latest generation Apple iPhone',
                            'variants' => [
                                [
                                    'sku' => 'IP15-BLK-128',
                                    'attributes' => [
                                        'color' => 'Black',
                                        'storage' => '128GB',
                                    ],
                                ],
                                [
                                    'sku' => 'IP15-BLK-256',
                                    'attributes' => [
                                        'color' => 'Black',
                                        'storage' => '256GB',
                                    ],
                                ],
                                [
                                    'sku' => 'IP15-WHT-128',
                                    'attributes' => [
                                        'color' => 'White',
                                        'storage' => '128GB',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        /**
         * SEED PER COMPANY (TENANCY SAFE)
         */
        foreach (Company::all() as $company) {

            foreach ($catalog['categories'] as $categoryData) {

                /**
                 * CATEGORY
                 */
                $category = Category::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'slug' => $categoryData['slug'],
                    ],
                    [
                        'name' => $categoryData['name'],
                        'is_active' => true,
                    ]
                );

                /**
                 * ATTRIBUTES + VALUES
                 */
                $attributeMap = [];

                foreach ($categoryData['attributes'] as $attrCode => $attrData) {

                    $attribute = Attribute::updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'code' => $attrCode,
                        ],
                        [
                            'name' => $attrData['name'],
                            'is_active' => true,
                        ]
                    );

                    CategoryAttribute::updateOrCreate([
                        'category_id' => $category->id,
                        'attribute_id' => $attribute->id,
                    ]);

                    foreach ($attrData['values'] as $index => $value) {
                        $attrValue = AttributeValue::updateOrCreate(
                            [
                                'attribute_id' => $attribute->id,
                                'value' => $value,
                            ],
                            [
                                'sort_order' => $index + 1,
                                'is_active' => true,
                            ]
                        );

                        $attributeMap[$attrCode][$value] = $attrValue;
                    }
                }

                /**
                 * PRODUCTS
                 */
                foreach ($categoryData['products'] as $productData) {

                    $product = Product::updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'slug' => $productData['slug'],
                        ],
                        [
                            'uuid' => Str::uuid(),
                            'category_id' => $category->id,
                            'name' => $productData['name'],
                            'description' => $productData['description'],
                            'is_active' => true,
                        ]
                    );

                    /**
                     * VARIANTS
                     */
                    foreach ($productData['variants'] as $variantData) {

                        $variant = ProductVariant::updateOrCreate(
                            [
                                'company_id' => $company->id,
                                'sku' => $variantData['sku'],
                            ],
                            [
                                'uuid' => Str::uuid(),
                                'product_id' => $product->id,
                                'is_active' => true,
                            ]
                        );

                        /**
                         * VARIANT ATTRIBUTE MAPPING
                         */
                        $attributeValueIds = [];

                        foreach ($variantData['attributes'] as $code => $value) {
                            $attributeValueIds[] =
                                $attributeMap[$code][$value]->id;
                        }

                        $variant->attributeValues()
                            ->syncWithoutDetaching($attributeValueIds);
                    }
                }
            }
        }
    }
}
