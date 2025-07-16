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
            $table->string('national_id_passport')->nullable()->after('identity_proof');
            $table->string('utilities_bills')->nullable()->after('national_id_passport');
            $table->string('power_of_attorney')->nullable()->after('utilities_bills');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propertys', function (Blueprint $table) {
            $table->dropColumn('national_id_passport');
            $table->dropColumn('utilities_bills');
            $table->dropColumn('power_of_attorney');
        });
    }
};
