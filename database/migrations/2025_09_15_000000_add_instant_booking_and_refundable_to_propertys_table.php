<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInstantBookingAndRefundableToPropertysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('propertys', function (Blueprint $table) {
            $table->boolean('instant_booking')->default(false)->after('available_rooms');
            $table->boolean('refundable')->default(true)->after('instant_booking');
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
            $table->dropColumn('instant_booking');
            $table->dropColumn('refundable');
        });
    }
}
