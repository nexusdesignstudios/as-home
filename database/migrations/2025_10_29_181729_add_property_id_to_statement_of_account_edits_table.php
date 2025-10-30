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
        // Add property_id column only if it doesn't exist
        if (!Schema::hasColumn('statement_of_account_edits', 'property_id')) {
            Schema::table('statement_of_account_edits', function (Blueprint $table) {
                $table->unsignedBigInteger('property_id')->nullable()->after('reservation_id');
            });
        }
        
        // Add foreign key constraint separately (after column is added)
        if (Schema::hasTable('statement_of_account_edits') && Schema::hasColumn('statement_of_account_edits', 'property_id')) {
            // Check if foreign key already exists
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'statement_of_account_edits' 
                AND COLUMN_NAME = 'property_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            if (count($foreignKeys) == 0) {
                // Add foreign key constraint with proper table name
                if (Schema::hasTable('propertys')) {
                    DB::statement('ALTER TABLE statement_of_account_edits ADD CONSTRAINT statement_of_account_edits_property_id_foreign FOREIGN KEY (property_id) REFERENCES propertys(id) ON DELETE CASCADE');
                } elseif (Schema::hasTable('properties')) {
                    DB::statement('ALTER TABLE statement_of_account_edits ADD CONSTRAINT statement_of_account_edits_property_id_foreign FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE');
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statement_of_account_edits', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
            $table->dropUnique(['property_id']);
            $table->dropColumn('property_id');
            // Restore unique constraint on reservation_id
            $table->unique('reservation_id');
        });
    }
};
