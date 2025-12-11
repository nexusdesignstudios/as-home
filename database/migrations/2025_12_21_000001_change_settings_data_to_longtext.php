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
        // Change data column from text to longText to support longer contracts
        DB::statement('ALTER TABLE settings MODIFY COLUMN data LONGTEXT');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to text (with size limit)
        DB::statement('ALTER TABLE settings MODIFY COLUMN data TEXT');
    }
};

