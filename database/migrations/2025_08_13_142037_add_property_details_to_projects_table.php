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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('bedroom')->nullable()->after('release_date');
            $table->string('bathroom')->nullable()->after('bedroom');
            $table->string('garage')->nullable()->after('bathroom');
            $table->string('year_built')->nullable()->after('garage');
            $table->string('lot_size')->nullable()->after('year_built');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['bedroom', 'bathroom', 'garage', 'year_built', 'lot_size']);
        });
    }
};
