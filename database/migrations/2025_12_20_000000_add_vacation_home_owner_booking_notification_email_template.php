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
        // Add the vacation home owner booking notification email template
        Setting::updateOrCreate(
            ['type' => 'vacation_home_owner_booking_notification_mail_template'],
            ['data' => 'Dear {property_owner_name},



A new booking request has been received for your vacation home property.



Booking Details:

• Property: {property_name}

• Address: {property_address}

• Reservation ID: {reservation_id}



Guest Information:

• Name: {customer_name}

• Email: {customer_email}

• Phone: {customer_phone}



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

🌐 www.as-home-group.com']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::where('type', 'vacation_home_owner_booking_notification_mail_template')->delete();
    }
};

