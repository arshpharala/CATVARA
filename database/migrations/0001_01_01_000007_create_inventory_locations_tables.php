<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * STORES
         */
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('code');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'code'], 'store_company_code_unique');
        });

        /**
         * WAREHOUSES
         */
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('code');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['company_id', 'code'], 'warehouse_company_code_unique');
        });

        /**
         * INVENTORY LOCATIONS (ABSTRACT)
         */
        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->morphs('locatable'); // Store or Warehouse

            $table->string('type'); // store | warehouse (explicit, for fast filtering)
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['company_id', 'locatable_type', 'locatable_id'],
                'company_location_unique'
            );
        });

        Schema::create('company_inventory_settings', function (Blueprint $table) {
            $table->id();

            // company_id FK (short name)
            $table->foreignId('company_id');
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('block_sale_if_no_stock')->default(true);

            $table->boolean('require_transfer_approval')->default(true);
            $table->boolean('auto_receive_transfer')->default(false);
            $table->boolean('allow_partial_transfer_receive')->default(true);

            // default inventory location
            $table->foreignId('default_inventory_location_id')->nullable();

            $table->timestamps();

            $table->unique('company_id', 'company_inventory_unique');
        });

        /**
         * ADD FKs SEPARATELY WITH SHORT NAMES
         */
        Schema::table('company_inventory_settings', function (Blueprint $table) {

            $table->foreign('company_id', 'cis_company_fk')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->foreign('default_inventory_location_id', 'cis_default_loc_fk')
                ->references('id')
                ->on('inventory_locations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('company_inventory_settings', function (Blueprint $table) {
            $table->dropForeign('cis_company_fk');
            $table->dropForeign('cis_default_loc_fk');
        });

        Schema::dropIfExists('company_inventory_settings');
        Schema::dropIfExists('inventory_locations');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('stores');
    }
};
