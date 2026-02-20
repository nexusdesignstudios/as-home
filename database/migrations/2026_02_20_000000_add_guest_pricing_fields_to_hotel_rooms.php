<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGuestPricingFieldsToHotelRooms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('hotel_rooms', 'base_guests')) {
                $table->integer('base_guests')->default(2)->nullable()->after('min_guests');
            }
            if (!Schema::hasColumn('hotel_rooms', 'guest_pricing_rules')) {
                $table->json('guest_pricing_rules')->nullable()->after('base_guests');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            if (Schema::hasColumn('hotel_rooms', 'base_guests')) {
                $table->dropColumn('base_guests');
            }
            if (Schema::hasColumn('hotel_rooms', 'guest_pricing_rules')) {
                $table->dropColumn('guest_pricing_rules');
            }
        });
    }
}
