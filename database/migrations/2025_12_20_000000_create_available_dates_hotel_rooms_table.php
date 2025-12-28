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
        // Drop table if it exists (from previous failed migration)
        Schema::dropIfExists('available_dates_hotel_rooms');
        
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
            
            // Indexes for better query performance (using custom names to avoid MySQL 64 char limit)
            $table->index(['property_id', 'from_date', 'to_date'], 'idx_avail_dates_prop_dates');
            $table->index(['hotel_room_id', 'from_date', 'to_date'], 'idx_avail_dates_room_dates');
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

