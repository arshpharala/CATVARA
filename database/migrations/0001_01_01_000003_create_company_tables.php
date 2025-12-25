<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_statuses', function (Blueprint $table) {

            $table->id();
            $table->string('name');              // Active, Suspended, Closed
            $table->string('code')->unique();    // ACTIVE, SUSPENDED, CLOSED
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('companies', function (Blueprint $table) {

            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name');              // Display name
            $table->string('legal_name');        // Registered name
            $table->string('code')->unique();    // Internal short code

            $table->string('logo')->nullable();
            $table->string('website_url')->nullable();

            $table->unsignedBigInteger('company_status_id');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_status_id')
                ->references('id')
                ->on(config('database.connections.mysql.prefix') . 'company_statuses');
        });

        Schema::create('company_details', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger('company_id');

            $table->string('invoice_prefix')->nullable();
            $table->string('invoice_postfix')->nullable();

            $table->string('quote_prefix')->nullable();
            $table->string('quote_postfix')->nullable();

            $table->text('address')->nullable();
            $table->string('tax_number')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('company_id');

            $table->foreign('company_id')
                ->references('id')
                ->on(config('database.connections.mysql.prefix') . 'companies')
                ->cascadeOnDelete();
        });

        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id');

            // POS_ORDER, WEB_ORDER, INVOICE, CREDIT_NOTE, QUOTE
            $table->string('document_type');

            // Optional: POS / WEB / B2B
            $table->string('channel')->nullable();

            // Prefix you define (POS-, INV-, CN-, etc)
            $table->string('prefix');

            // Optional postfix (e.g. /2025)
            $table->string('postfix')->nullable();

            // Last used number
            $table->unsignedBigInteger('current_number')->default(0);

            // Optional year-based reset
            $table->unsignedSmallInteger('year')->nullable();

            $table->timestamps();

            $table->unique(
                ['company_id', 'document_type', 'channel', 'year'],
                'doc_seq_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('company_details');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('company_statuses');
        Schema::dropIfExists('document_sequences');
        Schema::enableForeignKeyConstraints();
    }
};
