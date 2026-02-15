<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Reservation;
use App\Services\HelperService;
use App\Services\ReservationService;

class TestFlexibleHotelBookingEmail extends Command
{
    protected $signature = 'test:flexible-hotel-booking-email 
                            {email : The email address to send the test to}
                            {--reservation-id= : Optional reservation ID to use real data}';

    protected $description = 'Send a test Flexible Hotel Booking confirmation email to a given address';

    public function handle()
    {
        $email = $this->argument('email');
        $reservationId = $this->option('reservation-id');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address.');
            return Command::FAILURE;
        }

        $this->info('Preparing test for Flexible Hotel Booking confirmation email');
        $this->info('Recipient: ' . $email);

        $template = DB::table('settings')->where('type', 'flexible_hotel_booking_confirmation_mail_template')->value('data');
        if ($template) {
            $this->info('Template found in settings.');
        } else {
            $this->warn('Template not found in settings. Using default fallback content.');
        }

        if ($reservationId) {
            $reservation = Reservation::with(['customer', 'reservable', 'property'])->find($reservationId);
            if (!$reservation || !$reservation->customer) {
                $this->error('Reservation not found or missing customer.');
                return Command::FAILURE;
            }
            $reservation->customer->email = $email;
            $service = new ReservationService();
            $service->sendFlexibleHotelBookingConfirmationEmail($reservation);
            $this->info('Sent using real reservation data (ID: ' . $reservation->id . ').');
            return Command::SUCCESS;
        }

        $emailTypeData = HelperService::getEmailTemplatesTypes('flexible_hotel_booking_confirmation');
        $appName = env('APP_NAME') ?? 'As Home';

        $variables = [
            'app_name' => $appName,
            'customer_name' => 'Test User',
            'user_name' => 'Test User',
            'guest_email' => $email,
            'guest_phone' => '+201234567890',
            'reservation_id' => 'TEST-' . rand(1000, 9999),
            'hotel_name' => 'Test Hotel',
            'property_name' => 'Test Hotel',
            'room_type' => 'Deluxe Room',
            'room_number' => '101',
            'hotel_address' => '123 Test Street, Cairo',
            'property_address' => '123 Test Street, Cairo',
            'check_in_date' => now()->format('d M Y'),
            'check_out_date' => now()->addDays(2)->format('d M Y'),
            'number_of_guests' => 2,
            'total_price' => number_format(1500, 2),
            'total_amount' => number_format(1500, 2),
            'currency_symbol' => system_setting('currency_symbol') ?? 'EGP',
            'payment_status' => 'Paid',
            'special_requests' => 'None',
        ];

        if (empty($template)) {
            $template = "Dear {customer_name},\n\nYour reservation for {property_name} has been confirmed!\n\nProperty: {property_name}\nRoom Type: {room_type}\nRoom Number: {room_number}\nAddress: {property_address}\nCheck-in Date: {check_in_date}\nCheck-out Date: {check_out_date}\nNumber of Guests: {number_of_guests}\nTotal Amount: {total_price} {currency_symbol}\n\nThank you for choosing {app_name}!";
        }

        $content = HelperService::replaceEmailVariables($template, $variables);

        $data = [
            'email_template' => $content,
            'email' => $email,
            'title' => $emailTypeData['title'],
        ];

        HelperService::sendMail($data, false, true);
        $this->info('Test email sent using sample data.');
        return Command::SUCCESS;
    }
}
