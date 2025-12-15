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
        Schema::table('vacation_apartments', function (Blueprint $table) {
            $table->integer('quantity')->default(1)->after('bathrooms')
                ->comment('Number of units of this apartment type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacation_apartments', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};

