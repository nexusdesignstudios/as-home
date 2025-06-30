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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('company_legal_name')->nullable()->change();
            $table->string('manager_name')->nullable()->change();
            $table->string('type_of_company')->nullable()->change();
            $table->string('email_address')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('company_legal_name')->nullable(false)->change();
            $table->string('manager_name')->nullable(false)->change();
            $table->string('type_of_company')->nullable(false)->change();
            $table->string('email_address')->nullable(false)->change();
        });
    }
};
