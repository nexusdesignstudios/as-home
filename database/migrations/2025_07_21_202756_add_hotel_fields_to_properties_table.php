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
            // Add check_in and check_out time fields for hotel properties
            $table->string('check_in')->nullable()->after('hotel_apartment_type_id')
                ->comment('Check-in time for hotel properties');
            $table->string('check_out')->nullable()->after('check_in')
                ->comment('Check-out time for hotel properties');

            // Modify corresponding_day to be JSON to handle hours like availability_dates
            $table->json('agent_addons')->nullable()->after('check_out')
                ->comment('Agent addons with text and price');

            // Check if corresponding_day exists and change its type to JSON
            if (Schema::hasColumn('propertys', 'corresponding_day')) {
                // Create a new column with JSON type
                $table->json('corresponding_day_json')->nullable()->after('agent_addons')
                    ->comment('Available hours for corresponding days');
            }
        });

        // If the corresponding_day column exists, we need to migrate the data and then drop the old column
        if (Schema::hasColumn('propertys', 'corresponding_day')) {
            // This will be handled in a separate data migration script
            // as we can't do data migration in the schema builder
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('propertys', function (Blueprint $table) {
            $table->dropColumn('check_in');
            $table->dropColumn('check_out');
            $table->dropColumn('agent_addons');

            if (Schema::hasColumn('propertys', 'corresponding_day_json')) {
                $table->dropColumn('corresponding_day_json');
            }
        });
    }
};
