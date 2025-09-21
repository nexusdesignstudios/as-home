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
            'reservable_type' => 'required|in:property,hotel_room',
            'property_id' => 'required|integer|exists:propertys,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_guests' => 'integer|min:1',
            'special_requests' => 'nullable|string',
        ]);

        // Add conditional validation rules based on reservable_type
        if ($request->reservable_type === 'property') {
            $validator->addRules([
                'reservable_id' => 'required|integer|exists:propertys,id',
            ]);
        } else {
            // For hotel_room, reservable_id should be an array of room objects with id and amount
            $validator->addRules([
                'reservable_id' => 'required|array',
                'reservable_id.*.id' => 'required|integer|exists:hotel_rooms,id',
                'reservable_id.*.amount' => 'required|numeric|min:0',
            ]);
        }

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

        try {
            // Handle property reservations
            if ($request->reservable_type === 'property') {
                // Get the property model
                $property = Property::find($request->reservable_id);

                if (!$property) {
                    ApiResponseService::errorResponse('Property not found');
                }

                // Check availability first
                $isAvailable = $this->reservationService->areDatesAvailable(
                    $modelType,
                    $request->reservable_id,
                    $request->check_in_date,
                    $request->check_out_date
                );

                if (!$isAvailable) {
                    ApiResponseService::errorResponse('Selected dates are not available for this property');
                }

                // Calculate total price
                $checkIn = Carbon::parse($request->check_in_date);
                $checkOut = Carbon::parse($request->check_out_date);
                $numberOfDays = $checkIn->diffInDays($checkOut);
                $totalPrice = $property->price * $numberOfDays;

                // Create reservation data
                $reservationData = [
                    'customer_id' => Auth::guard('sanctum')->user()->id,
                    'reservable_id' => $request->reservable_id,
                    'reservable_type' => $modelType,
                    'property_id' => $request->property_id,
                    'check_in_date' => $request->check_in_date,
                    'check_out_date' => $request->check_out_date,
                    'number_of_guests' => $request->number_of_guests ?? 1,
                    'total_price' => $totalPrice,
                    'special_requests' => $request->special_requests,
                    'status' => 'pending',
                    'payment_status' => 'unpaid',
                ];

                // Create the reservation
                $reservation = $this->reservationService->createReservation($reservationData);

                ApiResponseService::successResponse('Reservation created successfully', [
                    'reservation' => $reservation
                ]);
            }
            // Handle hotel room reservations
            else {
                $roomObjects = $request->reservable_id;
                $reservations = [];
                $totalAmount = 0;
                $checkIn = Carbon::parse($request->check_in_date);
                $checkOut = Carbon::parse($request->check_out_date);
                $numberOfDays = $checkIn->diffInDays($checkOut);

                // Validate all rooms exist and are available
                foreach ($roomObjects as $roomObject) {
                    $roomId = $roomObject['id'];
                    $roomAmount = $roomObject['amount'];

                    $room = HotelRoom::find($roomId);

                    if (!$room) {
                        ApiResponseService::errorResponse("Hotel room with ID {$roomId} not found");
                    }

                    // Check if the room belongs to the specified property
                    if ($room->property_id != $request->property_id) {
                        ApiResponseService::errorResponse("Room {$roomId} does not belong to the specified property");
                    }

                    // Check availability
                    $isAvailable = $this->reservationService->areDatesAvailable(
                        $modelType,
                        $roomId,
                        $request->check_in_date,
                        $request->check_out_date
                    );

                    if (!$isAvailable) {
                        ApiResponseService::errorResponse("Room {$roomId} is not available for the selected dates");
                    }
                }

                // All validations passed, create reservations for each room
                foreach ($roomObjects as $roomObject) {
                    $roomId = $roomObject['id'];
                    $roomAmount = $roomObject['amount'];
                    $totalAmount += $roomAmount;

                    $reservationData = [
                        'customer_id' => Auth::guard('sanctum')->user()->id,
                        'reservable_id' => $roomId,
                        'reservable_type' => $modelType,
                        'property_id' => $request->property_id,
                        'check_in_date' => $request->check_in_date,
                        'check_out_date' => $request->check_out_date,
                        'number_of_guests' => $request->number_of_guests ?? 1,
                        'total_price' => $roomAmount,
                        'special_requests' => $request->special_requests,
                        'status' => 'pending',
                        'payment_status' => 'unpaid',
                    ];

                    $reservations[] = $this->reservationService->createReservation($reservationData);
                }

                ApiResponseService::successResponse('Multiple room reservations created successfully', [
                    'reservations' => $reservations,
                    'total_amount' => $totalAmount,
                    'rooms_count' => count($reservations)
                ]);
            }
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
            'status' => 'required|in:pending,approved,confirmed,cancelled,completed',
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
            $oldStatus = $reservation->status;
            $newStatus = $request->status;

            // If changing from pending to confirmed, use the service method to handle the full confirmation logic
            if ($oldStatus === 'pending' && $newStatus === 'confirmed') {
                $paymentStatus = $request->payment_status ?? 'paid';
                $this->reservationService->handleReservationConfirmation($reservation, $paymentStatus);

                ApiResponseService::successResponse('Reservation confirmed successfully. Available dates updated and confirmation email sent.', [
                    'reservation' => $reservation->fresh()
                ]);
                return;
            }

            // If cancelling, use the service to update available dates
            if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                $reservation = $this->reservationService->cancelReservation($id);
            } elseif ($newStatus === 'approved') {
                // Handle approved status - send approval email
                $reservation->status = $newStatus;

                if ($request->has('payment_status')) {
                    $reservation->payment_status = $request->payment_status;
                }

                $reservation->save();

                // Send approval email
                $this->reservationService->sendReservationApprovalEmail($reservation);

                ApiResponseService::successResponse('Reservation approved successfully. Approval email sent to customer.', [
                    'reservation' => $reservation
                ]);
                return;
            } else {
                $reservation->status = $newStatus;

                if ($request->has('payment_status')) {
                    $reservation->payment_status = $request->payment_status;
                }

                $reservation->save();
            }

            ApiResponseService::successResponse('Reservation status updated successfully', [
                'reservation' => $reservation
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to update reservation status via API', [
                'reservation_id' => $id,
                'old_status' => $oldStatus ?? 'unknown',
                'new_status' => $newStatus ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
            'reservable_type' => 'required|in:property,hotel_room',
            'property_id' => 'required|integer|exists:propertys,id',
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

        // Add conditional validation rules based on reservable_type
        if ($request->reservable_type === 'property') {
            $validator->addRules([
                'reservable_id' => 'required|integer|exists:propertys,id',
            ]);
        } else {
            // For hotel_room, reservable_id should be an array of room objects with id and amount
            $validator->addRules([
                'reservable_id' => 'required|array',
                'reservable_id.*.id' => 'required|integer|exists:hotel_rooms,id',
                'reservable_id.*.amount' => 'required|numeric|min:0',
            ]);
        }

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

        try {
            $customerId = Auth::guard('sanctum')->user()->id;

            // Generate a unique transaction ID that's compatible with Paymob
            // Paymob expects merchant_order_id to be a string, so we'll use a timestamp-based ID
            $transactionId = 'RES_' . time() . '_' . $customerId . '_' . rand(1000, 9999);

            // Handle property reservations
            if ($request->reservable_type === 'property') {
                // Get the property model
                $property = Property::find($request->reservable_id);

                if (!$property) {
                    ApiResponseService::errorResponse('Property not found');
                }

                // Check availability first
                $isAvailable = $this->reservationService->areDatesAvailable(
                    $modelType,
                    $request->reservable_id,
                    $request->check_in_date,
                    $request->check_out_date
                );

                if (!$isAvailable) {
                    ApiResponseService::errorResponse('Selected dates are not available for this property');
                }

                // Calculate discount
                $discountInfo = $this->calculateCustomerDiscount(
                    $customerId,
                    $modelType,
                    $request->payment['amount']
                );

                // Use database transaction
                $reservation = null;
                $payment = null;

                DB::transaction(function () use ($request, $modelType, $discountInfo, $transactionId, &$reservation, &$payment) {
                    // Create temporary reservation to hold the details
                    $reservation = Reservation::create([
                        'customer_id' => Auth::guard('sanctum')->user()->id,
                        'reservable_id' => $request->reservable_id,
                        'reservable_type' => $modelType,
                        'property_id' => $request->property_id,
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
            }
            // Handle hotel room reservations
            else {
                $roomObjects = $request->reservable_id;
                $reservations = [];
                $checkIn = Carbon::parse($request->check_in_date);
                $checkOut = Carbon::parse($request->check_out_date);
                $numberOfDays = $checkIn->diffInDays($checkOut);

                // Validate all rooms exist and are available
                foreach ($roomObjects as $roomObject) {
                    $roomId = $roomObject['id'];
                    $roomAmount = $roomObject['amount'];

                    $room = HotelRoom::find($roomId);

                    if (!$room) {
                        ApiResponseService::errorResponse("Hotel room with ID {$roomId} not found");
                    }

                    // Check if the room belongs to the specified property
                    if ($room->property_id != $request->property_id) {
                        ApiResponseService::errorResponse("Room {$roomId} does not belong to the specified property");
                    }

                    // Check availability
                    $isAvailable = $this->reservationService->areDatesAvailable(
                        $modelType,
                        $roomId,
                        $request->check_in_date,
                        $request->check_out_date
                    );

                    if (!$isAvailable) {
                        ApiResponseService::errorResponse("Room {$roomId} is not available for the selected dates");
                    }
                }

                // Calculate discount on the total payment amount
                $discountInfo = $this->calculateCustomerDiscount(
                    $customerId,
                    $modelType,
                    $request->payment['amount']
                );

                // Use database transaction
                $payment = null;
                $mainReservation = null; // This will be the first reservation, linked to the payment

                DB::transaction(function () use ($request, $modelType, $roomObjects, $discountInfo, $transactionId, &$reservations, &$payment, &$mainReservation) {
                    // Create reservations for each room
                    foreach ($roomObjects as $index => $roomObject) {
                        $roomId = $roomObject['id'];
                        $roomAmount = $roomObject['amount'];

                        // For the first room, create a reservation that will be linked to the payment
                        if ($index === 0) {
                            $mainReservation = Reservation::create([
                                'customer_id' => Auth::guard('sanctum')->user()->id,
                                'reservable_id' => $roomId,
                                'reservable_type' => $modelType,
                                'property_id' => $request->property_id,
                                'check_in_date' => $request->check_in_date,
                                'check_out_date' => $request->check_out_date,
                                'number_of_guests' => $request->number_of_guests ?? 1,
                                'total_price' => $roomAmount,
                                'special_requests' => $request->special_requests,
                                'status' => 'pending',
                                'payment_status' => 'unpaid',
                                'payment_method' => 'paymob',
                                'transaction_id' => $transactionId,
                            ]);

                            $reservations[] = $mainReservation;

                            // Create payment record linked to the first reservation
                            $payment = PaymobPayment::create([
                                'customer_id' => Auth::guard('sanctum')->user()->id,
                                'transaction_id' => $transactionId,
                                'amount' => $discountInfo['final_amount'], // Use the total discounted amount
                                'currency' => config('paymob.currency', 'EGP'),
                                'status' => 'pending',
                                'payment_method' => 'paymob',
                                'reservable_id' => $roomId,
                                'reservable_type' => $modelType,
                                'reservation_id' => $mainReservation->id,
                            ]);
                        } else {
                            // For subsequent rooms, create reservations with the same transaction ID
                            $reservation = Reservation::create([
                                'customer_id' => Auth::guard('sanctum')->user()->id,
                                'reservable_id' => $roomId,
                                'reservable_type' => $modelType,
                                'property_id' => $request->property_id,
                                'check_in_date' => $request->check_in_date,
                                'check_out_date' => $request->check_out_date,
                                'number_of_guests' => $request->number_of_guests ?? 1,
                                'total_price' => $roomAmount,
                                'special_requests' => $request->special_requests,
                                'status' => 'pending',
                                'payment_status' => 'unpaid',
                                'payment_method' => 'paymob',
                                'transaction_id' => $transactionId, // Same transaction ID for all reservations
                            ]);

                            $reservations[] = $reservation;
                        }
                    }
                });

                // Set the reservation variable for the payment intent creation
                $reservation = $mainReservation;
            }

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

            // Prepare response based on reservation type
            if ($request->reservable_type === 'property') {
                return ApiResponseService::successResponse('Reservation and payment intent created successfully', [
                    'reservation' => $reservation,
                    'payment_intent' => $paymentIntent,
                    'transaction_id' => $transactionId,
                    'discount_info' => $discountInfo,
                ]);
            } else {
                return ApiResponseService::successResponse('Multiple room reservations and payment intent created successfully', [
                    'reservations' => $reservations,
                    'main_reservation_id' => $mainReservation->id,
                    'payment_intent' => $paymentIntent,
                    'transaction_id' => $transactionId,
                    'discount_info' => $discountInfo,
                    'rooms_count' => count($reservations)
                ]);
            }
        } catch (\Exception $e) {
            // If payment intent creation fails, we should clean up the created records
            if ($request->reservable_type === 'property') {
                // Clean up single reservation
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
            } else {
                // Clean up multiple reservations
                if (isset($reservations) && !empty($reservations) && isset($payment)) {
                    try {
                        DB::transaction(function () use ($reservations, $payment) {
                            $payment->delete();

                            // Delete all created reservations
                            foreach ($reservations as $res) {
                                $res->delete();
                            }
                        });
                    } catch (\Exception $cleanupException) {
                        // Log cleanup failure but don't throw it
                        Log::error('Failed to cleanup multiple reservations and payment after payment intent failure', [
                            'reservation_ids' => collect($reservations)->pluck('id')->toArray(),
                            'payment_id' => $payment->id ?? null,
                            'cleanup_error' => $cleanupException->getMessage()
                        ]);
                    }
                }
            }

            throw new \Exception('Failed to create reservation with payment: ' . $e->getMessage());
        }
    }

    /**
     * Get reservations for properties owned by a specific customer.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPropertyOwnerReservations(Request $request, $customer_id)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'nullable|integer|exists:propertys,id',
            'status' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            ApiResponseService::errorResponse('Validation failed', $validator->errors());
        }

        $customerId = $customer_id;
        $propertyId = $request->property_id;
        $status = $request->status ? explode(',', $request->status) : null;
        $perPage = $request->per_page ?? 10;

        // Start building the query for reservations
        $query = Reservation::query();

        if ($propertyId) {
            // If specific property is provided, filter by property_id
            $query->where('property_id', $propertyId);
        } else {
            // Get all properties owned by the customer
            $query->whereHas('property', function ($propertyQuery) use ($customerId) {
                $propertyQuery->where('added_by', $customerId);
            });
        }

        // Add status filter if provided
        if ($status) {
            $query->whereIn('status', $status);
        }

        // Add relationships and pagination with proper handling of polymorphic relationships
        $reservations = $query->with([
            'customer:id,name,email,mobile',
            'property:id,title,category_id,price,title_image,property_classification',
            'property.category:id,category,image',
            // Use morphWith to correctly load relationships based on the model type
            'reservable'
        ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Transform the data to provide more context about each reservation
        $formattedReservations = $reservations->through(function ($reservation) {
            $data = $reservation->toArray();

            // Add reservation type for easier frontend handling
            $data['reservation_type'] = $reservation->reservable_type === 'App\\Models\\Property' ? 'property' : 'hotel_room';

            // Always include the main property information from the direct property relationship
            if (isset($reservation->property)) {
                $data['property_info'] = [
                    'id' => $reservation->property->id,
                    'title' => $reservation->property->title,
                    'title_image' => $reservation->property->title_image,
                    'property_classification' => $reservation->property->property_classification
                ];

                // Add category information if available
                if (isset($reservation->property->category)) {
                    $data['property_info']['category'] = [
                        'id' => $reservation->property->category->id,
                        'name' => $reservation->property->category->category,
                        'image' => $reservation->property->category->image
                    ];
                }
            }

            // Add specific information based on reservation type
            if ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                // For hotel room reservations, add room-specific information
                if (isset($reservation->reservable)) {
                    $hotelRoom = $reservation->reservable;

                    // Load the room type if available
                    $roomTypeName = isset($hotelRoom->roomType) ? $hotelRoom->roomType->name : 'Unknown';

                    $data['room_info'] = [
                        'id' => $hotelRoom->id,
                        'room_number' => $hotelRoom->room_number,
                        'room_type' => $roomTypeName,
                        'price_per_night' => $hotelRoom->price_per_night
                    ];
                }
            }

            return $data;
        });

        return ApiResponseService::successResponse('Property owner reservations retrieved successfully', [
            'reservations' => $formattedReservations
        ]);
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
