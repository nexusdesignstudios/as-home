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
            $table->text('area_description')->nullable()->after('description');
            $table->string('company_employee_username')->nullable()->after('area_description');
            $table->string('company_employee_email')->nullable()->after('company_employee_username');
            $table->string('company_employee_phone_number')->nullable()->after('company_employee_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propertys', function (Blueprint $table) {
            $table->dropColumn([
                'area_description',
                'company_employee_username',
                'company_employee_email',
                'company_employee_phone_number'
            ]);
        });
    }
};
