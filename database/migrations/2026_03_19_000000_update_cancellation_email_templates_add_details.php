<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        $customerFallbackTemplate = 'Dear {customer_name},

We are writing to confirm that your reservation has been cancelled as requested.

Reservation Details:
- Reservation ID: {reservation_id}
- Property: {property_name}
- Guest Email: {guest_email}
- Room Type: {room_type}
- Room Number: {room_number}
- Package Type: {package_type}
- Check-in Date: {check_in_date}
- Check-out Date: {check_out_date}
- Total Amount: {currency_symbol}{total_price}
{cancellation_reason}{end_cancellation_reason}

If you requested a refund, please note that it will be processed according to our refund policy. Depending on your payment method, it may take 3-5 business days for the refund to appear in your account.

If you did not request this cancellation or have any questions, please contact our customer support team immediately.

Thank you for your understanding.

Best regards,
The {app_name} Team';

        $ownerFallbackTemplate = '<p>Dear {hotel_owner_name},</p>
<p>The reservation cancellation has been completed successfully.</p>
<p><strong>Reservation Details</strong></p>
<p><strong>Reservation ID:</strong> {reservation_id}<br>
<strong>Guest Name:</strong> {customer_name}<br>
<strong>Guest Email:</strong> {guest_email}<br>
<strong>Guest Phone:</strong> {guest_phone}<br>
<strong>Property:</strong> {hotel_name}<br>
<strong>Room Type:</strong> {room_type}<br>
<strong>Room Number:</strong> {room_number}<br>
<strong>Package Type:</strong> {package_type}<br>
<strong>Check-in Date:</strong> {check_in_date}<br>
<strong>Check-out Date:</strong> {check_out_date}</p>
{{#if cancellation_reason}}<p><strong>Cancellation Reason:</strong> {cancellation_reason}</p>{{/if}}
<p>Thank you and best regards,</p>
<p>Warm regards,<br>
<strong>As-home Asset Management Team</strong></p>';

        $customerSetting = Setting::where('type', 'reservation_cancellation_mail_template')->first();
        if (!$customerSetting) {
            Setting::updateOrCreate(['type' => 'reservation_cancellation_mail_template'], ['data' => $customerFallbackTemplate]);
        } else {
            $data = (string) ($customerSetting->data ?? '');
            if (stripos($data, '{room_type}') === false || stripos($data, '{package_type}') === false) {
                $data = rtrim($data) . "\n\nReservation Extra Details:\n- Guest Email: {guest_email}\n- Room Type: {room_type}\n- Room Number: {room_number}\n- Package Type: {package_type}\n{cancellation_reason}{end_cancellation_reason}\n";
                $customerSetting->data = $data;
                $customerSetting->save();
            }
        }

        $ownerSetting = Setting::where('type', 'hotel_owner_cancellation_mail_template')->first();
        if (!$ownerSetting) {
            Setting::updateOrCreate(['type' => 'hotel_owner_cancellation_mail_template'], ['data' => $ownerFallbackTemplate]);
        } else {
            $data = (string) ($ownerSetting->data ?? '');
            if (stripos($data, '{room_type}') === false || stripos($data, '{package_type}') === false || stripos($data, '{guest_email}') === false) {
                $data = rtrim($data) . "\n\n" . '<p><strong>Guest Name:</strong> {customer_name}<br><strong>Guest Email:</strong> {guest_email}<br><strong>Guest Phone:</strong> {guest_phone}<br><strong>Room Type:</strong> {room_type}<br><strong>Room Number:</strong> {room_number}<br><strong>Package Type:</strong> {package_type}</p>{{#if cancellation_reason}}<p><strong>Cancellation Reason:</strong> {cancellation_reason}</p>{{/if}}';
                $ownerSetting->data = $data;
                $ownerSetting->save();
            }
        }
    }

    public function down(): void
    {
    }
};

