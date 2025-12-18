<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('property_terms', function (Blueprint $table) {
            // Change terms_conditions from TEXT to LONGTEXT to support large contracts
            $table->longText('terms_conditions')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('property_terms', function (Blueprint $table) {
            // Revert back to TEXT if needed
            $table->text('terms_conditions')->nullable()->change();
        });
    }
};
