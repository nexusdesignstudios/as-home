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
        Schema::create('available_dates_hotel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('propertys')->onDelete('cascade');
            $table->foreignId('hotel_room_id')->constrained('hotel_rooms')->onDelete('cascade');
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('type', 20)->default('open')->comment('open, reserved, dead');
            $table->decimal('nonrefundable_percentage', 5, 2)->nullable();
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index(['property_id', 'from_date', 'to_date']);
            $table->index(['hotel_room_id', 'from_date', 'to_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('available_dates_hotel_rooms');
    }
};

