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
        Schema::table('hotel_rooms', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['room_type_id']);
            
            // Change column to nullable
            $table->unsignedBigInteger('room_type_id')->nullable()->change();
            
            // Add custom_room_type column
            $table->string('custom_room_type')->nullable()->after('room_type_id');
            
            // Re-add foreign key constraint (works for non-null values)
            $table->foreign('room_type_id')->references('id')->on('hotel_room_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            $table->dropForeign(['room_type_id']);
            $table->dropColumn('custom_room_type');
            
            // Note: Cannot easily revert to NOT NULL if there are NULL values
            // So we just re-add the foreign key
            $table->unsignedBigInteger('room_type_id')->nullable(false)->change();
            $table->foreign('room_type_id')->references('id')->on('hotel_room_types')->onDelete('cascade');
        });
    }
};
