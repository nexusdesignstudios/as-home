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
        Schema::table('projects', function (Blueprint $table) {
            // Agreements documents
            if (!Schema::hasColumn('projects', 'ownership_contract')) {
                $table->string('ownership_contract')->nullable()->after('lot_size');
            }
            if (!Schema::hasColumn('projects', 'national_id_passport')) {
                $table->string('national_id_passport')->nullable()->after('ownership_contract');
            }
            if (!Schema::hasColumn('projects', 'alternative_id')) {
                $table->string('alternative_id')->nullable()->after('national_id_passport');
            }
            if (!Schema::hasColumn('projects', 'utilities_bills')) {
                $table->string('utilities_bills')->nullable()->after('alternative_id');
            }
            if (!Schema::hasColumn('projects', 'power_of_attorney')) {
                $table->string('power_of_attorney')->nullable()->after('utilities_bills');
            }
            
            // Contact details - Ownership type
            if (!Schema::hasColumn('projects', 'ownership_type')) {
                $table->string('ownership_type')->nullable()->after('power_of_attorney');
            }
            
            // Individual/Admin Info
            if (!Schema::hasColumn('projects', 'admin_full_name')) {
                $table->string('admin_full_name')->nullable()->after('ownership_type');
            }
            if (!Schema::hasColumn('projects', 'admin_email')) {
                $table->string('admin_email')->nullable()->after('admin_full_name');
            }
            if (!Schema::hasColumn('projects', 'admin_phone_number')) {
                $table->string('admin_phone_number')->nullable()->after('admin_email');
            }
            if (!Schema::hasColumn('projects', 'admin_whatsapp_number')) {
                $table->string('admin_whatsapp_number')->nullable()->after('admin_phone_number');
            }
            if (!Schema::hasColumn('projects', 'admin_address')) {
                $table->text('admin_address')->nullable()->after('admin_whatsapp_number');
            }
            if (!Schema::hasColumn('projects', 'admin_profile_image')) {
                $table->string('admin_profile_image')->nullable()->after('admin_address');
            }
            
            // Employee Details
            if (!Schema::hasColumn('projects', 'company_employee_username')) {
                $table->string('company_employee_username')->nullable()->after('admin_profile_image');
            }
            if (!Schema::hasColumn('projects', 'company_employee_email')) {
                $table->string('company_employee_email')->nullable()->after('company_employee_username');
            }
            if (!Schema::hasColumn('projects', 'company_employee_phone_number')) {
                $table->string('company_employee_phone_number')->nullable()->after('company_employee_email');
            }
            if (!Schema::hasColumn('projects', 'company_employee_whatsappnumber')) {
                $table->string('company_employee_whatsappnumber')->nullable()->after('company_employee_phone_number');
            }
            
            // Company Details
            if (!Schema::hasColumn('projects', 'company_legal_name')) {
                $table->string('company_legal_name')->nullable()->after('company_employee_whatsappnumber');
            }
            if (!Schema::hasColumn('projects', 'manager_name')) {
                $table->string('manager_name')->nullable()->after('company_legal_name');
            }
            if (!Schema::hasColumn('projects', 'type_of_company')) {
                $table->string('type_of_company')->nullable()->after('manager_name');
            }
            if (!Schema::hasColumn('projects', 'company_email_address')) {
                $table->string('company_email_address')->nullable()->after('type_of_company');
            }
            if (!Schema::hasColumn('projects', 'bank_branch')) {
                $table->string('bank_branch')->nullable()->after('company_email_address');
            }
            if (!Schema::hasColumn('projects', 'bank_address')) {
                $table->text('bank_address')->nullable()->after('bank_branch');
            }
            if (!Schema::hasColumn('projects', 'company_country')) {
                $table->string('company_country')->nullable()->after('bank_address');
            }
            if (!Schema::hasColumn('projects', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('company_country');
            }
            if (!Schema::hasColumn('projects', 'iban')) {
                $table->string('iban')->nullable()->after('bank_account_number');
            }
            if (!Schema::hasColumn('projects', 'swift_code')) {
                $table->string('swift_code')->nullable()->after('iban');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'ownership_contract',
                'national_id_passport',
                'alternative_id',
                'utilities_bills',
                'power_of_attorney',
                'ownership_type',
                'admin_full_name',
                'admin_email',
                'admin_phone_number',
                'admin_whatsapp_number',
                'admin_address',
                'admin_profile_image',
                'company_employee_username',
                'company_employee_email',
                'company_employee_phone_number',
                'company_employee_whatsappnumber',
                'company_legal_name',
                'manager_name',
                'type_of_company',
                'company_email_address',
                'bank_branch',
                'bank_address',
                'company_country',
                'bank_account_number',
                'iban',
                'swift_code'
            ]);
        });
    }
};

