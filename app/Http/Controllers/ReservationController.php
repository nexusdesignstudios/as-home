<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Property;
use App\Models\HotelRoom;
use App\Models\Reservation;
use App\Models\PaymobPayment;
use Illuminate\Http\Request;
use App\Services\ReservationService;
use App\Services\ApiResponseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    protected $reservationService;
    protected $apiResponseService;

    /**
     * Create a new controller instance.
     *
     * @param ReservationService $reservationService
     * @param ApiResponseService $apiResponseService
     */
    public function __construct(ReservationService $reservationService, ApiResponseService $apiResponseService)
    {
        $this->reservationService = $reservationService;
        $this->apiResponseService = $apiResponseService;
    }

    /**
     * Check availability for a property or room.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reservable_id' => 'required|integer',
            'reservable_type' => 'required|in:property,hotel_room',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
        ]);

        // Custom validation for dates
        $checkInDate = Carbon::parse($request->check_in_date);
        $checkOutDate = Carbon::parse($request->check_out_date);
        $today = Carbon::today();

        // Check for 31st day restriction
        if ($checkInDate->format('d') == '31' || $checkOutDate->format('d') == '31') {
            $validator->errors()->add('date_restriction', 'Reservations are not allowed on the 31st day of any month');
        }

        // Check for past dates
        if ($checkInDate->lt($today) || $checkOutDate->lt($today)) {
            $validator->errors()->add('past_date', 'Check-in and check-out dates cannot be in the past');
        }

        if ($validator->fails()) {
            ApiResponseService::errorResponse('Validation failed', $validator->errors());
        }

        // Map the reservable type to the model class
        $modelType = $request->reservable_type === 'property'
            ? 'App\\Models\\Property'
            : 'App\\Models\\HotelRoom';

        // Check availability
        $isAvailable = $this->reservationService->areDatesAvailable(
            $modelType,
            $request->reservable_id,
            $request->check_in_date,
            $request->check_out_date
        );

        ApiResponseService::successResponse('Availability checked successfully', [
            'is_available' => $isAvailable
        ]);
    }

    /**
     * Create a new reservation.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createReservation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reservable_id' => 'required|integer',
            'reservable_type' => 'required|in:property,hotel_room',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_guests' => 'integer|min:1',
            'special_requests' => 'nullable|string',
        ]);

        // Custom validation for dates
        $checkInDate = Carbon::parse($request->check_in_date);
        $checkOutDate = Carbon::parse($request->check_out_date);
        $today = Carbon::today();

        // Check for 31st day restriction
        if ($checkInDate->format('d') == '31' || $checkOutDate->format('d') == '31') {
            $validator->errors()->add('date_restriction', 'Reservations are not allowed on the 31st day of any month');
        }

        // Check for past dates
        if ($checkInDate->lt($today) || $checkOutDate->lt($today)) {
            $validator->errors()->add('past_date', 'Check-in and check-out dates cannot be in the past');
        }

        if ($validator->fails()) {
            ApiResponseService::errorResponse('Validation failed', $validator->errors());
        }

        // Map the reservable type to the model class
        $modelType = $request->reservable_type === 'property'
            ? 'App\\Models\\Property'
            : 'App\\Models\\HotelRoom';

        // Get the model to calculate price
        $model = $request->reservable_type === 'property'
            ? Property::find($request->reservable_id)
            : HotelRoom::find($request->reservable_id);

        if (!$model) {
            ApiResponseService::errorResponse('Item not found');
        }

        // Check availability first
        $isAvailable = $this->reservationService->areDatesAvailable(
            $modelType,
            $request->reservable_id,
            $request->check_in_date,
            $request->check_out_date
        );

        if (!$isAvailable) {
            ApiResponseService::errorResponse('Selected dates are not available');
        }

        // Calculate total price
        $checkIn = Carbon::parse($request->check_in_date);
        $checkOut = Carbon::parse($request->check_out_date);
        $numberOfDays = $checkIn->diffInDays($checkOut);

        $basePrice = $request->reservable_type === 'property'
            ? $model->price
            : $model->price_per_night;

        $totalPrice = $basePrice * $numberOfDays;

        // Create reservation data
        $reservationData = [
            'customer_id' => Auth::guard('sanctum')->user()->id,
            'reservable_id' => $request->reservable_id,
            'reservable_type' => $modelType,
            'check_in_date' => $request->check_in_date,
            'check_out_date' => $request->check_out_date,
            'number_of_guests' => $request->number_of_guests ?? 1,
            'total_price' => $totalPrice,
            'special_requests' => $request->special_requests,
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ];

        try {
            // Create the reservation
            $reservation = $this->reservationService->createReservation($reservationData);

            ApiResponseService::successResponse('Reservation created successfully', [
                'reservation' => $reservation
            ]);
        } catch (\Exception $e) {
            ApiResponseService::errorResponse('Failed to create reservation: ' . $e->getMessage());
        }
    }

    /**
     * Get reservations for the authenticated customer.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerReservations(Request $request)
    {
        $customerId = Auth::guard('sanctum')->user()->id;
        $status = $request->status ? explode(',', $request->status) : null;

        $query = Reservation::where('customer_id', $customerId);

        if ($status) {
            $query->whereIn('status', $status);
        }

        $reservations = $query->with('reservable')->orderBy('created_at', 'desc')->get();

        ApiResponseService::successResponse('Reservations retrieved successfully', [
            'reservations' => $reservations
        ]);
    }

    /**
     * Get a specific reservation.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReservation($id)
    {
        $customerId = Auth::guard('sanctum')->user()->id;
        $reservation = Reservation::where('id', $id)
            ->where('customer_id', $customerId)
            ->with('reservable')
            ->first();

        if (!$reservation) {
            ApiResponseService::errorResponse('Reservation not found');
        }

        ApiResponseService::successResponse('Reservation retrieved successfully', [
            'reservation' => $reservation
        ]);
    }

    /**
     * Cancel a reservation.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelReservation($id)
    {
        $customerId = Auth::guard('sanctum')->user()->id;
        $reservation = Reservation::where('id', $id)
            ->where('customer_id', $customerId)
            ->first();

        if (!$reservation) {
            ApiResponseService::errorResponse('Reservation not found');
        }

        if ($reservation->status !== 'pending' && $reservation->status !== 'confirmed') {
            ApiResponseService::errorResponse('This reservation cannot be cancelled');
        }

        try {
            $reservation = $this->reservationService->cancelReservation($id);

            ApiResponseService::successResponse('Reservation cancelled successfully', [
                'reservation' => $reservation
            ]);
        } catch (\Exception $e) {
            ApiResponseService::errorResponse('Failed to cancel reservation: ' . $e->getMessage());
        }
    }

    /**
     * Get all reservations (admin only).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllReservations(Request $request)
    {
        $status = $request->status ? explode(',', $request->status) : null;
        $propertyId = $request->property_id;
        $roomId = $request->room_id;

        $query = Reservation::with(['customer', 'reservable']);

        if ($status) {
            $query->whereIn('status', $status);
        }

        if ($propertyId) {
            $query->where(function ($q) use ($propertyId) {
                $q->where('reservable_type', 'App\\Models\\Property')
                    ->where('reservable_id', $propertyId);
            })->orWhere(function ($q) use ($propertyId) {
                $q->where('reservable_type', 'App\\Models\\HotelRoom')
                    ->whereHas('reservable', function ($q) use ($propertyId) {
                        $q->where('property_id', $propertyId);
                    });
            });
        }

        if ($roomId) {
            $query->where(function ($q) use ($roomId) {
                $q->where('reservable_type', 'App\\Models\\HotelRoom')
                    ->where('reservable_id', $roomId);
            });
        }

        $reservations = $query->orderBy('created_at', 'desc')->paginate(10);

        ApiResponseService::successResponse('Reservations retrieved successfully', [
            'reservations' => $reservations
        ]);
    }

    /**
     * Update reservation status (admin only).
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateReservationStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,cancelled,completed',
            'payment_status' => 'nullable|in:paid,unpaid,partial',
        ]);

        if ($validator->fails()) {
            ApiResponseService::errorResponse('Validation failed', $validator->errors());
        }

        $reservation = Reservation::find($id);

        if (!$reservation) {
            ApiResponseService::errorResponse('Reservation not found');
        }

        try {
            // If cancelling, use the service to update available dates
            if ($request->status === 'cancelled' && $reservation->status !== 'cancelled') {
                $reservation = $this->reservationService->cancelReservation($id);
            } else {
                $reservation->status = $request->status;

                if ($request->has('payment_status')) {
                    $reservation->payment_status = $request->payment_status;
                }

                $reservation->save();
            }

            ApiResponseService::successResponse('Reservation status updated successfully', [
                'reservation' => $reservation
            ]);
        } catch (\Exception $e) {
            ApiResponseService::errorResponse('Failed to update reservation: ' . $e->getMessage());
        }
    }

    /**
     * Get payment details for a specific reservation.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReservationPayment($id)
    {
        $customerId = Auth::guard('sanctum')->user()->id;
        $reservation = Reservation::where('id', $id)
            ->where('customer_id', $customerId)
            ->first();

        if (!$reservation) {
            ApiResponseService::errorResponse('Reservation not found');
        }

        $payment = PaymobPayment::where('reservation_id', $id)->first();

        if (!$payment) {
            ApiResponseService::errorResponse('No payment found for this reservation');
        }

        ApiResponseService::successResponse('Payment details retrieved successfully', [
            'payment' => $payment
        ]);
    }

    /**
     * Create a reservation with payment in a single step.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createReservationWithPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reservable_id' => 'required|integer',
            'reservable_type' => 'required|in:property,hotel_room',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_guests' => 'integer|min:1',
            'special_requests' => 'nullable|string',
            'payment' => 'required|array',
            'payment.amount' => 'required|numeric|min:1',
            'payment.email' => 'required|email',
            'payment.first_name' => 'required|string',
            'payment.last_name' => 'required|string',
            'payment.phone' => 'required|string',
        ]);

        // Custom validation for dates
        $checkInDate = Carbon::parse($request->check_in_date);
        $checkOutDate = Carbon::parse($request->check_out_date);
        $today = Carbon::today();

        // Check for 31st day restriction
        if ($checkInDate->format('d') == '31' || $checkOutDate->format('d') == '31') {
            $validator->errors()->add('date_restriction', 'Reservations are not allowed on the 31st day of any month');
        }

        // Check for past dates
        if ($checkInDate->lt($today) || $checkOutDate->lt($today)) {
            $validator->errors()->add('past_date', 'Check-in and check-out dates cannot be in the past');
        }

        if ($validator->fails()) {
            ApiResponseService::errorResponse('Validation failed', $validator->errors());
        }

        // Map the reservable type to the model class
        $modelType = $request->reservable_type === 'property'
            ? 'App\\Models\\Property'
            : 'App\\Models\\HotelRoom';

        // Get the model to calculate price
        $model = $request->reservable_type === 'property'
            ? Property::find($request->reservable_id)
            : HotelRoom::find($request->reservable_id);

        if (!$model) {
            ApiResponseService::errorResponse('Item not found');
        }

        // Check availability first
        $isAvailable = $this->reservationService->areDatesAvailable(
            $modelType,
            $request->reservable_id,
            $request->check_in_date,
            $request->check_out_date
        );

        if (!$isAvailable) {
            ApiResponseService::errorResponse('Selected dates are not available');
        }

        try {
            $customerId = Auth::guard('sanctum')->user()->id;
            $discountInfo = $this->calculateCustomerDiscount(
                $customerId,
                $modelType,
                $request->payment['amount']
            );

            // Generate a unique transaction ID that's compatible with Paymob
            // Paymob expects merchant_order_id to be a string, so we'll use a timestamp-based ID
            $transactionId = 'RES_' . time() . '_' . Auth::guard('sanctum')->user()->id . '_' . rand(1000, 9999);

            // Use database transaction only for database operations
            $reservation = null;
            $payment = null;

            DB::transaction(function () use ($request, $modelType, $discountInfo, $transactionId, &$reservation, &$payment) {
                // Create temporary reservation to hold the details
                $reservation = Reservation::create([
                    'customer_id' => Auth::guard('sanctum')->user()->id,
                    'reservable_id' => $request->reservable_id,
                    'reservable_type' => $modelType,
                    'check_in_date' => $request->check_in_date,
                    'check_out_date' => $request->check_out_date,
                    'number_of_guests' => $request->number_of_guests ?? 1,
                    'total_price' => $discountInfo['final_amount'],
                    'special_requests' => $request->special_requests,
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                    'payment_method' => 'paymob',
                    'transaction_id' => $transactionId,
                ]);

                // Create payment record
                $payment = PaymobPayment::create([
                    'customer_id' => Auth::guard('sanctum')->user()->id,
                    'transaction_id' => $transactionId,
                    'amount' => $discountInfo['final_amount'],
                    'currency' => config('paymob.currency', 'EGP'),
                    'status' => 'pending',
                    'payment_method' => 'paymob',
                    'reservable_id' => $request->reservable_id,
                    'reservable_type' => $modelType,
                    'reservation_id' => $reservation->id,
                ]);
            });

            // Create the payment intent outside of the transaction (external API call)
            $paymentData = [
                'payment_method' => 'paymob',
                'paymob_api_key' => config('paymob.api_key'),
                'paymob_integration_id' => config('paymob.integration_id'),
                'paymob_iframe_id' => config('paymob.iframe_id'),
                'paymob_currency' => config('paymob.currency'),
            ];

            $metadata = [
                'email' => $request->payment['email'],
                'first_name' => $request->payment['first_name'],
                'last_name' => $request->payment['last_name'],
                'phone' => $request->payment['phone'],
                'payment_transaction_id' => $transactionId,
            ];

            // Create payment service
            $paymentService = app(\App\Services\Payment\PaymentService::class)->create($paymentData);

            // Create payment intent
            $paymentIntent = $paymentService->createAndFormatPaymentIntent($discountInfo['final_amount'], $metadata);

            return ApiResponseService::successResponse('Reservation and payment intent created successfully', [
                'reservation' => $reservation,
                'payment_intent' => $paymentIntent,
                'transaction_id' => $transactionId,
                'discount_info' => $discountInfo,
            ]);
        } catch (\Exception $e) {
            // If payment intent creation fails, we should clean up the created records
            if (isset($reservation) && isset($payment)) {
                try {
                    DB::transaction(function () use ($reservation, $payment) {
                        $payment->delete();
                        $reservation->delete();
                    });
                } catch (\Exception $cleanupException) {
                    // Log cleanup failure but don't throw it
                    Log::error('Failed to cleanup reservation and payment after payment intent failure', [
                        'reservation_id' => $reservation->id ?? null,
                        'payment_id' => $payment->id ?? null,
                        'cleanup_error' => $cleanupException->getMessage()
                    ]);
                }
            }

            throw new \Exception('Failed to create reservation with payment: ' . $e->getMessage());
        }
    }
    private function calculateCustomerDiscount($customerId, $reservableType, $originalAmount)
    {
        $completedBookings = Reservation::where('customer_id', $customerId)
            ->where('reservable_type', $reservableType)
            ->where('status', 'confirmed')
            ->count();

        $discountPercentage = 0;

        if ($reservableType === 'App\\Models\\Property') {

            if ($completedBookings == 15) {
                $discountPercentage = 10;
            } elseif ($completedBookings == 10) {
                $discountPercentage = 7;
            } elseif ($completedBookings == 5) {
                $discountPercentage = 3;
            }
        } elseif ($reservableType === 'App\\Models\\HotelRoom') {
            if ($completedBookings == 20) {
                $discountPercentage = 5;
            } elseif ($completedBookings == 15) {
                $discountPercentage = 4;
            } elseif ($completedBookings == 10) {
                $discountPercentage = 2;
            }
        }

        $discountAmount = ($originalAmount * $discountPercentage) / 100;
        $finalAmount = $originalAmount - $discountAmount;

        return [
            'original_amount' => $originalAmount,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'completed_bookings' => $completedBookings
        ];
    }
}
