<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * INVENTORY REASONS
         * Master list of why stock changed (audit + reporting)
         */
        Schema::create('inventory_reasons', function (Blueprint $table) {
            $table->id();

            $table->string('name');                // Sale, Transfer, Adjustment
            $table->string('code')->unique();      // SALE, TRANSFER_OUT, ADJUSTMENT_IN
            $table->boolean('is_increase');        // true => +qty, false => -qty (default direction)
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });

        /**
         * INVENTORY BALANCES
         * Cache table for fast reads. Source of truth is MOVEMENTS.
         * Unique per (company, location, variant).
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

            // Use DECIMAL for enterprise flexibility (pieces now, weight later)
            $table->decimal('quantity', 18, 6)->default(0);

            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['company_id', 'inventory_location_id', 'product_variant_id'],
                'inv_bal_unique'
            );

            $table->index(['company_id', 'inventory_location_id'], 'inv_bal_company_loc_idx');
            $table->index(['company_id', 'product_variant_id'], 'inv_bal_company_var_idx');
        });

        /**
         * INVENTORY MOVEMENTS (LEDGER)
         * Append-only. Never update/delete. Correct via reversing movements.
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

            // Signed quantity: + adds stock, - reduces stock
            $table->decimal('quantity', 18, 6);

            // Optional: cost snapshot later (not pricing; just valuation if you add it)
            $table->decimal('unit_cost', 18, 6)->nullable();

            // Reference to business object (Order, POS Sale, Transfer, Adjustment, Purchase)
            $table->string('reference_type')->nullable(); // e.g. App\Models\Sales\Order
            $table->unsignedBigInteger('reference_id')->nullable();

            // Idempotency: prevents duplicates on retries (very important)
            $table->string('idempotency_key')->nullable();

            // Who performed this movement (nullable for system jobs/import)
            $table->foreignId('performed_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Business time vs system time
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamp('posted_at')->useCurrent();

            $table->timestamps(); // created_at = insert time; keep for dev ergonomics

            $table->index(['company_id', 'inventory_location_id'], 'inv_mov_company_loc_idx');
            $table->index(['company_id', 'product_variant_id'], 'inv_mov_company_var_idx');
            $table->index(['company_id', 'inventory_reason_id'], 'inv_mov_company_reason_idx');

            // Keep short to avoid 64-char limit with ec_ prefix
            $table->unique(['company_id', 'idempotency_key'], 'inv_mov_idem_unique');
        });

        /**
         * INVENTORY TRANSFERS (BUSINESS DOCUMENT)
         * One transfer produces movements when shipped/received depending on workflow.
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

            // DRAFT -> APPROVED -> SHIPPED -> RECEIVED -> CANCELLED
            $table->string('status')->default('DRAFT');

            $table->string('transfer_no')->nullable(); // optional numbering later

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status'], 'inv_tr_company_status_idx');
            $table->index(['company_id', 'from_location_id'], 'inv_tr_company_from_idx');
            $table->index(['company_id', 'to_location_id'], 'inv_tr_company_to_idx');
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

            $table->unique(['inventory_transfer_id', 'product_variant_id'], 'inv_tr_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_items');
        Schema::dropIfExists('inventory_transfers');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('inventory_reasons');
    }
};
