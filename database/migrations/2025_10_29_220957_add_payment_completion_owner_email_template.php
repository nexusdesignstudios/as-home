<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class AddPaymentCompletionOwnerEmailTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $emailTemplate = '<p>Hello <strong>{property_owner_name}</strong>,</p>
<p>Payment completed for your property <strong>"{property_name}"</strong>!</p>
<p><strong>Customer Information:</strong></p>
<ul>
<li><strong>Customer Name:</strong> {customer_name}</li>
<li><strong>Email:</strong> {customer_email}</li>
<li><strong>Phone:</strong> {customer_phone}</li>
</ul>
<p><strong>Reservation Details:</strong></p>
<ul>
<li><strong>Reservation ID:</strong> {reservation_id}</li>
<li><strong>Property:</strong> {property_name}</li>
<li><strong>Property Address:</strong> {property_address}</li>
<li><strong>Check-in Date:</strong> {check_in_date}</li>
<li><strong>Check-out Date:</strong> {check_out_date}</li>
<li><strong>Number of Guests:</strong> {number_of_guests}</li>
<li><strong>Total Amount:</strong> {total_price} {currency_symbol}</li>
<li><strong>Payment Status:</strong> {payment_status}</li>
<li><strong>Transaction ID:</strong> {transaction_id}</li>
<li><strong>Payment Completion Date:</strong> {payment_completion_date}</li>
</ul>
<p><strong>Special Requests:</strong> {special_requests}</p>
<p>Please prepare for your guest&#39;s arrival!</p>
<p>Best regards,</p>
<p>The <strong>{app_name}</strong> Team</p>';

        // Add the email template to the settings table
        Setting::updateOrCreate(
            ['type' => 'payment_completion_owner_mail_template'],
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
        Setting::where('type', 'payment_completion_owner_mail_template')->delete();
    }
}
