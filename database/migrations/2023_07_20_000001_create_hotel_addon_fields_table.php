<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotelAddonFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hotel_addon_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('field_type', ['text', 'number', 'radio', 'checkbox', 'dropdown', 'textarea', 'file']);
            $table->integer('rank')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
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
        Schema::dropIfExists('hotel_addon_fields');
    }
}
