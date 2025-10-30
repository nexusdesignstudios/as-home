<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class AddFeedbackRequestEmailTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $emailTemplate = '<p>Dear <strong>{customer_name}</strong>,</p>
<p>Thank you for choosing <strong>{app_name}</strong> for your recent stay!</p>
<p>We hope you had a wonderful experience at <strong>{property_name}</strong>. Your feedback is extremely valuable to us and helps us improve our services.</p>
<p>Please take a moment to share your experience by clicking on the link below to complete our feedback form:</p>
<p><a href="{feedback_link}">{feedback_link}</a></p>
<p>Your feedback helps us:</p>
<ul>
<li>Improve our property amenities and services</li>
<li>Enhance the guest experience for future visitors</li>
<li>Maintain the highest quality standards</li>
</ul>
<p>We appreciate your time and look forward to hearing from you!</p>
<p>Best regards,<br>
The <strong>{app_name}</strong> Team</p>
<p><em>Note: This feedback link is valid and unique to your reservation.</em></p>';

        // Add the email template to the settings table
        Setting::updateOrCreate(
            ['type' => 'feedback_request_mail_template'],
            ['data' => $emailTemplate]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove the email template from the settings table
        Setting::where('type', 'feedback_request_mail_template')->delete();
    }
}
