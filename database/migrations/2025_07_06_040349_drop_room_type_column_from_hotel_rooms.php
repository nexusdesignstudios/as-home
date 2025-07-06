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
            // Drop the foreign key constraint first
            $table->dropForeign('hotel_rooms_room_type_foreign');
            // Then drop the column
            $table->dropColumn('room_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            // Add the column back
            $table->unsignedBigInteger('room_type')->after('room_type_id');
            // Add the foreign key constraint back
            $table->foreign('room_type')->references('id')->on('hotel_room_types')->onDelete('cascade');
        });
    }
};
