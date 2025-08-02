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
        Schema::table('paymob_payout_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('property_id')->nullable()->after('customer_id');
            $table->decimal('original_amount', 10, 2)->nullable()->after('amount');
            $table->decimal('commission_percentage', 5, 2)->nullable()->after('original_amount');
            $table->string('payout_month')->nullable()->after('notes');
            $table->string('payout_year')->nullable()->after('payout_month');
            $table->boolean('is_processed')->default(false)->after('payout_year');

            // Add foreign key
            $table->foreign('property_id')->references('id')->on('propertys')->onDelete('set null');

            // Add index
            $table->index(['payout_month', 'payout_year']);
            $table->index('is_processed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paymob_payout_transactions', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
            $table->dropColumn([
                'property_id',
                'original_amount',
                'commission_percentage',
                'payout_month',
                'payout_year',
                'is_processed'
            ]);
        });
    }
};
