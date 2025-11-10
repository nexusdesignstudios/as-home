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
        // Add the vacation home pending approval email template
        Setting::updateOrCreate(
            ['type' => 'vacation_home_pending_approval_mail_template'],
            ['data' => 'Dear {customer_name},



We have received your reservation request for {property_name} and it is now pending approval from the property owner.



Below are the details of your reservation request:



Guest Details

• Name: {customer_name}

• Email: {guest_email}

• Phone: {guest_phone}



Property Details

• Property: {property_name}

• Address: {property_address}



Booking Details

• Check-in Date: {check_in_date}

• Check-out Date: {check_out_date}

• Number of Guests: {number_of_guests}

• Total Amount: {currency_symbol}{total_amount}

• Special Requests: {special_requests}



⏳ Status: Pending Approval

Your reservation request has been submitted and is currently being reviewed by the property owner. You will receive a confirmation email once your booking is approved.



If you have any questions or need to make changes to your booking request, please don\'t hesitate to contact us at support@as-home-group.com or through your As-home account dashboard.



Thank you for choosing As-home!



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
        Setting::where('type', 'vacation_home_pending_approval_mail_template')->delete();
    }
};
