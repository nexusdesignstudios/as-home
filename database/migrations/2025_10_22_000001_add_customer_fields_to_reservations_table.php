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
            $table->string('customer_name')->nullable()->after('customer_id');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->string('customer_email')->nullable()->after('customer_phone');
            $table->string('review_url')->nullable()->after('transaction_id');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('review_url');
            $table->boolean('requires_approval')->default(false)->after('approval_status');
            $table->string('booking_type')->nullable()->after('requires_approval');
            $table->json('property_details')->nullable()->after('booking_type');
            $table->json('reservable_data')->nullable()->after('property_details');
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
                'review_url',
                'approval_status',
                'requires_approval',
                'booking_type',
                'property_details',
                'reservable_data'
            ]);
        });
    }
};
