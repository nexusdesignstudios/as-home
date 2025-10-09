<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArabicFieldsToPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('propertys', function (Blueprint $table) {
            $table->text('title_ar')->nullable()->after('title');
            $table->text('description_ar')->nullable()->after('description');
            $table->text('area_description_ar')->nullable()->after('area_description');
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
            $table->dropColumn('title_ar');
            $table->dropColumn('description_ar');
            $table->dropColumn('area_description_ar');
        });
    }
}
