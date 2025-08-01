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
        Schema::table('propertys', function (Blueprint $table) {
            $table->string('hotel_name')->nullable()->after('identity_proof');
            $table->enum('refund_policy', ['flexible', 'non-refundable'])->nullable()->after('hotel_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propertys', function (Blueprint $table) {
            $table->dropColumn('hotel_name');
            $table->dropColumn('refund_policy');
        });
    }
};
