<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('company_id');
            $table->foreignId('store_id')->nullable(); // optional: website returns may not map to a store

            // Polymorphic order reference: POS / WEB / B2B
            $table->unsignedBigInteger('creditable_id');
            $table->string('creditable_type');

            $table->foreignId('user_id')->nullable(); // who issued the credit note (nullable for system/api)

            $table->string('credit_number')->unique();

            $table->enum('status', ['DRAFT', 'ISSUED', 'CANCELLED'])->default('DRAFT');

            $table->foreignId('currency_id');

            // Totals snapshot
            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('tax_total', 18, 6)->default(0);
            $table->decimal('discount_total', 18, 6)->default(0);
            $table->decimal('shipping_refund', 18, 6)->default(0);
            $table->decimal('grand_total', 18, 6)->default(0);

            $table->text('reason')->nullable();
            $table->timestamp('issued_at')->nullable();

            $table->timestamps();

            $table->index(['creditable_type', 'creditable_id'], 'cn_creditable_idx');
            $table->index(['company_id', 'status'], 'cn_company_status_idx');
        });

        Schema::create('credit_note_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('credit_note_id');
            $table->foreignId('product_variant_id');

            // Snapshots
            $table->decimal('unit_price', 18, 6);
            $table->integer('quantity');
            $table->decimal('line_total', 18, 6);

            $table->decimal('tax_amount', 18, 6)->default(0);

            // Optional linkage for traceability (POS line / web line)
            $table->unsignedBigInteger('source_item_id')->nullable();
            $table->string('source_item_type')->nullable();

            $table->timestamps();

            $table->unique(['credit_note_id', 'product_variant_id'], 'cni_note_variant_unique');
        });

        /**
         * FKs with short names (prefix-safe)
         */
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->foreign('company_id', 'cn_company_fk')
                ->references('id')->on('companies')
                ->cascadeOnDelete();

            $table->foreign('store_id', 'cn_store_fk')
                ->references('id')->on('stores')
                ->nullOnDelete();

            $table->foreign('user_id', 'cn_user_fk')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('currency_id', 'cn_currency_fk')
                ->references('id')->on('currencies')
                ->restrictOnDelete();
        });

        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->foreign('credit_note_id', 'cni_note_fk')
                ->references('id')->on('credit_notes')
                ->cascadeOnDelete();

            $table->foreign('product_variant_id', 'cni_variant_fk')
                ->references('id')->on('product_variants')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('credit_note_items', function (Blueprint $table) {
            $table->dropForeign('cni_note_fk');
            $table->dropForeign('cni_variant_fk');
        });

        Schema::table('credit_notes', function (Blueprint $table) {
            $table->dropForeign('cn_company_fk');
            $table->dropForeign('cn_store_fk');
            $table->dropForeign('cn_user_fk');
            $table->dropForeign('cn_currency_fk');
        });

        Schema::dropIfExists('credit_note_items');
        Schema::dropIfExists('credit_notes');
    }
};
