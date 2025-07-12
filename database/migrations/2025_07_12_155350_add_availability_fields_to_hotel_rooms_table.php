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
        Schema::table('hotel_rooms', function (Blueprint $table) {
            $table->tinyInteger('availability_type')->nullable()->after('status')
                ->comment('1:Available Days, 2:Busy Days');
            $table->json('available_dates')->nullable()->after('availability_type')
                ->comment('Array of date ranges [{from: "YYYY-MM-DD", to: "YYYY-MM-DD"}]');
            // Note: refund_policy is already in the hotel_rooms table
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            $table->dropColumn(['availability_type', 'available_dates']);
        });
    }
};
