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
        Schema::table('propertys', function (Blueprint $table) {
            if (!Schema::hasColumn('propertys', 'hotel_vat')) {
                $table->string('hotel_vat')->nullable()->after('non_refundable')
                    ->comment('Hotel VAT number for hotel properties');
            }
            if (!Schema::hasColumn('propertys', 'hotel_available_rooms')) {
                $table->integer('hotel_available_rooms')->nullable()->after('hotel_vat')
                    ->comment('Number of available rooms for hotel properties');
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
        Schema::table('propertys', function (Blueprint $table) {
            if (Schema::hasColumn('propertys', 'hotel_vat')) {
                $table->dropColumn('hotel_vat');
            }
            if (Schema::hasColumn('propertys', 'hotel_available_rooms')) {
                $table->dropColumn('hotel_available_rooms');
            }
        });
    }
};

