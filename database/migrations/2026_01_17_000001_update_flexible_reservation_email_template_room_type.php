<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class UpdateFlexibleReservationEmailTemplateRoomType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $flexibleReservationConfirmationTemplate = '<p>Hello <strong>{user_name}</strong>,</p> 
<p>Thank you for your flexible reservation with <strong>{app_name}</strong>! Your booking has been confirmed.</p> 
<p><strong>Reservation Details:</strong></p> 
<ul> 
<li><strong>Reservation ID:</strong> {reservation_id}</li> 
<li><strong>Property:</strong> {property_name}</li> 
<li><strong>Room Type:</strong> {room_type}</li>
<li><strong>Check-in Date:</strong> {check_in_date}</li> 
<li><strong>Check-out Date:</strong> {check_out_date}</li> 
<li><strong>Number of Guests:</strong> {number_of_guests}</li> 
<li><strong>Total Amount:</strong> {currency_symbol} {total_price}</li> 
<li><strong>Payment Status:</strong> {payment_status}</li> 
<li><strong>Transaction ID:</strong> {transaction_id}</li> 
</ul> 
<p><strong>Special Requests:</strong> {special_requests}</p> 
<p>This is a flexible booking, so you have more options for managing your dates. If you have any questions or need to modify your reservation, please do not hesitate to contact our support team.</p> 
<p>We look forward to welcoming you!</p> 
<p>Best regards,</p> 
<p>The <strong>{app_name}</strong> Team</p>';

        Setting::updateOrCreate(
            ['type' => 'flexible_reservation_confirmation_mail_template'],
            ['data' => $flexibleReservationConfirmationTemplate]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert to previous version without Room Type
        $flexibleReservationConfirmationTemplate = '<p>Hello <strong>{user_name}</strong>,</p> 
<p>Thank you for your flexible reservation with <strong>{app_name}</strong>! Your booking has been confirmed.</p> 
<p><strong>Reservation Details:</strong></p> 
<ul> 
<li><strong>Reservation ID:</strong> {reservation_id}</li> 
<li><strong>Property:</strong> {property_name}</li> 
<li><strong>Check-in Date:</strong> {check_in_date}</li> 
<li><strong>Check-out Date:</strong> {check_out_date}</li> 
<li><strong>Number of Guests:</strong> {number_of_guests}</li> 
<li><strong>Total Amount:</strong> {currency_symbol} {total_price}</li> 
<li><strong>Payment Status:</strong> {payment_status}</li> 
<li><strong>Transaction ID:</strong> {transaction_id}</li> 
</ul> 
<p><strong>Special Requests:</strong> {special_requests}</p> 
<p>This is a flexible booking, so you have more options for managing your dates. If you have any questions or need to modify your reservation, please do not hesitate to contact our support team.</p> 
<p>We look forward to welcoming you!</p> 
<p>Best regards,</p> 
<p>The <strong>{app_name}</strong> Team</p>';

        Setting::updateOrCreate(
            ['type' => 'flexible_reservation_confirmation_mail_template'],
            ['data' => $flexibleReservationConfirmationTemplate]
        );
    }
}
