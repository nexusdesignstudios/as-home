<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGuestScalingToHotelRooms extends Migration
{
    public function up()
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('hotel_rooms', 'enable_guest_pricing')) {
                $table->boolean('enable_guest_pricing')->default(false)->after('base_guests');
            }
            if (!Schema::hasColumn('hotel_rooms', 'single_guest_rate')) {
                $table->decimal('single_guest_rate', 5, 2)->default(0.75)->after('enable_guest_pricing');
            }
            if (!Schema::hasColumn('hotel_rooms', 'extra_guest_rate')) {
                $table->decimal('extra_guest_rate', 5, 2)->default(0.25)->after('single_guest_rate');
            }
        });
    }

    public function down()
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            foreach (['enable_guest_pricing', 'single_guest_rate', 'extra_guest_rate'] as $col) {
                if (Schema::hasColumn('hotel_rooms', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
