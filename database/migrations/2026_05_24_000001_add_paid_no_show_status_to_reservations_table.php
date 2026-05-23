<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('pending', 'approved', 'confirmed', 'cancelled', 'completed', 'paid', 'no_show') DEFAULT 'pending'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE reservations MODIFY COLUMN status ENUM('pending', 'approved', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending'");
    }
};
