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
        // Drop the old JSON available_dates column from hotel_rooms table
        // Note: We're keeping availability_type in case it's needed for other purposes
        Schema::table('hotel_rooms', function (Blueprint $table) {
            if (Schema::hasColumn('hotel_rooms', 'available_dates')) {
                $table->dropColumn('available_dates');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the column if rolling back
        Schema::table('hotel_rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('hotel_rooms', 'available_dates')) {
                $table->json('available_dates')->nullable()->after('availability_type')
                    ->comment('Array of date ranges [{from: "YYYY-MM-DD", to: "YYYY-MM-DD"}]');
            }
        });
    }
};

