<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * QUOTE STATUSES
         */
        Schema::create('quote_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // DRAFT, SENT, ACCEPTED, EXPIRED, CANCELLED
            $table->string('name');
            $table->boolean('is_final')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /**
         * QUOTES (HEADER)
         */
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('status_id');

            $table->string('quote_number')->unique();

            $table->unsignedBigInteger('currency_id');

            // Payment term snapshot
            $table->unsignedBigInteger('payment_term_id')->nullable();
            $table->string('payment_term_name')->nullable();
            $table->integer('payment_due_days')->nullable();

            $table->decimal('subtotal', 18, 6)->default(0);
            $table->decimal('tax_total', 18, 6)->default(0);
            $table->decimal('discount_total', 18, 6)->default(0);
            $table->decimal('grand_total', 18, 6)->default(0);

            $table->timestamp('valid_until')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status_id'], 'quote_company_status_idx');
        });

        /**
         * QUOTE ITEMS
         */
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('quote_id');
            $table->unsignedBigInteger('product_variant_id');

            // Snapshot
            $table->string('product_name');
            $table->string('variant_description')->nullable();

            $table->decimal('unit_price', 18, 6);
            $table->integer('quantity');
            $table->decimal('line_total', 18, 6);
            $table->decimal('tax_amount', 18, 6)->default(0);

            $table->timestamps();
        });

        /**
         * FOREIGN KEYS (PREFIX SAFE)
         */
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreign('company_id', 'quote_company_fk')
                ->references('id')->on('companies')->cascadeOnDelete();

            $table->foreign('customer_id', 'quote_customer_fk')
                ->references('id')->on('customers')->nullOnDelete();

            $table->foreign('status_id', 'quote_status_fk')
                ->references('id')->on('quote_statuses')->restrictOnDelete();

            $table->foreign('currency_id', 'quote_currency_fk')
                ->references('id')->on('currencies')->restrictOnDelete();

            $table->foreign('payment_term_id', 'quote_payment_term_fk')
                ->references('id')->on('payment_terms')->nullOnDelete();

            $table->foreign('created_by', 'quote_user_fk')
                ->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->foreign('quote_id', 'qi_quote_fk')
                ->references('id')->on('quotes')->cascadeOnDelete();

            $table->foreign('product_variant_id', 'qi_variant_fk')
                ->references('id')->on('product_variants')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('quote_statuses');
    }
};
