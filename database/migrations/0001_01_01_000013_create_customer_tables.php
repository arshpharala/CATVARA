<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * CUSTOMERS
         */
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('payment_term_id')->nullable();

            $table->enum('type', ['INDIVIDUAL', 'COMPANY'])->default('INDIVIDUAL');

            // Common
            $table->string('display_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Company-specific
            $table->string('legal_name')->nullable();
            $table->string('tax_number')->nullable();

            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'type'], 'cust_company_type_idx');
            $table->unique(['company_id', 'email'], 'cust_company_email_unique');
            $table->unique(['company_id', 'phone'], 'cust_company_phone_unique');
        });

        /**
         * CUSTOMER ADDRESSES
         */
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id');

            $table->enum('type', ['BILLING', 'SHIPPING'])->default('SHIPPING');
            $table->boolean('is_default')->default(false);

            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();

            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country_code', 2)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['company_id', 'customer_id', 'type'],
                'cust_addr_comp_cust_type_idx'
            );
        });

        /**
         * FOREIGN KEYS (SHORT NAMES)
         */
        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('company_id', 'cust_company_fk')
                ->references('id')->on('companies')
                ->cascadeOnDelete();

            $table->foreign('payment_term_id', 'cust_payment_term_fk')
                ->references('id')->on('payment_terms')
                ->nullOnDelete();
        });

        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->foreign('company_id', 'custaddr_company_fk')
                ->references('id')->on('companies')
                ->cascadeOnDelete();

            $table->foreign('customer_id', 'custaddr_customer_fk')
                ->references('id')->on('customers')
                ->cascadeOnDelete();
        });

        /**
         * POS ORDERS → CUSTOMER (OPTIONAL)
         */
        Schema::table('pos_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_orders', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('user_id');
            }

            $table->foreign('customer_id', 'pos_customer_fk')
                ->references('id')->on('customers')
                ->nullOnDelete();

            $table->index(
                ['company_id', 'customer_id'],
                'pos_company_customer_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->dropForeign('pos_customer_fk');

            try {
                $table->dropIndex('pos_company_customer_idx');
            } catch (\Throwable $e) {
            }

            if (Schema::hasColumn('pos_orders', 'customer_id')) {
                $table->dropColumn('customer_id');
            }
        });

        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropForeign('custaddr_company_fk');
            $table->dropForeign('custaddr_customer_fk');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign('cust_company_fk');
            $table->dropForeign('cust_payment_term_fk');
        });

        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
