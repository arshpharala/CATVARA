<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * CATEGORIES
         */
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'slug'], 'cat_company_slug_unique');
        });

        /**
         * PRODUCTS (SPU / DISPLAY LEVEL)
         */
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'slug'], 'prod_company_slug_unique');
        });

        /**
         * ATTRIBUTES (Color, Size, etc.)
         */
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('code');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'code'], 'attr_company_code_unique');
        });

        /**
         * ATTRIBUTE VALUES (Red, XL, etc.)
         */
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attribute_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('value');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        /**
         * CATEGORY ↔ ATTRIBUTE (Allowed attributes per category)
         */
        Schema::create('category_attributes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('attribute_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['category_id', 'attribute_id'],
                'cat_attr_unique'
            );
        });

        /**
         * PRODUCT VARIANTS (SKU LEVEL)
         */
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('sku');
            $table->string('barcode')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'sku'], 'variant_company_sku_unique');
        });

        /**
         * VARIANT ↔ ATTRIBUTE VALUES (Combination mapping)
         */
        Schema::create('product_variant_attribute_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('attribute_value_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            // Explicit index name to avoid MySQL/MariaDB 64-char limit
            $table->unique(
                ['product_variant_id', 'attribute_value_id'],
                'pv_attr_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_attribute_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('category_attributes');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
