<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $templates = [
            'payment_form_submission_mail_template',
            'vacation_home_owner_booking_notification_mail_template'
        ];

        foreach ($templates as $templateName) {
            $currentValue = DB::table('settings')->where('type', $templateName)->value('data');
            
            if ($currentValue) {
                // Update the label in the existing template
                $newValue = str_replace('Booking Source:', 'Booking Source (Origin Country):', $currentValue);
                
                DB::table('settings')->where('type', $templateName)->update([
                    'data' => $newValue,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $templates = [
            'payment_form_submission_mail_template',
            'vacation_home_owner_booking_notification_mail_template'
        ];

        foreach ($templates as $templateName) {
            $currentValue = DB::table('settings')->where('type', $templateName)->value('data');
            
            if ($currentValue) {
                // Revert the label
                $newValue = str_replace('Booking Source (Origin Country):', 'Booking Source:', $currentValue);
                
                DB::table('settings')->where('type', $templateName)->update([
                    'data' => $newValue,
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
