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
            $table->string('policy_data')->nullable()->after('property_classification');
            $table->decimal('weekend_commission', 5, 2)->nullable()->after('policy_data');
            $table->string('identity_proof')->nullable()->after('weekend_commission');
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
            $table->dropColumn(['policy_data', 'weekend_commission', 'identity_proof']);
        });
    }
};
