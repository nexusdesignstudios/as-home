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
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('customer_name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('nationality')->nullable()->after('last_name');
            $table->string('booking_source')->nullable()->after('nationality');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'nationality', 'booking_source']);
        });
    }
};
