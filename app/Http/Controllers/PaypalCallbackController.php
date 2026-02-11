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
            
            $errorMessage = "Payment Capture Failed";
            
            // Extract detailed error info
            if (isset($captureResult['details'])) {
                $details = $captureResult['details'];
                
                // Handle JSON object details
                if (is_array($details)) {
                    if (isset($details['message'])) {
                        $errorMessage .= ": " . $details['message'];
                    }
                    
                    // Check for specific issues
                    if (isset($details['details']) && is_array($details['details'])) {
                        foreach ($details['details'] as $issue) {
                            if (isset($issue['issue'])) {
                                $errorMessage .= " (" . $issue['issue'] . ")";
                            }
                            if (isset($issue['description'])) {
                                $errorMessage .= " - " . $issue['description'];
                            }
                        }
                    }
                    
                    // Check for name (e.g. UNPROCESSABLE_ENTITY)
                    if (isset($details['name'])) {
                         $errorMessage .= " [" . $details['name'] . "]";
                    }
                } 
                // Handle string details
                elseif (is_string($details)) {
                     $errorMessage .= ": " . substr($details, 0, 150);
                }
            } elseif (isset($captureResult['message'])) {
                $errorMessage .= ": " . $captureResult['message'];
            }
            
            return redirect()->to(url('/booking-failure?message=' . urlencode($errorMessage)));
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
             Log::warning("PayPal Capture: Reservation not found by transaction_id: $customId. Attempting fallback...");
             
             // Fallback: Try to find by parsing custom_id (RES_Timestamp_CustomerID_Random)
             $parts = explode('_', $customId);
             if (count($parts) >= 3 && $parts[0] === 'RES') {
                 $timestamp = $parts[1];
                 $customerId = $parts[2];
                 
                 // Search for a recent pending reservation for this customer
                 // We look for reservations created within +/- 1 hour of the timestamp
                 $reservation = Reservation::where('customer_id', $customerId)
                    ->where('status', 'pending')
                    ->where('payment_status', 'unpaid')
                    ->where('created_at', '>=', \Carbon\Carbon::createFromTimestamp($timestamp)->subHour())
                    ->where('created_at', '<=', \Carbon\Carbon::createFromTimestamp($timestamp)->addHour())
                    ->latest()
                    ->first();
                    
                 if ($reservation) {
                     Log::info("PayPal Capture: Found orphan reservation via fallback. ID: " . $reservation->id . ". Linking to transaction: $customId");
                     $reservation->transaction_id = $customId;
                     $reservation->save();
                 }
             }
        }

        if (!$reservation) {
             Log::error("PayPal Capture: Reservation not found for transaction: $customId");
             return redirect()->to(url('/booking-failure?message=Reservation not found. Order ID: ' . $orderId));
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
