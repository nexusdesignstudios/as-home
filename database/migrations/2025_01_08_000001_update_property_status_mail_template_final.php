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
        // Update property status mail template with the exact format requested
        $newPropertyStatusMailTemplate = "<p>Dear {user_name},</p> <p>Thank you for listing at {app_name} for Asset Management,</p> <p>{app_name} is delighted to inform you that the status of your property, {property_name}, has been {status}.</p> <p>Feel free to contract {app_name} support team if you have any enquiries.</p> <p>Thank you & best regards,</p> <p>{app_name} for Asset Management</p>";

        Setting::where('type', 'property_status_mail_template')
            ->update(['data' => $newPropertyStatusMailTemplate]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous property status mail template
        $previousPropertyStatusMailTemplate = "<p>Dear {user_name},</p> <p>Thank you for listing at {app_name} for Asset Management,</p> <p>{app_name} is delighted to inform you that the status of your property, {property_name}, has been {status}.</p> <p>Feel free to contract {app_name} support team if you have any enquiries.</p> <p>Thank you & best regards,</p> <p>{app_name} for Asset Management</p>";

        Setting::where('type', 'property_status_mail_template')
            ->update(['data' => $previousPropertyStatusMailTemplate]);
    }
};
