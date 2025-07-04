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
            $table->tinyInteger('availability_type')->nullable()->after('identity_proof')
                ->comment('1:Available Days, 2:Busy Days');
            $table->json('available_dates')->nullable()->after('availability_type')
                ->comment('Array of date ranges [{from: "YYYY-MM-DD", to: "YYYY-MM-DD"}]');
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
            $table->dropColumn(['availability_type', 'available_dates']);
        });
    }
};
