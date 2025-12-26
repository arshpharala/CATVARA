<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * PAYMENT TERMS (MASTER)
         */
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();     // IMMEDIATE, NET_30
            $table->string('name');               // Immediate, Net 30 Days
            $table->integer('due_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /**
         * COMPANY PAYMENT TERMS (ALLOWED TERMS)
         */
        Schema::create('company_payment_terms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('payment_term_id')
                ->constrained('payment_terms')
                ->restrictOnDelete();

            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->unique(
                ['company_id', 'payment_term_id'],
                'company_payment_term_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_payment_terms');
        Schema::dropIfExists('payment_terms');
    }
};
