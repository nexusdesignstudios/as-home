<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\ReservationChangeRequest;
use App\Services\ApiResponseService;
use App\Services\ReservationService;
use App\Services\Payment\PaymentService;
use App\Services\HelperService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ReservationChangeController extends Controller
{
    protected $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    /**
     * Guest/Host requests a date change for a reservation.
     */
    public function requestChange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reservation_id' => 'required|exists:reservations,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors()->first());
        }

        $reservation = Reservation::findOrFail($request->reservation_id);
        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return ApiResponseService::errorResponse('Unauthorized', null, 401);
        }

        // 1. Deadline Check: 2 PM of original checkout date
        $deadline = Carbon::parse($reservation->check_out_date)->setHour(14)->setMinute(0)->setSecond(0);
        if (now()->gt($deadline)) {
            return ApiResponseService::errorResponse('The deadline (2 PM of checkout date) for modifying this reservation has passed.');
        }

        // 2. Check Availability
        $isAvailable = $this->reservationService->areDatesAvailable(
            $reservation->reservable_type,
            $reservation->reservable_id,
            $request->check_in_date,
            $request->check_out_date,
            $reservation->id // Exclude current reservation
        );

        if (!$isAvailable) {
            return ApiResponseService::errorResponse('The requested dates are not available.');
        }

        // 3. Price Calculation
        $requestedTotalPrice = $this->reservationService->calculateStayPrice(
            $reservation->reservable_type,
            $reservation->reservable_id,
            $request->check_in_date,
            $request->check_out_date,
            [
                'apartment_id' => $reservation->apartment_id,
                'apartment_quantity' => $reservation->apartment_quantity
            ]
        );

        // 4. Create Request
        $changeRequest = ReservationChangeRequest::create([
            'reservation_id' => $reservation->id,
            'requested_check_in' => $request->check_in_date,
            'requested_check_out' => $request->check_out_date,
            'requested_total_price' => $requestedTotalPrice,
            'old_check_in' => $reservation->check_in_date,
            'old_check_out' => $reservation->check_out_date,
            'old_total_price' => $reservation->total_price,
            'status' => 'pending',
            'requester_id' => $user->id,
            'requester_type' => $this->getRequesterType($user, $reservation),
            'reason' => $request->reason,
        ]);

        return ApiResponseService::successResponse('Change request submitted successfully', ['change_request' => $changeRequest]);
    }

    /**
     * Approve a change request.
     */
    public function approveChange(Request $request, $id)
    {
        $changeRequest = ReservationChangeRequest::findOrFail($id);
        $reservation = $changeRequest->reservation;

        if ($changeRequest->status !== 'pending' && $changeRequest->status !== 'waiting_for_payment') {
            return ApiResponseService::errorResponse('This request is not in a valid status for approval.');
        }

        // Check if price increased
        if ($changeRequest->requested_total_price > $changeRequest->old_total_price) {
            $difference = $changeRequest->requested_total_price - $changeRequest->old_total_price;
            
            // Set status to waiting_for_payment
            $changeRequest->status = 'waiting_for_payment';
            $changeRequest->save();

            // Initialize Payment Service
            $paymentSettings = HelperService::getActivePaymentDetails('paymob');
            if (empty($paymentSettings)) {
                return ApiResponseService::errorResponse('Payment system is not configured.');
            }

            // Metadata for callback identification
            $metadata = [
                'change_request_id' => $changeRequest->id,
                'reservation_id' => $reservation->id,
                'type' => 'reservation_change',
                'email' => $reservation->customer->email ?? 'no-email@ashome.com',
                'first_name' => explode(' ', $reservation->customer->name ?? 'Guest')[0],
                'last_name' => explode(' ', $reservation->customer->name ?? 'Guest', 2)[1] ?? 'Guest',
                'phone' => $reservation->customer->mobile ?? '0000000000'
            ];

            try {
                // Generate unique transaction ID for Paymob
                $transactionId = 'RESC_' . $changeRequest->id . '_' . time();

                $paymentIntent = PaymentService::create($paymentSettings)->createAndFormatPaymentIntent(
                    round($difference, 2),
                    $metadata
                );

                return ApiResponseService::successResponse('Approval pending payment', [
                    'status' => 'waiting_for_payment',
                    'difference' => $difference,
                    'payment_intent' => $paymentIntent
                ]);
            } catch (\Exception $e) {
                Log::error('Change request payment generation failed: ' . $e->getMessage());
                return ApiResponseService::errorResponse('Failed to generate payment link: ' . $e->getMessage());
            }
        } else {
            // Price is same or less - apply immediately
            $this->reservationService->applyReservationChange(
                $reservation,
                $changeRequest->requested_check_in,
                $changeRequest->requested_check_out,
                (float) $changeRequest->requested_total_price
            );

            $changeRequest->status = 'completed';
            $changeRequest->handheld_at = now();
            $changeRequest->save();

            return ApiResponseService::successResponse('Change request approved and applied successfully.');
        }
    }

    /**
     * Reject a change request.
     */
    public function rejectChange(Request $request, $id)
    {
        $changeRequest = ReservationChangeRequest::findOrFail($id);
        if ($changeRequest->status !== 'pending') {
            return ApiResponseService::errorResponse('This request is not in pending status.');
        }

        $changeRequest->status = 'rejected';
        $changeRequest->handheld_at = now();
        $changeRequest->save();

        return ApiResponseService::successResponse('Change request rejected successfully.');
    }

    /**
     * Get change requests.
     */
    public function getChangeRequests(Request $request)
    {
        $query = ReservationChangeRequest::with(['reservation', 'requester', 'reservation.customer']);

        if ($request->has('reservation_id')) {
            $query->where('reservation_id', $request->reservation_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // If user is guest, only show theirs
        $user = Auth::guard('sanctum')->user();
        if ($user && $user->type !== 'admin') {
             // For simplicity, limiting to requester or owner
             $query->where(function($q) use ($user) {
                 $q->where('requester_id', $user->id)
                   ->orWhereHas('reservation', function($qr) use ($user) {
                       $qr->where('customer_id', $user->id);
                   });
             });
        }

        return ApiResponseService::successResponse('Requests retrieved successfully', $query->get());
    }

    private function getRequesterType($user, $reservation)
    {
        if ($user->id == $reservation->customer_id) return 'guest';
        if ($user->type == 'admin') return 'admin';
        return 'host';
    }
}
