<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ReservationController;

class TestPayPalController extends Controller
{
    public function test(Request $request)
    {
        // Login as customer nexlancer.eg@gmail.com or create one if not exists
        $email = 'nexlancer.eg@gmail.com';
        $customer = Customer::where('email', $email)->first();
        if (!$customer) {
            $customer = Customer::create([
                'name' => 'Nexlancer Test',
                'email' => $email,
                'mobile' => '1234567890',
                'isActive' => '1',
                'logintype' => 'email',
                'auth_id' => 'test_auth_id_' . time(),
            ]);
        }
        
        // Manually authenticate the user for this request
        Auth::guard('sanctum')->setUser($customer);

        // Prepare request data
        // We use dates far in the future to avoid availability conflicts
        $checkIn = '2026-02-20';
        $checkOut = '2026-02-25';

        \Log::info("Testing PayPal with dates: $checkIn to $checkOut");

        $request->merge([
            'reservable_type' => 'property',
            'reservable_id' => 517, // Found valid property via tinker
            'property_id' => 517,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'number_of_guests' => 1,
            'payment_method' => 'paypal',
            'payment' => [
                'amount' => 100, // This might be recalculated by the controller
                'email' => 'nexlancer.eg@gmail.com',
                'first_name' => 'Nexlancer',
                'last_name' => 'Test',
                'phone' => '1234567890'
            ]
        ]);

        // Resolve the controller
        $reservationController = app(ReservationController::class);

        // Call the method
        return $reservationController->createReservationWithPayment($request);
    }
}
