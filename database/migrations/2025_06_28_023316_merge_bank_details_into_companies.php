<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add bank detail fields to companies table
        Schema::table('companies', function (Blueprint $table) {
            $table->string('bank_branch')->nullable()->after('email_address');
            $table->text('bank_address')->nullable()->after('bank_branch');
            $table->string('country')->nullable()->after('bank_address');
            $table->string('bank_account_number')->nullable()->after('country');
            $table->string('iban')->nullable()->after('bank_account_number'); // International Bank Account Number
            $table->string('swift_code')->nullable()->after('iban');
        });

        // Migrate data from bank_details to companies
        $bankDetails = DB::table('bank_details')->get();
        foreach ($bankDetails as $bankDetail) {
            // Find customers with this bank detail
            $customers = DB::table('customers')->where('bankDetails_id', $bankDetail->id)->get();

            foreach ($customers as $customer) {
                // If customer already has a company, update it with bank details
                if ($customer->company_id) {
                    DB::table('companies')
                        ->where('id', $customer->company_id)
                        ->update([
                            'bank_branch' => $bankDetail->bank_branch,
                            'bank_address' => $bankDetail->bank_address,
                            'country' => $bankDetail->country,
                            'bank_account_number' => $bankDetail->bank_account_number,
                            'iban' => $bankDetail->iban,
                            'swift_code' => $bankDetail->swift_code,
                        ]);
                }
                // If customer doesn't have a company but has bank details, create a new company
                else {
                    $companyId = DB::table('companies')->insertGetId([
                        'company_legal_name' => 'Company for ' . $customer->name,
                        'manager_name' => $customer->name,
                        'type_of_company' => $bankDetail->type_of_company ?? 'Individual',
                        'email_address' => $customer->email,
                        'bank_branch' => $bankDetail->bank_branch,
                        'bank_address' => $bankDetail->bank_address,
                        'country' => $bankDetail->country,
                        'bank_account_number' => $bankDetail->bank_account_number,
                        'iban' => $bankDetail->iban,
                        'swift_code' => $bankDetail->swift_code,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Update customer with new company_id
                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->update(['company_id' => $companyId]);
                }
            }
        }

        // Remove bankDetails_id column from customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['bankDetails_id']);
            $table->dropColumn('bankDetails_id');
        });

        // Drop bank_details table
        Schema::dropIfExists('bank_details');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Recreate bank_details table
        Schema::create('bank_details', function (Blueprint $table) {
            $table->id();
            $table->string('type_of_company')->nullable();
            $table->string('bank_branch')->nullable();
            $table->text('bank_address')->nullable();
            $table->string('country')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('swift_code')->nullable();
            $table->timestamps();
        });

        // Add bankDetails_id back to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('bankDetails_id')->nullable()->after('management_type');
            $table->foreign('bankDetails_id')->references('id')->on('bank_details')->onDelete('set null');
        });

        // Remove bank detail fields from companies table
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'bank_branch',
                'bank_address',
                'country',
                'bank_account_number',
                'iban',
                'swift_code'
            ]);
        });
    }
};
