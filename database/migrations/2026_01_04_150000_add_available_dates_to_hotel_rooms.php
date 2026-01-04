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
        if (!Schema::hasColumn('hotel_rooms', 'available_dates')) {
            Schema::table('hotel_rooms', function (Blueprint $table) {
                $table->json('available_dates')->nullable()->after('availability_type')
                    ->comment('Array of date ranges [{from: "YYYY-MM-DD", to: "YYYY-MM-DD"}]');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('hotel_rooms', 'available_dates')) {
            Schema::table('hotel_rooms', function (Blueprint $table) {
                $table->dropColumn('available_dates');
            });
        }
    }
};
