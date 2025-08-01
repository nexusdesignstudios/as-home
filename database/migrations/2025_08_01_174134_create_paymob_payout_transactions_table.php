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
        Schema::create('paymob_payout_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('transaction_id')->unique();
            $table->string('issuer'); // vodafone, etisalat, orange, aman, bank_wallet, bank_card
            $table->decimal('amount', 10, 2);
            $table->string('msisdn', 11)->nullable(); // 11 digits phone number
            $table->string('full_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('bank_card_number')->nullable();
            $table->string('bank_transaction_type')->nullable(); // salary, credit_card, prepaid_card, cash_transfer
            $table->string('bank_code')->nullable();
            $table->string('client_reference_id')->nullable();
            $table->string('disbursement_status'); // success, successful, failed, pending
            $table->string('status_code')->nullable();
            $table->text('status_description')->nullable();
            $table->string('reference_number')->nullable(); // AMAN only
            $table->boolean('paid')->nullable(); // AMAN only
            $table->json('aman_cashing_details')->nullable(); // AMAN specific details
            $table->json('transaction_data')->nullable(); // Full response data
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('customer_id');
            $table->index('transaction_id');
            $table->index('issuer');
            $table->index('disbursement_status');
            $table->index('client_reference_id');
            $table->index('created_at');

            // Foreign key
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paymob_payout_transactions');
    }
};
