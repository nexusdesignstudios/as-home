<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('hotel_rooms')) {
            Schema::table('hotel_rooms', function (Blueprint $table) {
                if (!Schema::hasColumn('hotel_rooms', 'instant_booking')) {
                    $table->boolean('instant_booking')->default(true)->after('available_rooms');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('hotel_rooms')) {
            Schema::table('hotel_rooms', function (Blueprint $table) {
                if (Schema::hasColumn('hotel_rooms', 'instant_booking')) {
                    $table->dropColumn('instant_booking');
                }
            });
        }
    }
};
