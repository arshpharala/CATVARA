<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * CURRENCIES (GLOBAL)
         */
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();

            $table->string('code', 3)->unique(); // USD, GBP
            $table->string('name');
            $table->string('symbol', 5)->nullable();
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        /**
         * EXCHANGE RATES (HISTORICAL, APPEND-ONLY)
         */
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('base_currency_id');
            $table->foreignId('target_currency_id');

            $table->decimal('rate', 18, 8);
            $table->date('effective_date');
            $table->string('source')->nullable(); // ECB, API, MANUAL

            $table->timestamps();

            $table->unique(
                ['base_currency_id', 'target_currency_id', 'effective_date'],
                'ex_rate_unique'
            );
        });

        /**
         * PRICE CHANNELS (POS, WEBSITE, B2B)
         */
        Schema::create('price_channels', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique(); // POS, WEBSITE, B2B
            $table->string('name');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        /**
         * VARIANT PRICES (CORE PRICING TABLE)
         */
        Schema::create('variant_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id');
            $table->foreignId('product_variant_id');
            $table->foreignId('price_channel_id');
            $table->foreignId('currency_id');

            $table->char('country_code', 2)->nullable(); // US, UK, AE

            $table->decimal('price', 18, 6);

            $table->date('valid_from');
            $table->date('valid_to')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(
                ['company_id', 'product_variant_id'],
                'vp_company_variant_idx'
            );
        });

        /**
         * STORE-SPECIFIC PRICE OVERRIDES
         */
        Schema::create('store_variant_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id');
            $table->foreignId('variant_price_id');

            $table->decimal('price_override', 18, 6);

            $table->timestamps();

            $table->unique(
                ['store_id', 'variant_price_id'],
                'store_vp_unique'
            );
        });

        /**
         * ADD FKs WITH SHORT NAMES (PREFIX SAFE)
         */
        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->foreign('base_currency_id', 'ex_base_cur_fk')
                ->references('id')->on('currencies')
                ->cascadeOnDelete();

            $table->foreign('target_currency_id', 'ex_target_cur_fk')
                ->references('id')->on('currencies')
                ->cascadeOnDelete();
        });

        Schema::table('variant_prices', function (Blueprint $table) {
            $table->foreign('company_id', 'vp_company_fk')
                ->references('id')->on('companies')
                ->cascadeOnDelete();

            $table->foreign('product_variant_id', 'vp_variant_fk')
                ->references('id')->on('product_variants')
                ->cascadeOnDelete();

            $table->foreign('price_channel_id', 'vp_channel_fk')
                ->references('id')->on('price_channels')
                ->restrictOnDelete();

            $table->foreign('currency_id', 'vp_currency_fk')
                ->references('id')->on('currencies')
                ->restrictOnDelete();
        });

        Schema::table('store_variant_prices', function (Blueprint $table) {
            $table->foreign('store_id', 'svp_store_fk')
                ->references('id')->on('stores')
                ->cascadeOnDelete();

            $table->foreign('variant_price_id', 'svp_vp_fk')
                ->references('id')->on('variant_prices')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_variant_prices');
        Schema::dropIfExists('variant_prices');
        Schema::dropIfExists('price_channels');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
