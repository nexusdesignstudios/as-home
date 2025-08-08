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
            $table->string('revenue_user_name')->nullable();
            $table->string('revenue_phone_number')->nullable();
            $table->string('revenue_email')->nullable();
            $table->string('reservation_user_name')->nullable();
            $table->string('reservation_phone_number')->nullable();
            $table->string('reservation_email')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propertys', function (Blueprint $table) {
            $table->dropColumn([
                'revenue_user_name',
                'revenue_phone_number',
                'revenue_email',
                'reservation_user_name',
                'reservation_phone_number',
                'reservation_email'
            ]);
        });
    }
};
