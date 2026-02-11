<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libraries\PaypalServerSdk;
use App\Models\Reservation;
use App\Models\PaymobPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\ReservationService;

class PaypalCallbackController extends Controller
{
    protected $paypalSdk;
    protected $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->paypalSdk = new PaypalServerSdk();
        $this->reservationService = $reservationService;
    }

    public function handleReturn(Request $request)
    {
        $orderId = $request->query('token');
        $payerId = $request->query('PayerID');

        if (!$orderId) {
            return response()->json(['error' => true, 'message' => 'Missing PayPal Token'], 400);
        }

        Log::info("PayPal Return Callback for Order: $orderId");

        // Capture the order
        $captureResult = $this->paypalSdk->captureOrder($orderId);

        if (isset($captureResult['error']) && $captureResult['error']) {
            Log::error("PayPal Capture Failed: " . json_encode($captureResult));
            return redirect()->to(url('/booking-failure?message=Payment Capture Failed'));
        }

        // Success
        $captureData = $captureResult['data'];
        $customId = $captureData['purchase_units'][0]['payments']['captures'][0]['custom_id'] ?? 
                    $captureData['purchase_units'][0]['custom_id'] ?? 
                    null;
        $captureId = $captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;
        
        if (!$customId) {
             Log::error("PayPal Capture: Missing Custom ID in response");
             return redirect()->to(url('/booking-failure?message=Payment processed but reservation not found'));
        }

        Log::info("PayPal Payment Captured for Reservation Transaction: $customId");

        $reservation = Reservation::where('transaction_id', $customId)->first();

        if (!$reservation) {
             Log::error("PayPal Capture: Reservation not found for transaction: $customId");
             return redirect()->to(url('/booking-failure?message=Reservation not found'));
        }

        // Update Reservation Status
        try {
            DB::beginTransaction();
            
            // Update Payment Record
            PaymobPayment::where('transaction_id', $customId)->update([
                'status' => 'successful',
                'payment_method' => 'paypal',
                'paymob_order_id' => $orderId,
                'paymob_transaction_id' => $captureId,
                'transaction_data' => json_encode($captureData)
            ]);
            
            // Use ReservationService to handle confirmation (updates status, payment_status, available_dates, and sends email)
            $this->reservationService->handleReservationConfirmation($reservation, 'paid');
            
            DB::commit();

            return redirect()->to(url('/booking-success?reservation_id=' . $reservation->id));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("PayPal Callback Exception: " . $e->getMessage());
            return redirect()->to(url('/booking-failure?message=System Error'));
        }
    }
}
