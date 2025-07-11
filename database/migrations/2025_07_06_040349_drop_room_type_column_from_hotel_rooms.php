<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            // Check if the column exists first
            if (Schema::hasColumn('hotel_rooms', 'room_type')) {
                // Check if the foreign key constraint exists
                $foreignKeys = $this->listTableForeignKeys('hotel_rooms');

                // Drop the foreign key constraint if it exists
                if (in_array('hotel_rooms_room_type_foreign', $foreignKeys)) {
                    $table->dropForeign('hotel_rooms_room_type_foreign');
                }

                // Then drop the column
                $table->dropColumn('room_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            // Check if the column doesn't exist before adding it back
            if (!Schema::hasColumn('hotel_rooms', 'room_type')) {
                // Add the column back
                $table->unsignedBigInteger('room_type')->after('room_type_id')->nullable();
                // Add the foreign key constraint back
                $table->foreign('room_type')->references('id')->on('hotel_room_types')->onDelete('cascade');
            }
        });
    }

    /**
     * Get a list of foreign keys for a table
     */
    private function listTableForeignKeys(string $table): array
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();

        $foreignKeys = [];

        try {
            $tableDetails = $conn->listTableDetails($table);

            foreach ($tableDetails->getForeignKeys() as $foreignKey) {
                $foreignKeys[] = $foreignKey->getName();
            }
        } catch (\Exception $e) {
            // Handle any exceptions that might occur
        }

        return $foreignKeys;
    }
};
