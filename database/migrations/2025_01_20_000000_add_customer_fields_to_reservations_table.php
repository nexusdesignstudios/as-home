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
        Schema::table('reservations', function (Blueprint $table) {
            // Add customer information columns for direct access
            $table->string('customer_name')->nullable()->after('customer_id');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->string('customer_email')->nullable()->after('customer_phone');
            
            // Add booking date (alias for created_at for frontend compatibility)
            $table->timestamp('booking_date')->nullable()->after('customer_email');
            
            // Add user information columns (for backward compatibility)
            $table->string('user_name')->nullable()->after('booking_date');
            $table->string('user_email')->nullable()->after('user_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'customer_name',
                'customer_phone', 
                'customer_email',
                'booking_date',
                'user_name',
                'user_email'
            ]);
        });
    }
};
