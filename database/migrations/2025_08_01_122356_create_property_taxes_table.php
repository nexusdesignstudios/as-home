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
        Schema::create('property_taxes', function (Blueprint $table) {
            $table->id();
            $table->integer('property_classification')->comment('4=vacation_homes, 5=hotel_booking');
            $table->decimal('service_charge', 10, 2)->nullable();
            $table->decimal('sales_tax', 10, 2)->nullable();
            $table->decimal('city_tax', 10, 2)->nullable();
            $table->timestamps();

            // Add unique constraint to ensure only one tax record per property classification
            $table->unique('property_classification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_taxes');
    }
};
