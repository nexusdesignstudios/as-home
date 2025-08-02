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
            $table->float('nonrefundable_percentage')->default(0)->after('discount_percentage');
            $table->integer('max_guests')->nullable()->after('weekend_commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            $table->dropColumn('nonrefundable_percentage');
            $table->dropColumn('max_guests');
        });
    }
};
