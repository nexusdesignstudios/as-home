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
        // Update the flexible hotel booking confirmation email template
        Setting::updateOrCreate(
            ['type' => 'flexible_hotel_booking_approval_mail_template'],
            ['data' => 'Dear {customer_name},



We are delighted to confirm your booking at {property_name}! 🎉



Below are the full details of your reservation:



Guest Details

• Name: {customer_name}

• Email: {guest_email}

• Phone: {guest_phone}



Property Details

• Property: {property_name}

• Room Type: {room_type}

• Address: {property_address}



Booking Details

• Check-in Date: {check_in_date}

• Check-out Date: {check_out_date}

• Number of Guests: {number_of_guests}

• Total Amount: {currency_symbol}{total_amount}



💳 Payment Information

For flexible bookings, payment can be made on the day of check-in at the hotel or prior to arrival to secure your reservation.



Your reservation has been successfully confirmed. We look forward to welcoming you soon and ensuring you have a comfortable and enjoyable stay.



If you have any questions or need to make changes to your booking, please don\'t hesitate to contact us at support@as-home-group.com or through your As-home account dashboard.



Warm regards,

As-home Asset Management Team

🌐 www.as-home-group.com']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous template if needed
        // This would require storing the old template, which we'll skip for now
    }
};
