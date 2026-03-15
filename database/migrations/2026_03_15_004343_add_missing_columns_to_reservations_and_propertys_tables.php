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
        // Add columns to 'propertys' table
        Schema::table('propertys', function (Blueprint $table) {
            if (!Schema::hasColumn('propertys', 'instant_booking')) {
                $table->boolean('instant_booking')->default(0)->after('status');
            }
        });

        // Add columns to 'reservations' table
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'booking_type')) {
                $table->string('booking_type')->nullable();
            }
            if (!Schema::hasColumn('reservations', 'approval_status')) {
                $table->string('approval_status')->default('pending');
            }
            if (!Schema::hasColumn('reservations', 'requires_approval')) {
                $table->boolean('requires_approval')->default(false);
            }
            if (!Schema::hasColumn('reservations', 'refund_policy')) {
                $table->string('refund_policy')->default('non-refundable');
            }
            if (!Schema::hasColumn('reservations', 'original_amount')) {
                $table->decimal('original_amount', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('reservations', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('reservations', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('reservations', 'customer_name')) {
                $table->string('customer_name')->nullable();
            }
            if (!Schema::hasColumn('reservations', 'customer_phone')) {
                $table->string('customer_phone')->nullable();
            }
            if (!Schema::hasColumn('reservations', 'customer_email')) {
                $table->string('customer_email')->nullable();
            }
             if (!Schema::hasColumn('reservations', 'property_id')) {
                $table->unsignedBigInteger('property_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propertys', function (Blueprint $table) {
            if (Schema::hasColumn('propertys', 'instant_booking')) {
                $table->dropColumn('instant_booking');
            }
        });

        Schema::table('reservations', function (Blueprint $table) {
            $columns = [
                'booking_type',
                'approval_status',
                'requires_approval',
                'refund_policy',
                'original_amount',
                'discount_amount',
                'discount_percentage',
                'customer_name',
                'customer_phone',
                'customer_email',
                // 'property_id' // Do not drop property_id as it might have FK constraints
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('reservations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
