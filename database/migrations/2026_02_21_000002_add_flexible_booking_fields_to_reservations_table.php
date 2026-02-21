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
            if (!Schema::hasColumn('reservations', 'is_flexible_booking')) {
                $table->boolean('is_flexible_booking')->default(false)->after('status');
            }
            if (!Schema::hasColumn('reservations', 'refund_policy')) {
                $table->string('refund_policy')->nullable()->after('is_flexible_booking');
            }
            if (!Schema::hasColumn('reservations', 'booking_type')) {
                $table->string('booking_type')->nullable()->after('refund_policy');
            }
            if (!Schema::hasColumn('reservations', 'flexible_booking_discount')) {
                $table->decimal('flexible_booking_discount', 10, 2)->nullable()->after('booking_type');
            }
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
            if (Schema::hasColumn('reservations', 'is_flexible_booking')) {
                $table->dropColumn('is_flexible_booking');
            }
            if (Schema::hasColumn('reservations', 'refund_policy')) {
                $table->dropColumn('refund_policy');
            }
            if (Schema::hasColumn('reservations', 'booking_type')) {
                $table->dropColumn('booking_type');
            }
            if (Schema::hasColumn('reservations', 'flexible_booking_discount')) {
                $table->dropColumn('flexible_booking_discount');
            }
        });
    }
};
