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
        // This migration is a reminder to manually remove the BankDetailController.php file
        // since it's now merged into the CompanyController
        // The file to remove is: app/Http/Controllers/BankDetailController.php
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No action needed for rollback
    }
};
