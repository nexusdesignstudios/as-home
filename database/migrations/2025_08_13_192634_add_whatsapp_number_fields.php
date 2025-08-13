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
        // Add whatsappnumber field to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->string('whatsappnumber')->nullable()->after('mobile');
        });

        // Add company_employee_whatsappnumber field to propertys table
        Schema::table('propertys', function (Blueprint $table) {
            $table->string('company_employee_whatsappnumber')->nullable()->after('company_employee_phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove whatsappnumber field from customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('whatsappnumber');
        });

        // Remove company_employee_whatsappnumber field from propertys table
        Schema::table('propertys', function (Blueprint $table) {
            $table->dropColumn('company_employee_whatsappnumber');
        });
    }
};
