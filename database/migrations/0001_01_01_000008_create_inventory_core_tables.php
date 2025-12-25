<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * INVENTORY TRANSFER STATUSES
         */
        Schema::create('inventory_transfer_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();   // DRAFT, APPROVED, SHIPPED, RECEIVED, CLOSED, CANCELLED
            $table->string('name');
            $table->boolean('is_final')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        /**
         * INVENTORY REASONS
         */
        Schema::create('inventory_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_increase');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        /**
         * INVENTORY BALANCES
         */
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('inventory_location_id')
                ->constrained('inventory_locations')
                ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->decimal('quantity', 18, 6)->default(0);
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['company_id', 'inventory_location_id', 'product_variant_id'],
                'inv_bal_unique'
            );
        });

        /**
         * INVENTORY MOVEMENTS (LEDGER)
         */
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('inventory_location_id')
                ->constrained('inventory_locations')
                ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->foreignId('inventory_reason_id')
                ->constrained('inventory_reasons')
                ->restrictOnDelete();

            $table->decimal('quantity', 18, 6);
            $table->decimal('unit_cost', 18, 6)->nullable();

            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('idempotency_key')->nullable();

            $table->foreignId('performed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamp('posted_at')->useCurrent();
            $table->timestamps();

            $table->unique(
                ['company_id', 'idempotency_key'],
                'inv_mov_idem_unique'
            );
        });

        /**
         * INVENTORY TRANSFERS (BUSINESS DOCUMENT)
         */
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('from_location_id')
                ->constrained('inventory_locations')
                ->restrictOnDelete();

            $table->foreignId('to_location_id')
                ->constrained('inventory_locations')
                ->restrictOnDelete();

            // ✅ NORMALIZED STATUS
            $table->unsignedBigInteger('status_id');

            $table->string('transfer_no')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status_id'], 'inv_tr_comp_status_idx');
        });

        /**
         * TRANSFER ITEMS
         */
        Schema::create('inventory_transfer_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_transfer_id')
                ->constrained('inventory_transfers')
                ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->restrictOnDelete();

            $table->decimal('quantity', 18, 6);
            $table->decimal('received_quantity', 18, 6)->default(0);

            $table->timestamps();

            $table->unique(
                ['inventory_transfer_id', 'product_variant_id'],
                'inv_tr_item_unique'
            );
        });

        /**
         * FOREIGN KEY FOR TRANSFER STATUS
         */
        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->foreign('status_id', 'inv_tr_status_fk')
                ->references('id')
                ->on('inventory_transfer_statuses')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->dropForeign('inv_tr_status_fk');
        });

        Schema::dropIfExists('inventory_transfer_items');
        Schema::dropIfExists('inventory_transfers');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('inventory_reasons');
        Schema::dropIfExists('inventory_transfer_statuses');
    }
};
