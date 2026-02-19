<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $template = '<p>Dear {property_owner_name},<br />A new payment form submission has been received for your property "{property_name}". </p>
<p>Customer Details: <br />- Name: {customer_name} <br />- Email: {customer_email} <br />- Phone: {customer_phone}</p>
<p>Property Details: <br />- Property: {property_name} <br />- Address: {property_address} <br />- Room Type: {room_type} </p>
<p>Booking Details: <br />- Booking ID: #{reservation_id} <br />- Check-in Date: {check_in_date}<br />- Check-out Date: {check_out_date}<br />- Number of Guests: {number_of_guests}<br />- Total Amount: {total_amount} {currency_symbol} </p>
{payment_breakdown}
<p>Payment Details:<br />Card Number: {card_number_masked}<br />Special Requests: {special_requests} <br />Submission Date: {submission_date} <br /><br />Please review this submission and take appropriate action. </p>
<p>Best regards, <br />{app_name} Team</p>';

        DB::table('settings')->updateOrInsert(
            ['type' => 'payment_form_submission_mail_template'],
            ['data' => $template]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert to previous template without payment_breakdown
        $template = '<p>Dear {property_owner_name},<br />A new payment form submission has been received for your property "{property_name}". </p>
<p>Customer Details: <br />- Name: {customer_name} <br />- Email: {customer_email} <br />- Phone: {customer_phone}</p>
<p>Property Details: <br />- Property: {property_name} <br />- Address: {property_address} <br />- Room Type: {room_type} </p>
<p>Booking Details: <br />- Booking ID: #{reservation_id} <br />- Check-in Date: {check_in_date}<br />- Check-out Date: {check_out_date}<br />- Number of Guests: {number_of_guests}<br />- Total Amount: {total_amount} {currency_symbol} </p>
<p>Payment Details:<br />Card Number: {card_number_masked}<br />Special Requests: {special_requests} <br />Submission Date: {submission_date} <br /><br />Please review this submission and take appropriate action. </p>
<p>Best regards, <br />{app_name} Team</p>';

        DB::table('settings')->updateOrInsert(
            ['type' => 'payment_form_submission_mail_template'],
            ['data' => $template]
        );
    }
};
