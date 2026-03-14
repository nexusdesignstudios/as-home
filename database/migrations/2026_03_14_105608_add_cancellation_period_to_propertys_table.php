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
            $table->string('cancellation_period')->nullable()->after('refund_policy')->comment('7_days, same_day_6pm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('propertys', function (Blueprint $table) {
            $table->dropColumn('cancellation_period');
        });
    }
};
