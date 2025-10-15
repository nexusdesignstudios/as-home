<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class AddReservationRejectionEmailTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $emailTemplate = '<p>Dear {customer_name},</p>

<p>We regret to inform you that your reservation request has been declined.</p>

<p><strong>Reservation Details:</strong></p>
<ul>
<li><strong>Reservation ID:</strong> {reservation_id}</li>
<li><strong>Property:</strong> {property_name}</li>
<li><strong>Check-in Date:</strong> {check_in_date}</li>
<li><strong>Check-out Date:</strong> {check_out_date}</li>
<li><strong>Number of Guests:</strong> {number_of_guests}</li>
<li><strong>Total Amount:</strong> {currency_symbol}{total_price}</li>
</ul>

<p><strong>Reason for Rejection:</strong><br>
{rejection_reason}</p>

<p>We understand this may be disappointing, and we apologize for any inconvenience this may cause. Our team has carefully reviewed your reservation request and unfortunately, we are unable to accommodate it at this time.</p>

<p>If you have any questions or would like to discuss alternative options, please do not hesitate to contact our customer support team.</p>

<p>We value your interest in our properties and hope to have the opportunity to serve you in the future.</p>

<p>Best regards,<br>
The {app_name} Team</p>';

        // Add the email template to the settings table
        Setting::updateOrCreate(
            ['type' => 'reservation_rejection_mail_template'],
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
        Setting::where('type', 'reservation_rejection_mail_template')->delete();
    }
}
