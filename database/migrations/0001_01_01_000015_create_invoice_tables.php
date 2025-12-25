<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * INVOICE STATUSES
         */
        Schema::create('invoice_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // DRAFT, ISSUED, VOID, PAID, PARTIALLY_PAID, OVERDUE
            $table->string('name');
            $table->boolean('is_final')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /**
         * INVOICES (UNIFIED)
         * - source_type/source_id links to PosOrder / WebOrder / future B2B Order
         * - customer_id nullable (POS guest allowed)
         * - payment term snapshot fields included (no recalculation later)
         * - exchange rate snapshot optional (future accounting)
         */
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('store_id')->nullable(); // POS store, optional for web
            $table->unsignedBigInteger('customer_id')->nullable();

            $table->unsignedBigInteger('status_id');

            $table->string('invoice_number')->unique();

            // SOURCE LINK (polymorphic-like without morphs to keep FK simple)
            $table->string('source_type')->nullable(); // App\Models\Pos\PosOrder / App\Models\Web\WebOrder
            $table->unsignedBigInteger('source_id')->nullable();

            // Currency snapshot
            $table->unsignedBigInteger('currency_id');

            // Payment term snapshot (critical)
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->integer('payment_due_days')->default(0);
            $table->date('due_date')->nullable();

            // Amount snapshots
            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('tax_total', 18, 6)->default(0);
            $table->decimal('discount_total', 18, 6)->default(0);
            $table->decimal('shipping_amount', 18, 6)->default(0);
            $table->decimal('grand_total', 18, 6)->default(0);

            // Exchange rate snapshot (optional)
            $table->decimal('exchange_rate', 18, 8)->nullable();

            // Dates
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('voided_at')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'customer_id'], 'inv_comp_cust_idx');
            $table->index(['source_type', 'source_id'], 'inv_source_idx');
        });

        /**
         * INVOICE ITEMS (SNAPSHOT)
         * IMPORTANT: store product name/variant text here for audit (never breaks reporting if product changes later)
         */
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('product_variant_id')->nullable(); // allow non-catalog line items later

            $table->string('product_name');
            $table->string('variant_description')->nullable(); // e.g. Color: Red, Size: M
            $table->string('sku')->nullable();

            $table->decimal('unit_price', 18, 6);
            $table->integer('quantity');
            $table->decimal('line_total', 18, 6);

            $table->decimal('tax_amount', 18, 6)->default(0);
            $table->decimal('discount_amount', 18, 6)->default(0);

            $table->timestamps();
        });

        /**
         * INVOICE ADDRESSES (SNAPSHOT)
         * Useful for web orders; optional for POS
         */
        Schema::create('invoice_addresses', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('invoice_id');
            $table->enum('type', ['BILLING', 'SHIPPING']);

            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country_code', 2)->nullable();

            $table->timestamps();
        });

        /**
         * FOREIGN KEYS (SHORT NAMES FOR ec_ PREFIX + MARIADB LIMITS)
         */
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('company_id', 'inv_company_fk')
                ->references('id')->on('companies')->cascadeOnDelete();

            $table->foreign('store_id', 'inv_store_fk')
                ->references('id')->on('stores')->nullOnDelete();

            $table->foreign('customer_id', 'inv_customer_fk')
                ->references('id')->on('customers')->nullOnDelete();

            $table->foreign('status_id', 'inv_status_fk')
                ->references('id')->on('invoice_statuses')->restrictOnDelete();

            $table->foreign('currency_id', 'inv_currency_fk')
                ->references('id')->on('currencies')->restrictOnDelete();

            $table->foreign('payment_term_id', 'inv_term_fk')
                ->references('id')->on('payment_terms')->nullOnDelete();

            $table->foreign('created_by', 'inv_createdby_fk')
                ->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreign('invoice_id', 'invitem_inv_fk')
                ->references('id')->on('invoices')->cascadeOnDelete();

            $table->foreign('product_variant_id', 'invitem_var_fk')
                ->references('id')->on('product_variants')->nullOnDelete();
        });

        Schema::table('invoice_addresses', function (Blueprint $table) {
            $table->foreign('invoice_id', 'invaddr_inv_fk')
                ->references('id')->on('invoices')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_addresses', function (Blueprint $table) {
            $table->dropForeign('invaddr_inv_fk');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign('invitem_inv_fk');
            $table->dropForeign('invitem_var_fk');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign('inv_company_fk');
            $table->dropForeign('inv_store_fk');
            $table->dropForeign('inv_customer_fk');
            $table->dropForeign('inv_status_fk');
            $table->dropForeign('inv_currency_fk');
            $table->dropForeign('inv_term_fk');
            $table->dropForeign('inv_createdby_fk');
        });

        Schema::dropIfExists('invoice_addresses');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_statuses');
    }
};
