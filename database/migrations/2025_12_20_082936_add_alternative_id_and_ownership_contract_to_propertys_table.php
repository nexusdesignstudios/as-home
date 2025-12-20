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
        Schema::table('propertys', function (Blueprint $table) {
            if (!Schema::hasColumn('propertys', 'alternative_id')) {
                $table->string('alternative_id')->nullable()->after('national_id_passport');
            }
            if (!Schema::hasColumn('propertys', 'ownership_contract')) {
                $table->string('ownership_contract')->nullable()->after('power_of_attorney');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propertys', function (Blueprint $table) {
            if (Schema::hasColumn('propertys', 'alternative_id')) {
                $table->dropColumn('alternative_id');
            }
            if (Schema::hasColumn('propertys', 'ownership_contract')) {
                $table->dropColumn('ownership_contract');
            }
        });
    }
};
