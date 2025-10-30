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
use App\Services\HelperService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

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
     * Update room price in available dates.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateRoomPrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_type_id' => 'required|integer|exists:hotel_room_types,id',
            'property_id' => 'required|integer|exists:propertys,id',
            'price' => 'required|numeric|min:0',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ], [
            'to.after_or_equal' => 'The to date must be after or equal to the from date.',
        ]);

        // Debug: Log the request data and validation rules
        \Log::info('UpdateRoomPrice Request:', [
            'data' => $request->all(),
            'validation_rules' => [
                'to' => 'required|date|after_or_equal:from'
            ]
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed:', $validator->errors()->toArray());
            return ApiResponseService::errorResponse('Validation failed', $validator->errors());
        }

        try {
            // Get all rooms of the specified type in the property
            $rooms = HotelRoom::where('property_id', $request->property_id)
                ->where('room_type_id', $request->room_type_id)
                ->get();

            if ($rooms->isEmpty()) {
                return ApiResponseService::errorResponse('No rooms found for the specified room type and property');
            }

            $updatedRooms = [];
            $fromDate = Carbon::parse($request->from);
            $toDate = Carbon::parse($request->to);

            foreach ($rooms as $room) {
                // Get current available dates
                $availableDates = $room->available_dates ?? [];

                if (!is_array($availableDates)) {
                    $availableDates = [];
                }

                // Update the available dates with new price for the specified period
                $updatedDates = $this->updatePriceInDateRange(
                    $availableDates,
                    $fromDate,
                    $toDate,
                    $request->price,
                    $room
                );

                // Update the room
                $room->available_dates = $updatedDates;
                $room->save();

                $updatedRooms[] = [
                    'room_id' => $room->id,
                    'room_number' => $room->room_number,
                    'updated_dates' => $updatedDates
                ];
            }

            return ApiResponseService::successResponse('Room prices updated successfully', [
                'updated_rooms' => $updatedRooms,
                'period' => [
                    'from' => $fromDate->format('Y-m-d'),
                    'to' => $toDate->format('Y-m-d'),
                    'price' => $request->price
                ]
            ]);

        } catch (\Exception $e) {
            return ApiResponseService::errorResponse('Failed to update room prices: ' . $e->getMessage());
        }
    }

    /**
     * Update price in date range for available dates.
     *
     * @param array $availableDates
     * @param Carbon $fromDate
     * @param Carbon $toDate
     * @param float $newPrice
     * @param HotelRoom $room
     * @return array
     */
    private function updatePriceInDateRange($availableDates, $fromDate, $toDate, $newPrice, $room)
    {
        $updatedDates = [];
        $periodAdded = false;

        // First, deduplicate existing ranges to prevent accumulation of duplicates
        $deduplicatedDates = $this->deduplicateDateRanges($availableDates);

        foreach ($deduplicatedDates as $dateInfo) {
            // Skip if this isn't a date range format
            if (!isset($dateInfo['from']) || !isset($dateInfo['to'])) {
                $updatedDates[] = $dateInfo;
                continue;
            }

            $existingFrom = Carbon::parse($dateInfo['from']);
            $existingTo = Carbon::parse($dateInfo['to']);

            // Check if this range overlaps with our update period
            if ($this->rangesOverlap($existingFrom, $existingTo, $fromDate, $toDate)) {
                // Handle different overlap scenarios
                $ranges = $this->splitRangeForPriceUpdate(
                    $existingFrom,
                    $existingTo,
                    $fromDate,
                    $toDate,
                    $dateInfo,
                    $newPrice,
                    $room
                );

                $updatedDates = array_merge($updatedDates, $ranges);
                $periodAdded = true;
            } else {
                // No overlap, keep the original range
                $updatedDates[] = $dateInfo;
            }
        }

        // If no existing range overlapped with our period, add a new range
        if (!$periodAdded) {
            $updatedDates[] = [
                'from' => $fromDate->format('Y-m-d'),
                'to' => $toDate->format('Y-m-d'),
                'price' => $newPrice,
                'type' => 'open',
                'nonrefundable_percentage' => $room->nonrefundable_percentage ?? 0
            ];
        }

        // Final deduplication to ensure clean result
        return $this->deduplicateDateRanges($updatedDates);
    }

    /**
     * Check if two date ranges overlap.
     *
     * @param Carbon $from1
     * @param Carbon $to1
     * @param Carbon $from2
     * @param Carbon $to2
     * @return bool
     */
    private function rangesOverlap($from1, $to1, $from2, $to2)
    {
        return $from1->lte($to2) && $from2->lte($to1);
    }

    /**
     * Split a date range to accommodate price updates.
     *
     * @param Carbon $existingFrom
     * @param Carbon $existingTo
     * @param Carbon $updateFrom
     * @param Carbon $updateTo
     * @param array $dateInfo
     * @param float $newPrice
     * @param HotelRoom $room
     * @return array
     */
    private function splitRangeForPriceUpdate($existingFrom, $existingTo, $updateFrom, $updateTo, $dateInfo, $newPrice, $room)
    {
        $ranges = [];

        // Before period (if exists)
        if ($existingFrom->lt($updateFrom)) {
            $ranges[] = [
                'from' => $existingFrom->format('Y-m-d'),
                'to' => $updateFrom->copy()->subDay()->format('Y-m-d'),
                'price' => $dateInfo['price'] ?? $room->price_per_night,
                'type' => $dateInfo['type'] ?? 'open',
                'nonrefundable_percentage' => $dateInfo['nonrefundable_percentage'] ?? $room->nonrefundable_percentage ?? 0
            ];
        }

        // Updated period
        $ranges[] = [
            'from' => $updateFrom->format('Y-m-d'),
            'to' => $updateTo->format('Y-m-d'),
            'price' => $newPrice,
            'type' => $dateInfo['type'] ?? 'open',
            'nonrefundable_percentage' => $dateInfo['nonrefundable_percentage'] ?? $room->nonrefundable_percentage ?? 0
        ];

        // After period (if exists)
        if ($existingTo->gt($updateTo)) {
            $ranges[] = [
                'from' => $updateTo->copy()->addDay()->format('Y-m-d'),
                'to' => $existingTo->format('Y-m-d'),
                'price' => $dateInfo['price'] ?? $room->price_per_night,
                'type' => $dateInfo['type'] ?? 'open',
                'nonrefundable_percentage' => $dateInfo['nonrefundable_percentage'] ?? $room->nonrefundable_percentage ?? 0
            ];
        }

        return $ranges;
    }

    /**
     * Deduplicate date ranges to prevent accumulation of duplicates.
     *
     * @param array $dateRanges
     * @return array
     */
    private function deduplicateDateRanges($dateRanges)
    {
        if (!is_array($dateRanges)) {
            return [];
        }

        $deduplicated = [];
        $seen = [];

        foreach ($dateRanges as $range) {
            // Skip invalid ranges
            if (!isset($range['from']) || !isset($range['to'])) {
                continue;
            }

            // Create a unique key for this range
            $key = $range['from'] . '|' . $range['to'] . '|' . ($range['price'] ?? '') . '|' . ($range['type'] ?? 'open');

            // Only add if we haven't seen this exact range before
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $deduplicated[] = $range;
            }
        }

        // Sort ranges by date for consistency
        usort($deduplicated, function ($a, $b) {
            return strcmp($a['from'], $b['from']);
        });

        return $deduplicated;
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

                // Create the reservation without sending emails (checkout without payment)
                $reservation = $this->reservationService->createReservation($reservationData, true);
                
                // Send flexible hotel booking approval email for property reservations that require approval
                $this->reservationService->sendFlexibleHotelBookingApprovalEmail($reservation);

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

                    // Check room status - allow booking of active rooms (status = true) and pending rooms
                    // Only block inactive rooms (status = false)
                    // Note: This allows booking of rooms that are pending approval
                    // if ($room->status === false) {
                    //     ApiResponseService::errorResponse("Room {$roomId} is currently inactive and cannot be booked");
                    // }

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

                    $reservation = $this->reservationService->createReservation($reservationData, true);
                    $reservations[] = $reservation;
                    
                    // Send flexible hotel booking approval email for each reservation
                    $this->reservationService->sendFlexibleHotelBookingApprovalEmail($reservation);
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
        try {
            $user = Auth::guard('sanctum')->user();
            if (!$user) {
                return ApiResponseService::errorResponse('User not authenticated', null, 401);
            }

            $customerId = $user->id;
            $status = $request->status && trim($request->status) !== '' ? explode(',', $request->status) : null;

            $query = Reservation::where('customer_id', $customerId);

            if ($status && !empty(array_filter($status))) {
                $query->whereIn('status', array_filter($status));
            }

            $reservations = $query->orderBy('created_at', 'desc')->get();

            return ApiResponseService::successResponse('Reservations retrieved successfully', [
                'reservations' => $reservations
            ]);
        } catch (Exception $e) {
            Log::error('Error in getCustomerReservations: ' . $e->getMessage(), [
                'user_id' => Auth::guard('sanctum')->user()->id ?? 'not_authenticated',
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponseService::errorResponse('An error occurred while fetching reservations: ' . $e->getMessage());
        }
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

            // Send cancellation email to the customer
            $this->sendReservationCancellationEmail($reservation, 'cancellation');

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
        $status = $request->status && trim($request->status) !== '' ? explode(',', $request->status) : null;
        $propertyId = $request->property_id;
        $roomId = $request->room_id;

        $query = Reservation::with(['customer', 'reservable']);

        if ($status && !empty(array_filter($status))) {
            $query->whereIn('status', array_filter($status));
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

                // Send cancellation email to the customer
                $this->sendReservationCancellationEmail($reservation, 'cancellation');
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
            } elseif ($newStatus === 'rejected') {
                // Handle rejected status - send decline email
                $reservation->status = $newStatus;

                if ($request->has('payment_status')) {
                    $reservation->payment_status = $request->payment_status;
                }

                $reservation->save();

                // Send decline email to the customer
                $this->sendReservationCancellationEmail($reservation, 'decline');

                ApiResponseService::successResponse('Reservation declined successfully. Decline email sent to customer.', [
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
            'review_url' => 'nullable|url',
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
                        'review_url' => $request->review_url,
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

                    // Log payment creation for debugging
                    Log::info('Payment record created in database transaction', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $payment->transaction_id,
                        'status' => $payment->status,
                        'reservation_id' => $payment->reservation_id
                    ]);
                });

                // Log payment record after transaction is committed
                Log::info('Payment record committed to database', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'status' => $payment->status,
                    'reservation_id' => $payment->reservation_id
                ]);
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

                    // Check room status - allow booking of active rooms (status = true) and pending rooms
                    // Only block inactive rooms (status = false)
                    // Note: This allows booking of rooms that are pending approval
                    // if ($room->status === false) {
                    //     ApiResponseService::errorResponse("Room {$roomId} is currently inactive and cannot be booked");
                    // }

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
                                'review_url' => $request->review_url,
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

                            // Log payment creation for debugging
                            Log::info('Hotel room payment record created in database transaction', [
                                'payment_id' => $payment->id,
                                'transaction_id' => $payment->transaction_id,
                                'status' => $payment->status,
                                'reservation_id' => $payment->reservation_id
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
                                'review_url' => $request->review_url,
                            ]);

                            $reservations[] = $reservation;
                        }
                    }
                });

                // Log payment record after transaction is committed
                Log::info('Hotel room payment record committed to database', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'status' => $payment->status,
                    'reservation_id' => $payment->reservation_id
                ]);

                // Set the reservation variable for the payment intent creation
                $reservation = $mainReservation;
            }

            // Log before creating payment intent
            Log::info('About to create payment intent with Paymob', [
                'transaction_id' => $transactionId,
                'amount' => $discountInfo['final_amount']
            ]);

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

            // Update payment record with Paymob order ID
            if (isset($payment) && isset($paymentIntent['id'])) {
                $payment->paymob_order_id = $paymentIntent['id'];
                $payment->save();

                Log::info('Payment record updated with Paymob order ID', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'paymob_order_id' => $payment->paymob_order_id,
                    'reservation_id' => $payment->reservation_id
                ]);
            }

            // Log after payment intent is created
            Log::info('Payment intent created with Paymob', [
                'transaction_id' => $transactionId,
                'payment_intent' => $paymentIntent
            ]);

            // Send flexible hotel booking approval email to customer if this is a flexible booking
            // (instant_booking = false for hotel properties)
            if (isset($reservation) && $reservation) {
                $property = $reservation->property;
                if ($property && $property->property_classification == 5 && !$property->instant_booking) {
                    try {
                        // Send flexible booking approval email to customer
                        $this->reservationService->sendFlexibleHotelBookingApprovalEmail($reservation);
                        
                        // Also send notification to property owner about the new booking
                        $this->sendNewBookingNotificationToOwner($reservation);
                        
                        Log::info('Both emails sent during payment checkout: Flexible booking approval to customer and notification to property owner', [
                            'reservation_id' => $reservation->id,
                            'customer_id' => $reservation->customer_id,
                            'property_id' => $property->id,
                            'instant_booking' => $property->instant_booking,
                            'booking_type' => 'flexible'
                        ]);
                    } catch (\Exception $e) {
                        // Log email error but don't fail the transaction
                        Log::error('Failed to send flexible hotel booking emails during payment checkout: ' . $e->getMessage(), [
                            'reservation_id' => $reservation->id,
                            'property_id' => $property->id,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
            }

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
        try {
            \Log::info('getPropertyOwnerReservations called', [
                'customer_id' => $customer_id,
                'request_params' => $request->all()
            ]);

            $validator = Validator::make($request->all(), [
                'property_id' => 'nullable|integer|exists:propertys,id',
                'status' => 'nullable|string',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return ApiResponseService::errorResponse('Validation failed', $validator->errors());
            }

        $customerId = $customer_id;
        $propertyId = $request->property_id;
        $status = $request->status && trim($request->status) !== '' ? explode(',', $request->status) : null;
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
        if ($status && !empty(array_filter($status))) {
            $query->whereIn('status', array_filter($status));
        }

        // Add relationships and pagination with proper handling of polymorphic relationships
        \Log::info('Executing reservations query', [
            'customer_id' => $customerId,
            'property_id' => $propertyId,
            'status' => $status,
            'per_page' => $perPage
        ]);

        // Simplified query to debug the issue
        try {
            $reservations = $query->with([
                'customer:id,name,email,mobile',
                'property:id,title,category_id,price,title_image,property_classification',
                'property.category:id,category,image'
                // Temporarily remove 'reservable' to test if that's the issue
            ])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        } catch (Exception $e) {
            \Log::error('Error in reservations query', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        \Log::info('Reservations query executed successfully', [
            'total_reservations' => $reservations->total(),
            'current_page' => $reservations->currentPage(),
            'per_page' => $reservations->perPage(),
            'sample_reservation' => $reservations->first() ? [
                'id' => $reservations->first()->id,
                'property_id' => $reservations->first()->property_id,
                'reservable_id' => $reservations->first()->reservable_id,
                'reservable_type' => $reservations->first()->reservable_type
            ] : null
        ]);

        // Transform the data to provide more context about each reservation
        $formattedReservations = $reservations->through(function ($reservation) {
            $data = $reservation->toArray();

            // Ensure property_id is explicitly included in the response
            $data['property_id'] = $reservation->property_id;
            $data['reservable_id'] = $reservation->reservable_id;

            // Add missing customer fields for frontend compatibility
            // These fields might exist in the database or need to be populated from relationships
            if ($reservation->customer) {
                $data['customer_name'] = $data['customer_name'] ?? $reservation->customer->name ?? null;
                $data['customer_phone'] = $data['customer_phone'] ?? $reservation->customer->mobile ?? null;
                $data['customer_email'] = $data['customer_email'] ?? $reservation->customer->email ?? null;
                $data['user_name'] = $data['user_name'] ?? $reservation->customer->name ?? null;
                $data['user_email'] = $data['user_email'] ?? $reservation->customer->email ?? null;
            }

            // Add booking_date as alias for created_at
            $data['booking_date'] = $data['booking_date'] ?? $reservation->created_at;

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
                if (isset($reservation->reservable) && $reservation->reservable) {
                    $hotelRoom = $reservation->reservable;

                    // Load the room type if available
                    $roomTypeName = isset($hotelRoom->roomType) ? $hotelRoom->roomType->name : 'Unknown';

                    $data['room_info'] = [
                        'id' => $hotelRoom->id,
                        'room_number' => $hotelRoom->room_number ?? 'N/A',
                        'room_type' => $roomTypeName,
                        'price_per_night' => $hotelRoom->price_per_night ?? 0
                    ];
                } else {
                    // Handle case where reservable relationship is null
                    $data['room_info'] = [
                        'id' => null,
                        'room_number' => 'N/A',
                        'room_type' => 'Unknown',
                        'price_per_night' => 0
                    ];
                }
            }

            return $data;
        });

            \Log::info('Final response prepared', [
                'total_reservations' => $formattedReservations->total(),
                'sample_reservation_data' => $formattedReservations->first() ? [
                    'id' => $formattedReservations->first()['id'],
                    'property_id' => $formattedReservations->first()['property_id'],
                    'reservable_id' => $formattedReservations->first()['reservable_id'],
                    'reservable_type' => $formattedReservations->first()['reservable_type']
                ] : null
            ]);

            return ApiResponseService::successResponse('Property owner reservations retrieved successfully', [
                'reservations' => $formattedReservations
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getPropertyOwnerReservations', [
                'customer_id' => $customer_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponseService::errorResponse('An error occurred while fetching reservations: ' . $e->getMessage());
        }
    }
    /**
     * Send reservation cancellation email to customer
     *
     * @param Reservation $reservation
     * @param string $type 'cancellation' or 'decline'
     * @return void
     */
    private function sendReservationCancellationEmail($reservation, $type = 'cancellation')
    {
        try {
            // Get customer information
            $customer = $reservation->customer;

            if (!$customer || !$customer->email) {
                Log::warning('Cannot send reservation email: customer or email not found', [
                    'reservation_id' => $reservation->id,
                    'customer_id' => $reservation->customer_id,
                    'type' => $type
                ]);
                return;
            }

            // Get property information
            $propertyName = 'Unknown Property';
            if ($reservation->reservable_type === 'App\\Models\\Property') {
                $property = Property::find($reservation->reservable_id);
                if ($property) {
                    $propertyName = $property->title;
                }
            } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                $hotelRoom = HotelRoom::find($reservation->reservable_id);
                if ($hotelRoom && $hotelRoom->property) {
                    $propertyName = $hotelRoom->property->title;
                }
            }

            // Format dates
            $checkInDate = Carbon::parse($reservation->check_in_date)->format('Y-m-d');
            $checkOutDate = Carbon::parse($reservation->check_out_date)->format('Y-m-d');

            // Get currency symbol
            $currencySymbol = system_setting('currency_symbol') ?? '$';

            // Determine email template and title based on type
            $emailTemplateData = '';
            $emailTitle = '';
            $defaultTemplate = '';

            if ($type === 'decline') {
                $emailTitle = 'Your Booking Request Has Been Declined';
                $emailTemplateData = system_setting('reservation_decline_mail_template');

                if (empty($emailTemplateData)) {
                    Log::warning('Reservation decline email template not found, using default template');
                    $defaultTemplate = 'Dear {customer_name},

We regret to inform you that your booking request has been declined by the property owner.

Reservation Details:
- Reservation ID: {reservation_id}
- Property: {property_name}
- Check-in Date: {check_in_date}
- Check-out Date: {check_out_date}
- Total Amount: {currency_symbol}{total_price}

The property owner was unable to accommodate your request at this time. We apologize for any inconvenience this may cause.

If you have any questions or would like to explore alternative properties, please don\'t hesitate to contact our customer support team.

Thank you for your understanding.

Best regards,
The {app_name} Team';
                }
            } else {
                // Default to cancellation
                $emailTitle = 'Your Reservation Has Been Cancelled';
            $emailTemplateData = system_setting('reservation_cancellation_mail_template');

            if (empty($emailTemplateData)) {
                Log::warning('Reservation cancellation email template not found, using default template');
                    $defaultTemplate = 'Dear {customer_name},

We are writing to confirm that your reservation has been cancelled as requested.

Reservation Details:
- Reservation ID: {reservation_id}
- Property: {property_name}
- Check-in Date: {check_in_date}
- Check-out Date: {check_out_date}
- Total Amount: {currency_symbol}{total_price}

If you requested a refund, please note that it will be processed according to our refund policy. Depending on your payment method, it may take 3-5 business days for the refund to appear in your account.

If you did not request this cancellation or have any questions, please contact our customer support team immediately.

Thank you for your understanding.

Best regards,
The {app_name} Team';
                }
            }

            // Use default template if no custom template is set
            if (empty($emailTemplateData)) {
                $emailTemplateData = $defaultTemplate;
            }

            // Prepare email variables
            $variables = [
                'app_name' => env("APP_NAME") ?? "eBroker",
                'customer_name' => $customer->name,
                'reservation_id' => $reservation->id,
                'property_name' => $propertyName,
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'total_price' => number_format($reservation->total_price, 2),
                'currency_symbol' => $currencySymbol,
                'cancellation_date' => $reservation->cancelled_at ? $reservation->cancelled_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A'),
                'refund_processing_time' => '3-5 business days',
                'current_date_today' => now()->format('d M Y, h:i A'),
            ];

            // Replace variables in template
            $emailContent = HelperService::replaceEmailVariables($emailTemplateData, $variables);

            // Send email
            $data = [
                'email' => $customer->email,
                'title' => $emailTitle,
                'email_template' => $emailContent
            ];

            HelperService::sendMail($data);

            Log::info('Reservation email sent to customer', [
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
                'reservation_id' => $reservation->id,
                'type' => $type,
                'email_title' => $emailTitle
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send reservation email: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'reservation_id' => $reservation->id,
                'type' => $type
            ]);
        }
    }

    /**
     * Get reservation counts for a specific customer (vacation homes and hotel rooms separately).
     *
     * @param int $customer_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerReservationCounts($customer_id)
    {
        try {
            // Validate customer exists
            $customer = \App\Models\Customer::find($customer_id);
            if (!$customer) {
                return ApiResponseService::errorResponse('Customer not found');
            }

            // Get vacation homes (properties) reservation count
            $vacationHomesCount = Reservation::where('customer_id', $customer_id)
                ->where('reservable_type', 'App\\Models\\Property')
                ->count();

            // Get hotel rooms reservation count
            $hotelRoomsCount = Reservation::where('customer_id', $customer_id)
                ->where('reservable_type', 'App\\Models\\HotelRoom')
                ->count();

            // Get total count
            $totalCount = $vacationHomesCount + $hotelRoomsCount;

            // Get counts by status for vacation homes
            $vacationHomesByStatus = Reservation::where('customer_id', $customer_id)
                ->where('reservable_type', 'App\\Models\\Property')
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            // Get counts by status for hotel rooms
            $hotelRoomsByStatus = Reservation::where('customer_id', $customer_id)
                ->where('reservable_type', 'App\\Models\\HotelRoom')
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            return ApiResponseService::successResponse('Customer reservation counts retrieved successfully', [
                'customer_id' => $customer_id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'total_reservations' => $totalCount,
                'vacation_homes' => [
                    'total_count' => $vacationHomesCount,
                    'by_status' => $vacationHomesByStatus
                ],
                'hotel_rooms' => [
                    'total_count' => $hotelRoomsCount,
                    'by_status' => $hotelRoomsByStatus
                ]
            ]);

        } catch (\Exception $e) {
            return ApiResponseService::errorResponse('Failed to get customer reservation counts: ' . $e->getMessage());
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

    /**
     * Send new booking notification to property owner.
     *
     * @param \App\Models\Reservation $reservation
     * @return void
     */
    private function sendNewBookingNotificationToOwner($reservation)
    {
        try {
            $property = $reservation->property;
            $propertyOwner = $property->customer;
            $customer = $reservation->customer;

            if (!$propertyOwner || !$propertyOwner->email) {
                Log::warning('Cannot send new booking notification: property owner or email not found', [
                    'reservation_id' => $reservation->id,
                    'property_id' => $property->id
                ]);
                return;
            }

            // Get email template data
            $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes('new_booking_notification');
            $templateData = system_setting('new_booking_notification_mail_template');
            $appName = env('APP_NAME') ?? 'As-home';

            // Get hotel and room information
            $hotelName = $property->title ?? 'Hotel';
            $roomType = 'Property';
            $roomNumber = 'N/A';
            $hotelAddress = $property->address ?? 'N/A';

            if ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                $hotelRoom = $reservation->reservable;
                if ($hotelRoom) {
                    $roomNumber = $hotelRoom->room_number ?? 'N/A';
                    if ($hotelRoom->roomType) {
                        $roomType = $hotelRoom->roomType->name ?? 'Standard Room';
                    }
                }
            }

            // Get currency symbol
            $currencySymbol = system_setting('currency_symbol') ?? '$';

            $variables = array(
                'app_name' => $appName,
                'property_owner_name' => $propertyOwner->name,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone ?? 'N/A',
                'hotel_name' => $hotelName,
                'room_type' => $roomType,
                'room_number' => $roomNumber,
                'hotel_address' => $hotelAddress,
                'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->format('d M Y') : 'N/A',
                'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
                'number_of_guests' => $reservation->number_of_guests,
                'total_price' => number_format($reservation->total_price, 2),
                'currency_symbol' => $currencySymbol,
                'payment_status' => ucfirst($reservation->payment_status),
                'special_requests' => $reservation->special_requests ?? 'None',
                'reservation_id' => $reservation->id,
                'booking_date' => now()->format('d M Y, h:i A'),
                'booking_type' => 'flexible_booking'
            );

            if (empty($templateData)) {
                $templateData = 'New flexible booking request received for {hotel_name} from {customer_name} ({customer_email}). Room Type: {room_type}. Amount: {total_price} {currency_symbol}. Check-in: {check_in_date}, Check-out: {check_out_date}. Reservation ID: {reservation_id}. Please review and approve this booking in your dashboard.';
            }

            $emailTemplate = \App\Services\HelperService::replaceEmailVariables($templateData, $variables);

            $data = array(
                'email_template' => $emailTemplate,
                'email' => $propertyOwner->email,
                'title' => $emailTypeData['title'] ?? 'New Flexible Booking Request - Approval Required',
            );

            \App\Services\HelperService::sendMail($data);

            Log::info('New booking notification sent to property owner', [
                'reservation_id' => $reservation->id,
                'property_owner_email' => $propertyOwner->email,
                'property_id' => $property->id,
                'booking_type' => 'flexible'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send new booking notification to property owner: ' . $e->getMessage(), [
                'reservation_id' => $reservation->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send daily checkout reminder emails to customers whose reservations are checking out today.
     * This method is designed to be called by a cronjob.
     *
     * @return array
     */
    public function sendDailyCheckoutReminders()
    {
        try {
            $today = Carbon::today();
            $sentCount = 0;
            $failedCount = 0;
            $errors = [];

            Log::info('Starting daily checkout reminders process', [
                'date' => $today->format('Y-m-d')
            ]);

            // Get all reservations checking out today with confirmed status
            $reservations = Reservation::whereDate('check_out_date', $today)
                ->where('status', 'confirmed')
                ->with(['customer', 'property'])
                ->get();

            if ($reservations->isEmpty()) {
                Log::info('No reservations found for checkout today', [
                    'date' => $today->format('Y-m-d')
                ]);
                return [
                    'success' => true,
                    'message' => 'No reservations found for checkout today',
                    'sent_count' => 0,
                    'failed_count' => 0
                ];
            }

            Log::info('Found reservations for checkout reminders', [
                'count' => $reservations->count(),
                'date' => $today->format('Y-m-d')
            ]);

            foreach ($reservations as $reservation) {
                try {
                    $this->sendCheckoutReminderEmail($reservation);
                    $sentCount++;

                    Log::info('Checkout reminder email sent successfully', [
                        'reservation_id' => $reservation->id,
                        'customer_id' => $reservation->customer_id,
                        'customer_email' => $reservation->customer->email ?? 'N/A'
                    ]);
                } catch (\Exception $e) {
                    $failedCount++;
                    $errorMessage = "Failed to send checkout reminder for reservation {$reservation->id}: " . $e->getMessage();
                    $errors[] = $errorMessage;

                    Log::error($errorMessage, [
                        'reservation_id' => $reservation->id,
                        'customer_id' => $reservation->customer_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            $result = [
                'success' => true,
                'message' => "Daily checkout reminders completed. Sent: {$sentCount}, Failed: {$failedCount}",
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'total_reservations' => $reservations->count(),
                'date' => $today->format('Y-m-d')
            ];

            if (!empty($errors)) {
                $result['errors'] = $errors;
            }

            Log::info('Daily checkout reminders process completed', $result);

            return $result;

        } catch (\Exception $e) {
            $errorMessage = 'Failed to process daily checkout reminders: ' . $e->getMessage();
            Log::error($errorMessage, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
                'sent_count' => 0,
                'failed_count' => 0
            ];
        }
    }

    /**
     * Send checkout reminder email to a specific customer.
     *
     * @param Reservation $reservation
     * @return void
     */
    private function sendCheckoutReminderEmail($reservation)
    {
        // Get customer information
        $customer = $reservation->customer;

        if (!$customer || !$customer->email) {
            throw new \Exception('Customer or email not found for reservation ' . $reservation->id);
        }

        // Get property information
        $propertyName = 'Unknown Property';
        if ($reservation->reservable_type === 'App\\Models\\Property') {
            $property = Property::find($reservation->reservable_id);
            if ($property) {
                $propertyName = $property->title;
            }
        } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
            $hotelRoom = HotelRoom::find($reservation->reservable_id);
            if ($hotelRoom && $hotelRoom->property) {
                $propertyName = $hotelRoom->property->title;
            }
        }

        // Get currency symbol
        $currencySymbol = system_setting('currency_symbol') ?? '$';

        // Prepare email variables
        $variables = [
            'app_name' => env("APP_NAME") ?? "eBroker",
            'customer_name' => $customer->name,
            'reservation_id' => $reservation->id,
            'property_name' => $propertyName,
            'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->format('d M Y') : 'N/A',
            'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
            'total_price' => number_format($reservation->total_price, 2),
            'currency_symbol' => $currencySymbol,
            'number_of_guests' => $reservation->number_of_guests,
            'special_requests' => $reservation->special_requests ?? 'None',
            'current_date_today' => now()->format('d M Y, h:i A'),
        ];

        // Get email template
        $emailTemplateData = system_setting('checkout_reminder_mail_template');

        if (empty($emailTemplateData)) {
            Log::warning('Checkout reminder email template not found, using default template');
            $emailTemplateData = 'Dear {customer_name},

This is a friendly reminder that your reservation is checking out today.

Reservation Details:
- Reservation ID: {reservation_id}
- Property: {property_name}
- Check-in Date: {check_in_date}
- Check-out Date: {check_out_date}
- Number of Guests: {number_of_guests}
- Total Amount: {currency_symbol}{total_price}

Please ensure you have completed the checkout process and returned any keys or access cards as required.

If you have any questions or need assistance, please don\'t hesitate to contact our support team.

Thank you for choosing As-home. We hope you had a wonderful stay!

Best regards,
As-home Asset Management Team';
        }

        // Replace variables in template
        $emailContent = HelperService::replaceEmailVariables($emailTemplateData, $variables);

        // Send email
        $data = [
            'email' => $customer->email,
            'title' => 'Checkout Reminder - Your Reservation Ends Today',
            'email_template' => $emailContent
        ];

        HelperService::sendMail($data);
    }
}
