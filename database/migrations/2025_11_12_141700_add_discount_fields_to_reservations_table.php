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
            if (!Schema::hasColumn('reservations', 'original_amount')) {
                $table->decimal('original_amount', 10, 2)->nullable()->after('total_price');
            }
            if (!Schema::hasColumn('reservations', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->nullable()->after('original_amount');
            }
            if (!Schema::hasColumn('reservations', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->nullable()->after('discount_percentage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'original_amount')) {
                $table->dropColumn('original_amount');
            }
            if (Schema::hasColumn('reservations', 'discount_percentage')) {
                $table->dropColumn('discount_percentage');
            }
            if (Schema::hasColumn('reservations', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
        });
    }
};

