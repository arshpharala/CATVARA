<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * POS ORDER STATUSES
         */
        Schema::create('pos_order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();     // DRAFT, COMPLETED, CANCELLED
            $table->string('name');
            $table->boolean('is_final')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /**
         * POS ORDERS (HEADER)
         */
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('user_id'); // cashier
            $table->unsignedBigInteger('status_id');

            $table->unsignedBigInteger('customer_id')->nullable();

            $table->string('order_number')->unique();

            // Currency snapshot
            $table->unsignedBigInteger('currency_id');

            /**
             * PAYMENT TERM SNAPSHOT (for invoice consistency)
             */
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->string('payment_term_code')->nullable();  // NET_30, COD, etc
            $table->string('payment_term_name')->nullable();  // Net 30, Cash On Delivery
            $table->unsignedInteger('payment_due_days')->default(0);
            $table->timestamp('due_date')->nullable();
            $table->boolean('is_credit_sale')->default(false);

            // Amount snapshots
            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('tax_total', 18, 6)->default(0);
            $table->decimal('discount_total', 18, 6)->default(0);
            $table->decimal('shipping_amount', 18, 6)->default(0);
            $table->decimal('grand_total', 18, 6)->default(0);

            // Exchange rate snapshot (future accounting use)
            $table->decimal('exchange_rate', 18, 8)->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status_id'], 'pos_company_status_idx');
            $table->index(['company_id', 'customer_id'], 'pos_company_customer_idx');
        });

        /**
         * POS ORDER ITEMS (LINES)
         */
        Schema::create('pos_order_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('pos_order_id');
            $table->unsignedBigInteger('product_variant_id');

            $table->decimal('unit_price', 18, 6);
            $table->integer('quantity');
            $table->decimal('line_total', 18, 6);
            $table->decimal('tax_amount', 18, 6)->default(0);

            $table->timestamps();
        });


        /**
         * FOREIGN KEYS (SHORT, PREFIX-SAFE)
         */
        Schema::table('pos_orders', function (Blueprint $table) {

            $table->foreign('company_id', 'pos_company_fk')
                ->references('id')->on('companies')
                ->cascadeOnDelete();

            $table->foreign('store_id', 'pos_store_fk')
                ->references('id')->on('stores')
                ->restrictOnDelete();

            $table->foreign('user_id', 'pos_user_fk')
                ->references('id')->on('users')
                ->restrictOnDelete();

            $table->foreign('currency_id', 'pos_currency_fk')
                ->references('id')->on('currencies')
                ->restrictOnDelete();

            $table->foreign('status_id', 'pos_status_fk')
                ->references('id')->on('pos_order_statuses')
                ->restrictOnDelete();

            // Payment term FK (short)
            $table->foreign('payment_term_id', 'pos_pt_fk')
                ->references('id')->on('payment_terms')
                ->nullOnDelete();
        });

        Schema::table('pos_order_items', function (Blueprint $table) {

            $table->foreign('pos_order_id', 'poi_order_fk')
                ->references('id')->on('pos_orders')
                ->cascadeOnDelete();

            $table->foreign('product_variant_id', 'poi_variant_fk')
                ->references('id')->on('product_variants')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->dropForeign('poi_order_fk');
            $table->dropForeign('poi_variant_fk');
        });

        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropForeign('pos_company_fk');
            $table->dropForeign('pos_store_fk');
            $table->dropForeign('pos_user_fk');
            $table->dropForeign('pos_currency_fk');
            $table->dropForeign('pos_status_fk');
            $table->dropForeign('pos_pt_fk');
        });

        Schema::dropIfExists('pos_order_items');
        Schema::dropIfExists('pos_orders');
        Schema::dropIfExists('pos_order_statuses');
    }
};
