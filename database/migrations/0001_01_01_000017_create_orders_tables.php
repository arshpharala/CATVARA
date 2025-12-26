<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * ORDER STATUSES
         */
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // DRAFT, CONFIRMED, PARTIALLY_FULFILLED, FULFILLED, CANCELLED
            $table->string('name');
            $table->boolean('is_final')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /**
         * ORDER PAYMENT STATUSES
         */
        Schema::create('order_payment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // INITIATED, SUCCESS, FAILED, REFUNDED
            $table->string('name');
            $table->boolean('is_final')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /**
         * ORDERS (HEADER)
         */
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('status_id');

            // Origin tracking
            $table->string('source')->nullable(); // QUOTE, POS, WEB, MANUAL
            $table->unsignedBigInteger('source_id')->nullable();

            $table->string('order_number')->unique();

            $table->unsignedBigInteger('currency_id');

            // Payment term snapshot (CRITICAL)
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->string('payment_term_name')->nullable();
            $table->integer('payment_due_days')->nullable();
            $table->date('due_date')->nullable();

            // Totals snapshot
            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('tax_total', 18, 6)->default(0);
            $table->decimal('discount_total', 18, 6)->default(0);
            $table->decimal('grand_total', 18, 6)->default(0);

            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status_id'], 'order_company_status_idx');
        });

        /**
         * ORDER ITEMS
         */
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_variant_id');

            // Snapshot
            $table->string('product_name');
            $table->string('variant_description')->nullable();

            $table->decimal('unit_price', 18, 6);
            $table->integer('quantity');
            $table->integer('fulfilled_quantity')->default(0);

            $table->decimal('line_total', 18, 6);
            $table->decimal('tax_amount', 18, 6)->default(0);

            $table->timestamps();

            $table->unique(['order_id', 'product_variant_id'], 'order_item_unique');
        });

        /**
         * FOREIGN KEYS (PREFIX SAFE)
         */
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('company_id', 'order_company_fk')
                ->references('id')->on('companies')->cascadeOnDelete();

            $table->foreign('customer_id', 'order_customer_fk')
                ->references('id')->on('customers')->nullOnDelete();

            $table->foreign('status_id', 'order_status_fk')
                ->references('id')->on('order_statuses')->restrictOnDelete();

            $table->foreign('currency_id', 'order_currency_fk')
                ->references('id')->on('currencies')->restrictOnDelete();

            $table->foreign('payment_term_id', 'order_payment_term_fk')
                ->references('id')->on('payment_terms')->nullOnDelete();

            $table->foreign('created_by', 'order_user_fk')
                ->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('order_id', 'oi_order_fk')
                ->references('id')->on('orders')->cascadeOnDelete();

            $table->foreign('product_variant_id', 'oi_variant_fk')
                ->references('id')->on('product_variants')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('order_statuses');
    }
};
