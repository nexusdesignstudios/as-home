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
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('management_type', ['himself', 'as home'])->nullable()->after('isActive');
            $table->unsignedBigInteger('bankDetails_id')->nullable()->after('management_type');
            $table->unsignedBigInteger('company_id')->nullable()->after('bankDetails_id');

            // Add foreign key constraints
            $table->foreign('bankDetails_id')->references('id')->on('bank_details')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['bankDetails_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn('management_type');
            $table->dropColumn('bankDetails_id');
            $table->dropColumn('company_id');
        });
    }
};
