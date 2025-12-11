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
        Schema::create('vacation_apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('propertys')->onDelete('cascade');
            $table->string('apartment_number');
            $table->decimal('price_per_night', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('status')->default(1);
            $table->tinyInteger('availability_type')->nullable()
                ->comment('1:Available Days, 2:Busy Days');
            $table->json('available_dates')->nullable()
                ->comment('Array of date ranges [{from: "YYYY-MM-DD", to: "YYYY-MM-DD"}]');
            $table->integer('max_guests')->nullable();
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacation_apartments');
    }
};

