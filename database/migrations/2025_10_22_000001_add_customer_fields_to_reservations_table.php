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
        // Add only the columns that don't exist yet
        // review_url already exists from previous migration
        
        if (!Schema::hasColumn('reservations', 'customer_name')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->string('customer_name')->nullable()->after('customer_id');
            });
        }
        
        if (!Schema::hasColumn('reservations', 'customer_phone')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->string('customer_phone')->nullable()->after('customer_name');
            });
        }
        
        if (!Schema::hasColumn('reservations', 'customer_email')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->string('customer_email')->nullable()->after('customer_phone');
            });
        }
        
        if (!Schema::hasColumn('reservations', 'approval_status')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('review_url');
            });
        }
        
        if (!Schema::hasColumn('reservations', 'requires_approval')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->boolean('requires_approval')->default(false)->after('approval_status');
            });
        }
        
        if (!Schema::hasColumn('reservations', 'booking_type')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->string('booking_type')->nullable()->after('requires_approval');
            });
        }
        
        if (!Schema::hasColumn('reservations', 'property_details')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->json('property_details')->nullable()->after('booking_type');
            });
        }
        
        if (!Schema::hasColumn('reservations', 'reservable_data')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->json('reservable_data')->nullable()->after('property_details');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop only the columns that this migration added
        // Don't drop review_url as it was added by a previous migration
        
        if (Schema::hasColumn('reservations', 'customer_name')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('customer_name');
            });
        }
        
        if (Schema::hasColumn('reservations', 'customer_phone')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('customer_phone');
            });
        }
        
        if (Schema::hasColumn('reservations', 'customer_email')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('customer_email');
            });
        }
        
        if (Schema::hasColumn('reservations', 'approval_status')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('approval_status');
            });
        }
        
        if (Schema::hasColumn('reservations', 'requires_approval')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('requires_approval');
            });
        }
        
        if (Schema::hasColumn('reservations', 'booking_type')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('booking_type');
            });
        }
        
        if (Schema::hasColumn('reservations', 'property_details')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('property_details');
            });
        }
        
        if (Schema::hasColumn('reservations', 'reservable_data')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('reservable_data');
            });
        }
    }
};
