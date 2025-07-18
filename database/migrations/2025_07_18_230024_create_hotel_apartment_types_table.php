<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hotel_apartment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('propertys', function (Blueprint $table) {
            $table->unsignedBigInteger('hotel_apartment_type_id')->nullable()->after('property_classification');
            $table->foreign('hotel_apartment_type_id')->references('id')->on('hotel_apartment_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propertys', function (Blueprint $table) {
            $table->dropForeign(['hotel_apartment_type_id']);
            $table->dropColumn('hotel_apartment_type_id');
        });

        Schema::dropIfExists('hotel_apartment_types');
    }
};
