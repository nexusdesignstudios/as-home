<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertyHotelAddonValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('property_hotel_addon_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('propertys')->onDelete('cascade');
            $table->foreignId('hotel_addon_field_id')->constrained('hotel_addon_fields')->onDelete('cascade');
            $table->text('value');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('property_hotel_addon_values');
    }
}
