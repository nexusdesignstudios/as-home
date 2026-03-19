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
            $anchor = Schema::hasColumn('projects', 'lot_size')
                ? 'lot_size'
                : (Schema::hasColumn('projects', 'id') ? 'id' : null);

            $addString = function (string $column) use ($table, &$anchor) {
                $col = $table->string($column)->nullable();
                if ($anchor) {
                    $col->after($anchor);
                    $anchor = $column;
                }
            };

            $addText = function (string $column) use ($table, &$anchor) {
                $col = $table->text($column)->nullable();
                if ($anchor) {
                    $col->after($anchor);
                    $anchor = $column;
                }
            };

            // Agreements documents
            if (!Schema::hasColumn('projects', 'ownership_contract')) {
                $addString('ownership_contract');
            }
            if (!Schema::hasColumn('projects', 'national_id_passport')) {
                $addString('national_id_passport');
            }
            if (!Schema::hasColumn('projects', 'alternative_id')) {
                $addString('alternative_id');
            }
            if (!Schema::hasColumn('projects', 'utilities_bills')) {
                $addString('utilities_bills');
            }
            if (!Schema::hasColumn('projects', 'power_of_attorney')) {
                $addString('power_of_attorney');
            }
            
            // Contact details - Ownership type
            if (!Schema::hasColumn('projects', 'ownership_type')) {
                $addString('ownership_type');
            }
            
            // Individual/Admin Info
            if (!Schema::hasColumn('projects', 'admin_full_name')) {
                $addString('admin_full_name');
            }
            if (!Schema::hasColumn('projects', 'admin_email')) {
                $addString('admin_email');
            }
            if (!Schema::hasColumn('projects', 'admin_phone_number')) {
                $addString('admin_phone_number');
            }
            if (!Schema::hasColumn('projects', 'admin_whatsapp_number')) {
                $addString('admin_whatsapp_number');
            }
            if (!Schema::hasColumn('projects', 'admin_address')) {
                $addText('admin_address');
            }
            if (!Schema::hasColumn('projects', 'admin_profile_image')) {
                $addString('admin_profile_image');
            }
            
            // Employee Details
            if (!Schema::hasColumn('projects', 'company_employee_username')) {
                $addString('company_employee_username');
            }
            if (!Schema::hasColumn('projects', 'company_employee_email')) {
                $addString('company_employee_email');
            }
            if (!Schema::hasColumn('projects', 'company_employee_phone_number')) {
                $addString('company_employee_phone_number');
            }
            if (!Schema::hasColumn('projects', 'company_employee_whatsappnumber')) {
                $addString('company_employee_whatsappnumber');
            }
            
            // Company Details
            if (!Schema::hasColumn('projects', 'company_legal_name')) {
                $addString('company_legal_name');
            }
            if (!Schema::hasColumn('projects', 'manager_name')) {
                $addString('manager_name');
            }
            if (!Schema::hasColumn('projects', 'type_of_company')) {
                $addString('type_of_company');
            }
            if (!Schema::hasColumn('projects', 'company_email_address')) {
                $addString('company_email_address');
            }
            if (!Schema::hasColumn('projects', 'bank_branch')) {
                $addString('bank_branch');
            }
            if (!Schema::hasColumn('projects', 'bank_address')) {
                $addText('bank_address');
            }
            if (!Schema::hasColumn('projects', 'company_country')) {
                $addString('company_country');
            }
            if (!Schema::hasColumn('projects', 'bank_account_number')) {
                $addString('bank_account_number');
            }
            if (!Schema::hasColumn('projects', 'iban')) {
                $addString('iban');
            }
            if (!Schema::hasColumn('projects', 'swift_code')) {
                $addString('swift_code');
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

