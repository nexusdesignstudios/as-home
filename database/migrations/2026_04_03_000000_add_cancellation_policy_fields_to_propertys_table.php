<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCancellationPolicyFieldsToPropertysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('propertys', function (Blueprint $table) {
            // Add cancellation_policy field (none, 3_days, 5_days, 7_days, same_day_6pm, custom)
            $table->string('cancellation_policy', 20)->nullable()->default('none')->after('cancellation_period');
            // Add custom days field for custom cancellation policy
            $table->integer('cancellation_custom_days')->nullable()->after('cancellation_policy');
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
            $table->dropColumn('cancellation_policy');
            $table->dropColumn('cancellation_custom_days');
        });
    }
}
