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
        // Add property client email template
        $propertyClientTemplate = '<p>Dear {customer_name},</p>

<p>Mr. {client_name} will see you in {corresponding_day}.</p>

<p>His contact details:</p>
<p>Phone number: {client_number}</p>
<p>Email: {client_email}</p>

<p>Best regards,<br>
{app_name} Team</p>';

        Setting::updateOrCreate(
            ['type' => 'property_client_mail_template'],
            ['data' => $propertyClientTemplate]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the property client email template
        Setting::where('type', 'property_client_mail_template')->delete();
    }
};
