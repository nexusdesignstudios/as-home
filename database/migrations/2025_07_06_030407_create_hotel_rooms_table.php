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
        Schema::create('hotel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('propertys')->onDelete('cascade');
            $table->foreignId('room_type_id')->constrained('hotel_room_types')->onDelete('cascade');
            $table->string('room_number');
            $table->decimal('price_per_night', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->enum('refund_policy', ['flexible', 'non-refundable'])->default('flexible');
            $table->text('description')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_rooms');
    }
};
