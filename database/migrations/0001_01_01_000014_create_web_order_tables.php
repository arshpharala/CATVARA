<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * WEB ORDER STATUSES
         */
        Schema::create('web_order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();     // DRAFT, PENDING_PAYMENT, PAID, CANCELLED, FULFILLED
            $table->string('name');               // Draft, Pending Payment, Paid
            $table->boolean('is_final')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /**
         * WEB ORDERS
         */
        Schema::create('web_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('status_id');

            $table->string('order_number')->unique();

            $table->unsignedBigInteger('currency_id');

            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('tax_total', 18, 6)->default(0);
            $table->decimal('shipping_amount', 18, 6)->default(0);
            $table->decimal('grand_total', 18, 6)->default(0);

            $table->timestamp('placed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'customer_id'], 'web_company_customer_idx');
        });

        /**
         * WEB ORDER ITEMS
         */
        Schema::create('web_order_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('web_order_id');
            $table->unsignedBigInteger('product_variant_id');

            // Snapshot fields
            $table->string('product_name');
            $table->string('variant_description')->nullable();

            $table->decimal('unit_price', 18, 6);
            $table->integer('quantity');
            $table->decimal('line_total', 18, 6);
            $table->decimal('tax_amount', 18, 6)->default(0);

            $table->timestamps();
        });

        /**
         * WEB ORDER ADDRESSES (SNAPSHOT)
         */
        Schema::create('web_order_addresses', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('web_order_id');
            $table->enum('type', ['BILLING', 'SHIPPING']);

            $table->string('contact_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country_code', 2);

            $table->timestamps();
        });

        /**
         * WEB PAYMENTS
         */
        Schema::create('web_payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('web_order_id');

            $table->string('method'); // stripe, paypal, cod
            $table->string('status'); // initiated, success, failed
            $table->decimal('amount', 18, 6);

            $table->string('gateway_reference')->nullable();
            $table->json('gateway_payload')->nullable();

            $table->timestamps();
        });

        /**
         * FOREIGN KEYS (SHORT & SAFE)
         */
        Schema::table('web_orders', function (Blueprint $table) {
            $table->foreign('company_id', 'web_company_fk')
                ->references('id')->on('companies')
                ->cascadeOnDelete();

            $table->foreign('customer_id', 'web_customer_fk')
                ->references('id')->on('customers')
                ->restrictOnDelete();

            $table->foreign('status_id', 'web_status_fk')
                ->references('id')->on('web_order_statuses')
                ->restrictOnDelete();

            $table->foreign('currency_id', 'web_currency_fk')
                ->references('id')->on('currencies')
                ->restrictOnDelete();
        });

        Schema::table('web_order_items', function (Blueprint $table) {
            $table->foreign('web_order_id', 'web_item_order_fk')
                ->references('id')->on('web_orders')
                ->cascadeOnDelete();

            $table->foreign('product_variant_id', 'web_item_variant_fk')
                ->references('id')->on('product_variants')
                ->restrictOnDelete();
        });

        Schema::table('web_order_addresses', function (Blueprint $table) {
            $table->foreign('web_order_id', 'web_addr_order_fk')
                ->references('id')->on('web_orders')
                ->cascadeOnDelete();
        });

        Schema::table('web_payments', function (Blueprint $table) {
            $table->foreign('web_order_id', 'web_pay_order_fk')
                ->references('id')->on('web_orders')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_payments');
        Schema::dropIfExists('web_order_addresses');
        Schema::dropIfExists('web_order_items');
        Schema::dropIfExists('web_orders');
        Schema::dropIfExists('web_order_statuses');
    }
};
