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
        // 1. Update payment_form_submission_mail_template
        $paymentTemplate = '<p>Dear {property_owner_name},<br />A new payment form submission has been received for your property "{property_name}". </p>
<p>Customer Details: <br />- Name: {customer_name} <br />- Email: {customer_email} <br />- Phone: {customer_phone}<br />- Nationality: {nationality}<br />- Booking Source: {booking_source}</p>
<p>Property Details: <br />- Property: {property_name} <br />- Address: {property_address} <br />- Room Type: {room_type} </p>
<p>Booking Details: <br />- Booking ID: #{reservation_id} <br />- Check-in Date: {check_in_date}<br />- Check-out Date: {check_out_date}<br />- Number of Guests: {number_of_guests}<br />- Total Amount: {total_amount} {currency_symbol} </p>
{payment_breakdown}
<p>Payment Details:<br />Card Number: {card_number_masked}<br />Special Requests: {special_requests} <br />Submission Date: {submission_date} <br /><br />Please review this submission and take appropriate action. </p>
<p>Best regards, <br />{app_name} Team</p>';

        DB::table('settings')->updateOrInsert(
            ['type' => 'payment_form_submission_mail_template'],
            ['data' => $paymentTemplate]
        );

        // 2. Update vacation_home_owner_booking_notification_mail_template
        $vacationTemplate = 'Dear {property_owner_name},

A new booking request has been received for your vacation home property.

Booking Details:
• Property: {property_name}
• Address: {property_address}
• Reservation ID: {reservation_id}

Guest Information:
• Name: {customer_name}
• Email: {customer_email}
• Phone: {customer_phone}
• Nationality: {nationality}
• Booking Source: {booking_source}

Booking Period:
• Check-in Date: {check_in_date}
• Check-out Date: {check_out_date}
• Number of Guests: {number_of_guests}

Financial Details:
• Total Amount: {currency_symbol}{total_amount}
• Payment Status: {payment_status}

Special Requests: {special_requests}

Booking Date: {booking_date}

⏳ Action Required: Please review and approve or reject this booking request in your dashboard.

If you have any questions or need assistance, please don\'t hesitate to contact us at support@as-home-group.com or through your As-home account dashboard.

Thank you for being part of As-home!

Best regards,
As-home Asset Management Team
🌐 www.as-home-group.com';

        DB::table('settings')->updateOrInsert(
            ['type' => 'vacation_home_owner_booking_notification_mail_template'],
            ['data' => $vacationTemplate]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert to versions without nationality/booking_source if needed
        // (Implementation omitted for brevity as this is a specific fix)
    }
};
