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
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_locations');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('stores');
    }
};
