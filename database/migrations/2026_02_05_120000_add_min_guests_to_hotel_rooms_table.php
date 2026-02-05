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
            // Add min_guests column, default to 1, nullable
            if (!Schema::hasColumn('hotel_rooms', 'min_guests')) {
                $table->integer('min_guests')->default(1)->nullable()->after('max_guests');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            if (Schema::hasColumn('hotel_rooms', 'min_guests')) {
                $table->dropColumn('min_guests');
            }
        });
    }
};
