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
            $table->integer('available_rooms')->nullable()->after('agent_addons')
                ->comment('Number of available rooms for hotel properties');
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
            $table->dropColumn('available_rooms');
        });
    }
};
