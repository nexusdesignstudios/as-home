<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add setting for monthly tax invoice schedule
        Setting::updateOrCreate(
            ['type' => 'monthly_tax_invoice_schedule'],
            ['data' => '15'] // 15th of each month
        );

        // Add setting for monthly tax invoice time
        Setting::updateOrCreate(
            ['type' => 'monthly_tax_invoice_time'],
            ['data' => '09:00'] // 9:00 AM
        );

        // Add setting for monthly tax invoice enabled
        Setting::updateOrCreate(
            ['type' => 'monthly_tax_invoice_enabled'],
            ['data' => '1'] // Enabled by default
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::whereIn('type', [
            'monthly_tax_invoice_schedule',
            'monthly_tax_invoice_time',
            'monthly_tax_invoice_enabled'
        ])->delete();
    }
};
