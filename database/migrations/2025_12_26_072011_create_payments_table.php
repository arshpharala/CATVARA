<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * PAYMENT METHODS
         * Master list per company
         */
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('company_id');

            $table->string('code');        // CASH, CARD, STRIPE, PAYPAL, BANK
            $table->string('name');        // Cash, Card, Stripe
            $table->string('type');        // CASH, CARD, GATEWAY, BANK, WALLET, CREDIT

            $table->boolean('is_active')->default(true);
            $table->boolean('allow_refund')->default(true);
            $table->boolean('requires_reference')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code'], 'pm_company_code_unique');
        });

        /**
         * PAYMENTS (FINANCIAL EVENT)
         * One record = one real-world money movement
         */
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('payment_method_id');

            /**
             * Source reference ONLY (NOT for reporting)
             * POS / WEB / API / MANUAL
             */
            $table->string('payable_type')->nullable();
            $table->unsignedBigInteger('payable_id')->nullable();

            /**
             * Currency snapshot
             */
            $table->unsignedBigInteger('payment_currency_id'); // e.g. USD
            $table->unsignedBigInteger('base_currency_id');    // company currency

            /**
             * Amounts
             */
            $table->decimal('amount', 18, 6);        // in payment currency
            $table->decimal('exchange_rate', 18, 8); // to base currency
            $table->decimal('base_amount', 18, 6);   // converted
            $table->decimal('fx_difference', 18, 6)->default(0);

            /**
             * Direction & status
             */
            $table->enum('direction', ['IN', 'OUT'])->default('IN');
            $table->string('status')->default('SUCCESS'); // PENDING, SUCCESS, FAILED, REFUNDED

            /**
             * Metadata
             */
            $table->string('source')->nullable();        // POS, WEB, BANK, STRIPE
            $table->string('document_no')->nullable();  // receipt / settlement
            $table->string('reference')->nullable();    // bank ref / cheque
            $table->string('gateway_reference')->nullable();
            $table->json('gateway_payload')->nullable();

            /**
             * Idempotency & timing
             */
            $table->string('idempotency_key')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['company_id', 'idempotency_key'],
                'pay_company_idem_unique'
            );

            $table->index(
                ['company_id', 'payable_type', 'payable_id'],
                'pay_company_source_idx'
            );
        });

        /**
         * PAYMENT ALLOCATIONS (ACCOUNTING LOGIC)
         * One payment → many allocations
         */
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('payment_id');

            /**
             * Allocated document
             * Order / Invoice / CreditNote
             */
            $table->string('allocatable_type');
            $table->unsignedBigInteger('allocatable_id');

            /**
             * Currency snapshot
             */
            $table->unsignedBigInteger('payment_currency_id');
            $table->unsignedBigInteger('base_currency_id');

            /**
             * Allocation amounts
             */
            $table->decimal('allocated_amount', 18, 6);
            $table->decimal('exchange_rate', 18, 8);
            $table->decimal('base_allocated_amount', 18, 6);

            /**
             * Allocation metadata
             */
            $table->string('reason')->nullable(); // ADVANCE, SETTLEMENT, REFUND
            $table->timestamp('allocated_at')->useCurrent();

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['company_id', 'allocatable_type', 'allocatable_id'],
                'pa_company_allocatable_idx'
            );
        });

        /**
         * FOREIGN KEYS (SHORT & SAFE)
         */
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->foreign('company_id')
                ->references('id')->on('companies')
                ->cascadeOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('company_id')
                ->references('id')->on('companies');

            $table->foreign('payment_method_id')
                ->references('id')->on('payment_methods');

            $table->foreign('payment_currency_id')
                ->references('id')->on('currencies');

            $table->foreign('base_currency_id')
                ->references('id')->on('currencies');
        });

        Schema::table('payment_allocations', function (Blueprint $table) {
            $table->foreign('company_id')
                ->references('id')->on('companies');

            $table->foreign('payment_id')
                ->references('id')->on('payments');

            $table->foreign('payment_currency_id')
                ->references('id')->on('currencies');

            $table->foreign('base_currency_id')
                ->references('id')->on('currencies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_methods');
    }
};
