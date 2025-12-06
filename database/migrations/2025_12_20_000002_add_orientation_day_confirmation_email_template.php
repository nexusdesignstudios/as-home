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
        // Add orientation day confirmation email template
        $orientationDayTemplate = '<p>Dear <strong>{client_name}</strong>,</p>

<p>Thank you for selecting an orientation day for <strong>{property_name}</strong>!</p>

<p><strong>Your Orientation Day Details:</strong></p>
<ul>
<li><strong>Property:</strong> {property_name}</li>
<li><strong>Address:</strong> {property_address}</li>
<li><strong>Day:</strong> {orientation_day}</li>
<li><strong>Time:</strong> {orientation_time}</li>
</ul>

<p><strong>Property Owner Contact Information:</strong></p>
<ul>
<li><strong>Name:</strong> {property_owner_name}</li>
<li><strong>Phone:</strong> {property_owner_phone}</li>
<li><strong>Email:</strong> {property_owner_email}</li>
</ul>

<p>We look forward to seeing you on <strong>{orientation_day}</strong> at <strong>{orientation_time}</strong>!</p>

<p>If you have any questions or need to reschedule, please contact the property owner directly.</p>

<p>Best regards,<br>
The <strong>{app_name}</strong> Team</p>';

        Setting::updateOrCreate(
            ['type' => 'orientation_day_confirmation_mail_template'],
            ['data' => $orientationDayTemplate]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the orientation day confirmation email template
        Setting::where('type', 'orientation_day_confirmation_mail_template')->delete();
    }
};


