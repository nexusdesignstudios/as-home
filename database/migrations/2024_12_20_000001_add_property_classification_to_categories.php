<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPropertyClassificationToCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'property_classification')) {
                $table->tinyInteger('property_classification')->default(1)->comment('1:Sell/Long Term Rent, 2:Commercial, 3:New Project, 4:Vacation Homes, 5:Hotel Booking')->nullable()->after('parameter_types');
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
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'property_classification')) {
                $table->dropColumn('property_classification');
            }
        });
    }
}
