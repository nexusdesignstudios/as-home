<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Property;
use App\Models\HotelRoom;
use App\Models\AvailableDatesHotelRoom;
use App\Models\Reservation;
use App\Models\PaymobPayment;
use App\Libraries\Paypal;
use Illuminate\Http\Request;
use App\Services\ReservationService;
use App\Services\ApiResponseService;
use App\Services\HelperService;
use App\Models\Usertokens;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        Log::info('UpdateRoomPrice Request:', [
            'data' => $request->all(),
            'validation_rules' => [
                'to' => 'required|date|after_or_equal:from'
            ]
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed:', $validator->errors()->toArray());
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

                // SYNC TO available_dates_hotel_rooms TABLE
                // This ensures the relational table used by search logic is kept in sync with the JSON column
                try {
                    // Remove existing dates for this room to avoid duplication/conflicts
                    AvailableDatesHotelRoom::where('hotel_room_id', $room->id)->delete();

                    // Insert new dates from the updated schedule
                    foreach ($updatedDates as $dateInfo) {
                        // Skip invalid entries if any
                        if (!isset($dateInfo['from']) || !isset($dateInfo['to'])) {
                            continue;
                        }

                        AvailableDatesHotelRoom::create([
                            'property_id' => $room->property_id,
                            'hotel_room_id' => $room->id,
                            'from_date' => $dateInfo['from'],
                            'to_date' => $dateInfo['to'],
                            'price' => $dateInfo['price'] ?? 0,
                            'type' => $dateInfo['type'] ?? 'open',
                            'nonrefundable_percentage' => $dateInfo['nonrefundable_percentage'] ?? 0,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to sync available_dates_hotel_rooms table', [
                        'room_id' => $room->id,
                        'error' => $e->getMessage()
                    ]);
                    // We continue even if sync fails, but log it. Ideally this should be in a transaction.
                }

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
            // Vacation apartment specific (optional) — used for vacation homes
            'apartment_id' => 'nullable|integer|exists:vacation_apartments,id',
            'apartment_quantity' => 'nullable|integer|min:1',
            'total_price' => 'nullable|numeric|min:0',
        ]);

        // Custom validation for dates - use timezone-aware comparison
        $checkInDate = Carbon::parse($request->check_in_date)->startOfDay();
        $checkOutDate = Carbon::parse($request->check_out_date)->startOfDay();
        $appTimezone = \App\Services\HelperService::getSettingData('timezone') ?? config('app.timezone', 'UTC');
        $today = Carbon::today($appTimezone)->startOfDay();

        // Check for past dates - compare dates only (not time) to avoid timezone issues
        if ($checkInDate->format('Y-m-d') < $today->format('Y-m-d') || 
            $checkOutDate->format('Y-m-d') < $today->format('Y-m-d')) {
            $validator->errors()->add('past_date', 'Check-in and check-out dates cannot be in the past');
        }

        if ($validator->fails()) {
            ApiResponseService::errorResponse('Validation failed', $validator->errors());
        }

        // Map the reservable type to the model class
        $modelType = $request->reservable_type === 'property'
            ? 'App\\Models\\Property'
            : 'App\\Models\\HotelRoom';

        // Prepare data array for apartment-specific availability checking
        $data = [];
        if ($request->apartment_id) {
            $data['apartment_id'] = $request->apartment_id;
            $data['apartment_quantity'] = $request->apartment_quantity ?? 1;
        }

        // Check availability with apartment data
        $isAvailable = $this->reservationService->areDatesAvailable(
            $modelType,
            $request->reservable_id,
            $request->check_in_date,
            $request->check_out_date,
            null, // excludeReservationId
            $data  // apartment data
        );

        // For vacation homes with apartments, get unit availability details
        $responseData = ['is_available' => $isAvailable];
        
        // SAFETY: Only provide detailed availability for MULTI-UNIT vacation homes
        if ($request->reservable_type === 'property' && $request->apartment_id) {
            $apartment = \App\Models\VacationApartment::find($request->apartment_id);
            if ($apartment && $apartment->property_id == $request->reservable_id) {
                $property = Property::find($request->reservable_id);
                $isVacationHome = $property && $property->getRawOriginal('property_classification') == 4;
                
                if ($isVacationHome) {
                    $totalUnits = $apartment->quantity;
                    
                    // Only provide detailed availability for multi-unit (quantity > 1)
                    if ($totalUnits > 1) {
                        // Count booked units for this apartment on these dates
                        $bookedUnits = $this->reservationService->countBookedUnitsForApartment(
                            $request->reservable_id,
                            $request->apartment_id,
                            $request->check_in_date,
                            $request->check_out_date
                        );
                        
                        $availableUnits = $totalUnits - $bookedUnits;
                        $requestedQuantity = $request->apartment_quantity ?? 1;
                        
                        $responseData['apartment_id'] = $request->apartment_id;
                        $responseData['total_units'] = $totalUnits;
                        $responseData['booked_units'] = $bookedUnits;
                        $responseData['available_units'] = $availableUnits;
                        $responseData['can_book_quantity'] = $availableUnits;
                        $responseData['is_available'] = $availableUnits >= $requestedQuantity;
                    }
                    // For single-unit (quantity = 1), responseData remains as is_available boolean only
                }
            }
        }
        // For hotels, responseData remains as is_available boolean only - UNAFFECTED

        ApiResponseService::successResponse('Availability checked successfully', $responseData);
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
            // Vacation apartment specific (optional) — used for vacation homes
            'apartment_id' => 'nullable|integer|exists:vacation_apartments,id',
            'apartment_quantity' => 'nullable|integer|min:1',
            'total_price' => 'nullable|numeric|min:0',
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

        // Custom validation for dates - use timezone-aware comparison
        $checkInDate = Carbon::parse($request->check_in_date)->startOfDay();
        $checkOutDate = Carbon::parse($request->check_out_date)->startOfDay();
        $appTimezone = \App\Services\HelperService::getSettingData('timezone') ?? config('app.timezone', 'UTC');
        $today = Carbon::today($appTimezone)->startOfDay();

        // Check for past dates - compare dates only (not time) to avoid timezone issues
        if ($checkInDate->format('Y-m-d') < $today->format('Y-m-d') || 
            $checkOutDate->format('Y-m-d') < $today->format('Y-m-d')) {
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
            // Generate a unique transaction ID for this booking batch
            $customerId = Auth::guard('sanctum')->user()->id;
            $transactionId = 'RES_' . time() . '_' . $customerId . '_' . rand(1000, 9999);

            // Handle property reservations
            if ($request->reservable_type === 'property') {
                // Get the property model
                $property = Property::find($request->reservable_id);

                if (!$property) {
                    ApiResponseService::errorResponse('Property not found', null, 404);
                }

                // Calculate total price
                $checkIn = Carbon::parse($request->check_in_date);
                $checkOut = Carbon::parse($request->check_out_date);
                $numberOfDays = $checkIn->diffInDays($checkOut);

                // If a vacation apartment is selected, price should come from the apartment (per unit * quantity)
                $totalPrice = $property->price * $numberOfDays;

                // Fix for flexible reservations showing 0 price
                // If total_price is provided in the request, use it instead of the calculated one
                // This is especially important for Hotel properties where property.price might be 0
                if ($request->has('total_price') && is_numeric($request->total_price) && $request->total_price > 0) {
                    $totalPrice = $request->total_price;
                }

                $selectedApartmentId = $request->apartment_id;
                $apartmentQuantity = $request->apartment_quantity ?? 1;

                $isVacationHome = $property->getRawOriginal('property_classification') == 4;
                
                // Prepare data array for apartment-specific availability checking
                $data = [];
                if ($isVacationHome && $selectedApartmentId) {
                    $data['apartment_id'] = $selectedApartmentId;
                    $data['apartment_quantity'] = $apartmentQuantity;
                }

                \Illuminate\Support\Facades\Log::info('Checking availability for property reservation', [
                    'property_id' => $request->reservable_id,
                    'is_vacation_home' => $isVacationHome,
                    'apartment_id' => $selectedApartmentId,
                    'check_in' => $request->check_in_date,
                    'check_out' => $request->check_out_date,
                    'data' => $data
                ]);

                // Check availability with apartment data
                $isAvailable = $this->reservationService->areDatesAvailable(
                    $modelType,
                    $request->reservable_id,
                    $request->check_in_date,
                    $request->check_out_date,
                    null, // excludeReservationId
                    $data  // apartment data
                );

                \Illuminate\Support\Facades\Log::info('Availability check result', [
                    'is_available' => $isAvailable
                ]);

                if (!$isAvailable) {
                    // For vacation homes with apartments, provide more specific error message
                    if ($isVacationHome && $selectedApartmentId) {
                        $apartment = \App\Models\VacationApartment::where('id', $selectedApartmentId)
                            ->where('property_id', $property->id)
                            ->first();
                        
                        if ($apartment) {
                            $totalUnits = $apartment->quantity;
                            $bookedUnits = $this->reservationService->countBookedUnitsForApartment(
                                $request->reservable_id,
                                $selectedApartmentId,
                                $request->check_in_date,
                                $request->check_out_date
                            );
                            $availableUnits = $totalUnits - $bookedUnits;
                            
                            if ($availableUnits == 0) {
                                ApiResponseService::errorResponse('All units are booked for the selected dates', null, 409);
                            } else {
                                ApiResponseService::errorResponse("Only {$availableUnits} unit(s) available for the selected dates. You requested {$apartmentQuantity} unit(s).", null, 409);
                            }
                        }
                    }
                    
                    ApiResponseService::errorResponse('Selected dates are not available for this property', null, 409);
                }
                if ($isVacationHome && $selectedApartmentId) {
                    $apartment = \App\Models\VacationApartment::where('id', $selectedApartmentId)
                        ->where('property_id', $property->id)
                        ->first();

                    if ($apartment) {
                        $pricePerNight = (float)$apartment->price_per_night;
                        $discount = (float)($apartment->discount_percentage ?? 0);
                        $discountedPrice = $pricePerNight * (1 - ($discount / 100));
                        $totalPrice = $discountedPrice * $numberOfDays * max(1, (int)$apartmentQuantity);
                    }
                }

                // Check if property has flexible refund policy for flexible reservation behavior
                $isFlexible = $property->refund_policy === 'flexible';

                // Apply Cancellation Policy Logic to override Flexible
                if ($isFlexible && $property->cancellation_period) {
                    $checkInDate = Carbon::parse($request->check_in_date)->startOfDay();
                    $today = Carbon::today()->startOfDay();
                    $cancellationPeriod = $property->cancellation_period;

                    if ($cancellationPeriod === 'same_day_6pm') {
                        if ($checkInDate->equalTo($today)) {
                            // Check current time (Server time)
                            if (Carbon::now()->hour >= 18) {
                                $isFlexible = false; // Force non-refundable
                            }
                        }
                    } else {
                        $days = intval($cancellationPeriod);
                        if ($days > 0) {
                            if ($checkInDate->diffInDays($today) < $days) {
                                $isFlexible = false; // Force non-refundable
                            }
                        }
                    }
                }
                
                // Get property classification
                $propertyClassification = $property->getRawOriginal('property_classification');
                $isVacationHome = $propertyClassification == 4;

                // Determine status based on property type
                if ($isVacationHome) {
                    // Vacation Homes:
                    // If instant_booking is enabled (1):
                    // - If payment is 'cash', confirm immediately.
                    // - If payment is 'online' (gateway), status is pending (awaiting payment).
                    // If instant_booking is disabled (0), status is pending (requires approval).
                    if ($property->instant_booking) {
                        $paymentMethod = $request->payment_method ?? 'online';
                        $status = ($paymentMethod === 'cash') ? 'confirmed' : 'pending';
                    } else {
                        $status = 'pending';
                    }
                } else {
                    // Other properties (Hotels, etc.):
                    if ($isFlexible) {
                        // Flexible policy (Cash payment)
                        // Respect instant_booking setting
                        $status = $property->instant_booking ? 'confirmed' : 'pending';
                    } else {
                        // Non-refundable (Online payment)
                        $status = 'pending';
                    }
                }
                
                // Create reservation data with conditional behavior based on refund policy
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
                    'status' => $status, // Use the determined status
                    'payment_status' => $isFlexible ? 'unpaid' : 'unpaid', // Keep unpaid for flexible until manual update
                    'payment_method' => $isFlexible ? 'cash' : ($request->payment_method ?? 'online'), // Cash only for flexible reservations
                    'refund_policy' => $isFlexible ? 'flexible' : 'non-refundable', // Store the refund policy
                    'transaction_id' => $transactionId,
                ];

                // SAFETY: Only populate apartment_id and apartment_quantity for MULTI-UNIT vacation homes
                if ($isVacationHome && $selectedApartmentId) {
                    $apartment = \App\Models\VacationApartment::find($selectedApartmentId);
                    
                    // Only store in database columns if quantity > 1 (multi-unit)
                    if ($apartment && $apartment->quantity > 1) {
                        $reservationData['apartment_id'] = $selectedApartmentId;
                        $reservationData['apartment_quantity'] = $apartmentQuantity;
                    }
                    
                    // Always store in special_requests for backward compatibility
                    $reservationData['special_requests'] = trim(($request->special_requests ?? '') . ' ' .
                        'Apartment ID: ' . $selectedApartmentId . ', Quantity: ' . max(1, (int)$apartmentQuantity));
                }
                // For hotels and single-unit vacation homes, apartment_id and apartment_quantity remain NULL
                // This ensures they're completely unaffected

                // Create the reservation without sending emails (checkout without payment)
                $reservation = $this->reservationService->createReservation($reservationData, true);
                
                // For confirmed reservations (Flexible or Instant Booking Cash), update available dates immediately
                if ($status === 'confirmed') {
                    try {
                        $this->reservationService->updateAvailableDates(
                            $reservation->reservable_type,
                            $reservation->reservable_id,
                            $reservation->check_in_date,
                            $reservation->check_out_date,
                            $reservation->id
                        );
                        
                        Log::info('Available dates updated for confirmed reservation', [
                            'reservation_id' => $reservation->id,
                            'property_id' => $reservation->reservable_id,
                            'status' => $status
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to update available dates for confirmed reservation', [
                            'error' => $e->getMessage(),
                            'reservation_id' => $reservation->id,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
                
                // Send appropriate email based on property classification
                $propertyClassification = $property->getRawOriginal('property_classification');

                if (app()->runningInConsole()) {
                    echo "\n[Debug] Classification: $propertyClassification, Status: $status, Instant: {$property->instant_booking}\n";
                }

                if ($propertyClassification == 4) {
                    if ($status === 'confirmed') {
                        // Vacation home with Instant Booking (Cash) - send confirmation email
                        $this->reservationService->sendFlexibleHotelBookingConfirmationEmail($reservation);
                        
                        // Notify owner about the new confirmed booking
                        $this->sendNewBookingNotificationToOwner($reservation);
                    } elseif (!$property->instant_booking) {
                        // Vacation home without Instant Booking - send pending approval email to customer
                        // ONLY if instant booking is disabled (requires approval)
                        $this->reservationService->sendVacationHomePendingApprovalEmail($reservation);
                        
                        // Notify property owner about the booking request
                        try {
                            $this->sendVacationHomeBookingRequestToOwner($reservation);
                            Log::info('Vacation home booking request notification sent to property owner', [
                                'reservation_id' => $reservation->id,
                                'property_id' => $property->id,
                                'instant_booking' => $property->instant_booking
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to send vacation home booking request notification to owner: ' . $e->getMessage(), [
                                'reservation_id' => $reservation->id,
                                'property_id' => $property->id,
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    } else {
                        // Instant booking enabled but pending (e.g. awaiting payment)
                        // Notify owner about the new booking attempt
                        $this->sendNewBookingNotificationToOwner($reservation);
                    }
                } elseif ($propertyClassification == 5) {
                    // Hotel booking
                    if ($status === 'confirmed') {
                        // Confirmed (Instant Booking ON + Flexible) -> Send confirmation email
                        $this->reservationService->sendFlexibleHotelBookingConfirmationEmail($reservation);
                    } elseif ($isFlexible) {
                        // Flexible but Pending (Instant Booking OFF) -> Send pending approval email
                        $this->reservationService->sendVacationHomePendingApprovalEmail($reservation);
                    }
                    
                    // Send notification to property owner
                    $this->sendNewBookingNotificationToOwner($reservation);
                } else {
                    if (app()->runningInConsole()) { echo "\n[Debug] Entering generic fallback for Classification $propertyClassification\n"; }
                    // Generic fallback for other classifications (1, 2, 3, etc.)
                    // Send notification to property owner
                    try {
                        $this->sendNewBookingNotificationToOwner($reservation);
                    } catch (\Exception $e) {
                         if (app()->runningInConsole()) { echo "\n[Debug] Exception calling notification: " . $e->getMessage() . "\n"; }
                    }
                }

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

                    // Validate Guest Limits
                    $guests = $request->number_of_guests ?? 1;
                    if ($room->min_guests && $guests < $room->min_guests) {
                        ApiResponseService::errorResponse("Room {$roomId} requires minimum {$room->min_guests} guests", null, 400);
                    }
                    if ($room->max_guests && $guests > $room->max_guests) {
                        ApiResponseService::errorResponse("Room {$roomId} allows maximum {$room->max_guests} guests", null, 400);
                    }
                }

                // Get the property to check refund policy for hotel rooms
                $property = Property::find($request->property_id);
                if (!$property) {
                    ApiResponseService::errorResponse('Property not found for hotel rooms');
                }
                
                // Determine booking type (Flexible vs Non-refundable)
                $bookingType = $request->booking_type ?? null;
                if ($bookingType === 'flexible') {
                    $isFlexible = true;
                } elseif ($bookingType === 'non_refundable') {
                    $isFlexible = false;
                } else {
                    $isFlexible = $property->refund_policy === 'flexible';
                }
                
                Log::info('Hotel Booking Request - Policy Check', [
                    'property_id' => $property->id,
                    'booking_type_request' => $bookingType,
                    'is_flexible_initial' => $isFlexible,
                    'cancellation_period' => $property->cancellation_period,
                    'check_in' => $request->check_in_date,
                    'check_out' => $request->check_out_date,
                    'now' => Carbon::now()->toDateTimeString()
                ]);

                // Validate Cancellation Policy for Flexible Bookings
                if ($isFlexible && $property->cancellation_period) {
                    $checkInDate = Carbon::parse($request->check_in_date)->startOfDay();
                    $today = Carbon::today()->startOfDay();
                    $cancellationPeriod = $property->cancellation_period;

                    if ($cancellationPeriod === 'same_day_6pm') {
                        if ($checkInDate->equalTo($today)) {
                            // Check current time (Server time)
                            if (Carbon::now()->hour >= 18) {
                                Log::info('Flexible booking blocked by Same Day 6PM rule');
                                return ApiResponseService::errorResponse("Flexible booking is not allowed after 6 PM for same-day check-in.");
                            }
                        }
                    } else {
                        $days = intval($cancellationPeriod);
                        if ($days > 0) {
                            $diffDays = $checkInDate->diffInDays($today);
                            Log::info('Checking N-Day Cancellation Rule', [
                                'days_configured' => $days,
                                'diff_days' => $diffDays
                            ]);
                            if ($diffDays < $days) {
                                Log::info('Flexible booking blocked by N-Day rule');
                                return ApiResponseService::errorResponse("Flexible booking is not allowed within the cancellation period ({$days} days). Please choose Non-Refundable.");
                            }
                        }
                    }
                }
                
                // All validations passed, create reservations for each room
                foreach ($roomObjects as $roomObject) {
                    $roomId = $roomObject['id'];
                    
                    // Check individual room refund policy (can override property policy if booking_type not explicit)
                    $room = HotelRoom::find($roomId);
                    
                    // Calculate dynamic price based on guests and dates
                    $calculatedRoomAmount = 0;
                    $guests = $request->number_of_guests ?? 1;
                    $currentLoopDate = Carbon::parse($request->check_in_date);
                    $loopEndDate = Carbon::parse($request->check_out_date);
                    // Access available_dates via the model accessor which handles DB/JSON merging
                    $roomAvailableDates = $room->available_dates ?? [];

                    while ($currentLoopDate->lt($loopEndDate)) {
                        $dateStr = $currentLoopDate->format('Y-m-d');
                        $dailyBasePrice = $room->price_per_night; // Default fallback

                        // Check if this date falls into any specific price range
                        if (is_array($roomAvailableDates)) {
                            foreach ($roomAvailableDates as $range) {
                                if (isset($range['from'], $range['to']) && 
                                    $dateStr >= $range['from'] && 
                                    $dateStr <= $range['to']) {
                                    if (isset($range['price'])) {
                                        $dailyBasePrice = (float)$range['price'];
                                    }
                                    break; // Found the range for this date
                                }
                            }
                        }

                        // Apply guest pricing rules to the daily base price
                        $dailyFinalPrice = $room->calculatePrice($guests, $dailyBasePrice);
                        $calculatedRoomAmount += $dailyFinalPrice;

                        $currentLoopDate->addDay();
                    }
                    
                    // Use the calculated amount instead of client-provided amount
                    $roomAmount = $calculatedRoomAmount;
                    $totalAmount += $roomAmount;

                    $roomIsFlexible = $isFlexible;
                    
                    // Only apply room-specific policy if booking_type was NOT explicitly provided
                    if (!$bookingType && $room && $room->refund_policy) {
                        $roomIsFlexible = $room->refund_policy === 'flexible';
                    }

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
                        'status' => $request->status ?? (($roomIsFlexible && $property->instant_booking) ? 'confirmed' : 'pending'), // Use request status if provided, otherwise respect instant booking setting
                        'payment_status' => $request->payment_status ?? ($roomIsFlexible ? 'unpaid' : 'unpaid'), // Use request payment status if provided
                        'payment_method' => $request->payment_method ?? ($roomIsFlexible ? 'cash' : ($request->payment_method ?? 'online')), // Use request payment method if provided
                        'refund_policy' => $roomIsFlexible ? 'flexible' : 'non-refundable', // Store the refund policy
                        'booking_type' => $roomIsFlexible ? 'flexible_booking' : 'reservation',
                        'is_flexible_booking' => $roomIsFlexible,
                        'transaction_id' => $transactionId,
                    ];

                    $reservation = $this->reservationService->createReservation($reservationData, true);
                    $reservations[] = $reservation;
                    
                    // For confirmed reservations, update available dates immediately
                    if ($reservation->status === 'confirmed') {
                        try {
                            $this->reservationService->updateAvailableDates(
                                $reservation->reservable_type,
                                $reservation->reservable_id,
                                $reservation->check_in_date,
                                $reservation->check_out_date,
                                $reservation->id
                            );
                            
                            Log::info('Available dates updated for confirmed reservation', [
                                'reservation_id' => $reservation->id,
                                'room_id' => $reservation->reservable_id,
                                'check_in' => $reservation->check_in_date,
                                'check_out' => $reservation->check_out_date
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to update available dates for confirmed reservation', [
                                'error' => $e->getMessage(),
                                'reservation_id' => $reservation->id,
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }
                }

                // Send emails after creating all reservations
                if (!empty($reservations)) {
                    $confirmedReservations = [];
                    $pendingReservations = [];

                    foreach ($reservations as $res) {
                        if ($res->status === 'confirmed') {
                            $confirmedReservations[] = $res;
                        } else {
                            $pendingReservations[] = $res;
                        }
                    }

                    // Send aggregated email for confirmed reservations
                    if (!empty($confirmedReservations)) {
                        $this->reservationService->sendAggregatedReservationConfirmationEmail($confirmedReservations);

                        // Send notifications for each confirmed reservation
                        foreach ($confirmedReservations as $res) {
                            $this->sendNewBookingNotificationToOwner($res);
                        }
                    }

                    // Handle pending reservations
                    if (!empty($pendingReservations)) {
                        $flexiblePending = [];
                        $otherPending = [];

                        foreach ($pendingReservations as $res) {
                            if ($res->is_flexible_booking || $res->refund_policy === 'flexible') {
                                $flexiblePending[] = $res;
                            } else {
                                $otherPending[] = $res;
                            }
                        }

                        // Flexible Pending (Instant Booking OFF) -> Send Pending Approval Email
                        if (!empty($flexiblePending)) {
                            // Check if property is a hotel (class 5)
                            $isHotel = false;
                            if (!empty($flexiblePending[0])) {
                                if ($flexiblePending[0]->reservable_type === 'App\Models\HotelRoom' || $flexiblePending[0]->reservable_type === 'hotel_room') {
                                    $isHotel = true;
                                } elseif ($flexiblePending[0]->reservable_type === 'App\Models\Property' || $flexiblePending[0]->reservable_type === 'property') {
                                    $property = \App\Models\Property::find($flexiblePending[0]->property_id);
                                    if ($property && $property->getRawOriginal('property_classification') == 5) {
                                        $isHotel = true;
                                    }
                                }
                            }

                            if ($isHotel) {
                                // For Hotels, we might want an aggregated pending email, but individual works too
                                foreach ($flexiblePending as $res) {
                                    $this->reservationService->sendVacationHomePendingApprovalEmail($res);
                                    $this->sendNewBookingNotificationToOwner($res);
                                }
                            } else {
                                // Vacation Homes logic (already handled, but good to be explicit)
                                foreach ($flexiblePending as $res) {
                                    $this->reservationService->sendVacationHomePendingApprovalEmail($res);
                                    $this->sendNewBookingNotificationToOwner($res);
                                }
                            }
                        }

                        // Non-Flexible Pending (Payment Required) -> Maintain existing behavior
                        if (!empty($otherPending)) {
                            if (count($otherPending) > 1) {
                                $this->reservationService->sendAggregatedReservationConfirmationEmail($otherPending);
                                foreach ($otherPending as $res) {
                                    $this->sendNewBookingNotificationToOwner($res);
                                }
                            } else {
                                foreach ($otherPending as $res) {
                                    $this->reservationService->sendReservationApprovalEmail($res);
                                    $this->sendNewBookingNotificationToOwner($res);
                                }
                            }
                        }
                    }
                }

                ApiResponseService::successResponse('Multiple room reservations created successfully', [
                    'reservations' => $reservations,
                    'total_amount' => $totalAmount,
                    'rooms_count' => count($reservations)
                ]);
            }
        } catch (\Exception $e) {
            // Handle reservation conflicts specifically
            if (strpos($e->getMessage(), 'Room is already booked') !== false) {
                return ApiResponseService::errorResponse(
                    'This room is already booked for the selected dates. Please choose different dates or another room.',
                    null,
                    409 // Conflict status code
                );
            }
            
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

            $query = Reservation::where('customer_id', $customerId)
                ->with(['reservable', 'property']);

            if ($status && !empty(array_filter($status))) {
                $query->whereIn('status', array_filter($status));
            }

            $reservations = $query->orderBy('created_at', 'desc')->get();

            // Format reservations for frontend
            $formattedReservations = $reservations->map(function ($reservation) {
                // For vacation home reservations, load the apartment data
                if ($reservation->reservable_type === 'App\Models\Property' && 
                    $reservation->apartment_id) {
                    // Load the vacation apartment if it exists
                    $apartment = \App\Models\VacationApartment::find($reservation->apartment_id);
                    if ($apartment) {
                        $reservation->apartment = $apartment;
                    }
                }
                
                // Ensure property_classification is accessible
                if ($reservation->reservable && $reservation->reservable instanceof \App\Models\Property) {
                    // Add property_classification directly to reservation for easier access
                    $reservation->property_classification = $reservation->reservable->property_classification;
                }
                
                return $reservation;
            });

            return ApiResponseService::successResponse('Reservations retrieved successfully', [
                'reservations' => $formattedReservations
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
            
            // Send cancellation email to the property owner
            $this->reservationService->sendReservationCancellationEmailToOwner($reservation);

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
                $q->where(function($q2) {
                    $q2->where('reservable_type', 'App\\Models\\HotelRoom')
                       ->orWhere('reservable_type', 'hotel_room');
                })->whereHas('reservable', function ($q) use ($propertyId) {
                    $q->where('property_id', $propertyId);
                });
            });
        }

        if ($roomId) {
            $query->where(function ($q) use ($roomId) {
                $q->where(function($q2) {
                    $q2->where('reservable_type', 'App\\Models\\HotelRoom')
                       ->orWhere('reservable_type', 'hotel_room');
                })->where('reservable_id', $roomId);
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

                // Send cancellation email to the property owner
                $this->reservationService->sendReservationCancellationEmailToOwner($reservation);
            } elseif ($newStatus === 'approved') {
                // Handle approved status - send approval email
                $reservation->status = $newStatus;

                if ($request->has('payment_status')) {
                    $reservation->payment_status = $request->payment_status;
                }

                // If this is a flexible hotel reservation, approving it also confirms it (blocks dates)
                if ($reservation->is_flexible_booking && ($reservation->reservable_type === 'App\Models\HotelRoom' || $reservation->reservable_type === 'hotel_room')) {
                    $this->reservationService->handleReservationConfirmation($reservation, 'unpaid');
                    
                    ApiResponseService::successResponse('Flexible reservation approved and confirmed successfully. Available dates updated and approval email sent.', [
                        'reservation' => $reservation->fresh()
                    ]);
                    return;
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
        // TEMP DEBUG: Log incoming request data
        Log::info('createReservationWithPayment called', [
            'request_data' => $request->all(),
            'reservable_type' => $request->reservable_type,
            'reservable_id' => $request->reservable_id,
            'property_id' => $request->property_id,
            'dates' => [
                'check_in_date' => $request->check_in_date,
                'check_out_date' => $request->check_out_date
            ]
        ]);

        $validator = Validator::make($request->all(), [
            'reservable_type' => 'required|in:property,hotel_room',
            'review_url' => 'nullable|url',
            'property_id' => 'required|integer|exists:propertys,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_guests' => 'integer|min:1',
            'special_requests' => 'nullable|string',
            // Vacation apartment specific (optional) — used for vacation homes
            'apartment_id' => 'nullable|integer|exists:vacation_apartments,id',
            'apartment_quantity' => 'nullable|integer|min:1',
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

        // Custom validation for dates - use timezone-aware comparison
        $checkInDate = Carbon::parse($request->check_in_date)->startOfDay();
        $checkOutDate = Carbon::parse($request->check_out_date)->startOfDay();
        $appTimezone = \App\Services\HelperService::getSettingData('timezone') ?? config('app.timezone', 'UTC');
        $today = Carbon::today($appTimezone)->startOfDay();

        // Check for past dates - compare dates only (not time) to avoid timezone issues
        if ($checkInDate->format('Y-m-d') < $today->format('Y-m-d') || 
            $checkOutDate->format('Y-m-d') < $today->format('Y-m-d')) {
            $validator->errors()->add('past_date', 'Check-in and check-out dates cannot be in the past');
        }

        if ($validator->fails()) {
            Log::error('Validation failed in createReservationWithPayment', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            return ApiResponseService::errorResponse('Validation failed', $validator->errors(), 400);
        }

        // Map the reservable type to the model class
        $modelType = $request->reservable_type === 'property'
            ? 'App\\Models\\Property'
            : 'App\\Models\\HotelRoom';

        try {
            $customerId = Auth::guard('sanctum')->user()->id;
            $paymentMethod = $request->payment_method ?? 'paymob';

            // Generate a unique transaction ID that's compatible with Paymob
            // Paymob expects merchant_order_id to be a string, so we'll use a timestamp-based ID
            $transactionId = 'RES_' . time() . '_' . $customerId . '_' . rand(1000, 9999);
            
            if (empty($transactionId)) {
                Log::error('Failed to generate transaction ID for reservation', ['customer_id' => $customerId]);
                return ApiResponseService::errorResponse('System error: Failed to generate transaction ID', null, 500);
            }
            
            Log::info('Generated Transaction ID for new reservation', ['transaction_id' => $transactionId, 'customer_id' => $customerId]);

            // Handle property reservations
            if ($request->reservable_type === 'property') {
                // Get the property model
                $property = Property::find($request->reservable_id);

                if (!$property) {
                    return ApiResponseService::errorResponse('Property not found', null, 404);
                }

                // Prepare data array for apartment-specific availability checking
                $selectedApartmentId = $request->apartment_id;
                $apartmentQuantity = $request->apartment_quantity ?? 1;
                $isVacationHome = $property->getRawOriginal('property_classification') == 4;
                
                $data = [];
                if ($isVacationHome && $selectedApartmentId) {
                    $data['apartment_id'] = $selectedApartmentId;
                    $data['apartment_quantity'] = $apartmentQuantity;
                }

                // Check availability with apartment data
                $isAvailable = $this->reservationService->areDatesAvailable(
                    $modelType,
                    $request->reservable_id,
                    $request->check_in_date,
                    $request->check_out_date,
                    null, // excludeReservationId
                    $data  // apartment data
                );

                if (!$isAvailable) {
                    // For vacation homes with apartments, provide more specific error message
                    if ($isVacationHome && $selectedApartmentId) {
                        $apartment = \App\Models\VacationApartment::where('id', $selectedApartmentId)
                            ->where('property_id', $property->id)
                            ->first();
                        
                        if ($apartment) {
                            $totalUnits = $apartment->quantity;
                            $bookedUnits = $this->reservationService->countBookedUnitsForApartment(
                                $request->reservable_id,
                                $selectedApartmentId,
                                $request->check_in_date,
                                $request->check_out_date
                            );
                            $availableUnits = $totalUnits - $bookedUnits;
                            
                            if ($availableUnits == 0) {
                                ApiResponseService::errorResponse('All units are booked for the selected dates', null, 409);
                            } else {
                                ApiResponseService::errorResponse("Only {$availableUnits} unit(s) available for the selected dates. You requested {$apartmentQuantity} unit(s).", null, 409);
                            }
                        }
                    }
                    
                    ApiResponseService::errorResponse('Selected dates are not available for this property', null, 409);
                }

                // Calculate discount
                try {
                    $discountInfo = $this->calculateCustomerDiscount(
                        $customerId,
                        $modelType,
                        $request->payment['amount']
                    );
                } catch (\Exception $e) {
                    Log::error('Error calculating customer discount for property reservation', [
                        'customer_id' => $customerId,
                        'property_id' => $request->property_id,
                        'amount' => $request->payment['amount'],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return ApiResponseService::errorResponse('Failed to calculate discount. Please try again.', null, 400);
                }

                // Validate discount info
                if (!$discountInfo || !is_array($discountInfo)) {
                    Log::error('Invalid discount calculation result - discountInfo is null or not an array for property reservation', [
                        'discount_info' => $discountInfo,
                        'payment_amount' => $request->payment['amount'],
                        'customer_id' => $customerId,
                        'property_id' => $request->property_id
                    ]);
                    return ApiResponseService::errorResponse('Failed to calculate discount. Please try again.', null, 400);
                }

                if (!isset($discountInfo['final_amount']) || $discountInfo['final_amount'] <= 0) {
                    Log::error('Invalid discount calculation result for property reservation', [
                        'discount_info' => $discountInfo,
                        'payment_amount' => $request->payment['amount'],
                        'customer_id' => $customerId,
                        'property_id' => $request->property_id
                    ]);
                    return ApiResponseService::errorResponse('Invalid payment amount after discount calculation', null, 400);
                }

                // Use database transaction
                $reservation = null;
                $payment = null;

                // Prepare special_requests with apartment info if applicable
                $specialRequests = $request->special_requests ?? '';
                $apartmentIdForReservation = null;
                $apartmentQuantityForReservation = null;
                
                // SAFETY: Only populate apartment_id and apartment_quantity for MULTI-UNIT vacation homes
                if ($isVacationHome && $selectedApartmentId) {
                    $apartment = \App\Models\VacationApartment::find($selectedApartmentId);
                    
                    // Only store in database columns if quantity > 1 (multi-unit)
                    if ($apartment && $apartment->quantity > 1) {
                        $apartmentIdForReservation = $selectedApartmentId;
                        $apartmentQuantityForReservation = $apartmentQuantity;
                    }
                    
                    // Always store in special_requests for backward compatibility
                    $specialRequests = trim($specialRequests . ' ' .
                        'Apartment ID: ' . $selectedApartmentId . ', Quantity: ' . max(1, (int)$apartmentQuantity));
                }
                // For hotels and single-unit vacation homes, apartment_id and apartment_quantity remain NULL

                DB::transaction(function () use ($request, $modelType, $discountInfo, $transactionId, &$reservation, &$payment, $specialRequests, $apartmentIdForReservation, $apartmentQuantityForReservation, $paymentMethod) {
                    // Create temporary reservation to hold the details
                    $reservationData = [
                        'customer_id' => Auth::guard('sanctum')->user()->id,
                        'reservable_id' => $request->reservable_id,
                        'reservable_type' => $modelType,
                        'property_id' => $request->property_id,
                        'check_in_date' => $request->check_in_date,
                        'check_out_date' => $request->check_out_date,
                        'number_of_guests' => $request->number_of_guests ?? 1,
                        'total_price' => $discountInfo['final_amount'],
                        'original_amount' => $discountInfo['original_amount'] ?? $discountInfo['final_amount'],
                        'discount_percentage' => $discountInfo['discount_percentage'] ?? 0,
                        'discount_amount' => $discountInfo['discount_amount'] ?? 0,
                        'special_requests' => $specialRequests,
                        'status' => 'pending',
                        'payment_status' => 'unpaid',
                        'payment_method' => $paymentMethod,
                        'transaction_id' => $transactionId,
                        'review_url' => $request->review_url,
                    ];
                    
                    // Only add apartment fields for multi-unit vacation homes
                    if ($apartmentIdForReservation !== null) {
                        $reservationData['apartment_id'] = $apartmentIdForReservation;
                        $reservationData['apartment_quantity'] = $apartmentQuantityForReservation;
                    }
                    
                    $reservation = Reservation::create($reservationData);

                    // Create payment record
                    $payment = PaymobPayment::create([
                        'customer_id' => Auth::guard('sanctum')->user()->id,
                        'transaction_id' => $transactionId,
                        'amount' => $discountInfo['final_amount'],
                        'currency' => config('paymob.currency', 'EGP'),
                        'status' => 'pending',
                        'payment_method' => $paymentMethod,
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

                foreach ($roomObjects as &$roomObject) {
                    $roomId = $roomObject['id'];
                    $roomAmount = $roomObject['amount'];

                    $room = HotelRoom::find($roomId);

                    if (!$room) {
                        return ApiResponseService::errorResponse("Hotel room with ID {$roomId} not found", null, 404);
                    }

                    if ($room->property_id != $request->property_id) {
                        return ApiResponseService::errorResponse("Room {$roomId} does not belong to the specified property", null, 400);
                    }

                    // Check room status - allow booking of active rooms (status = true) and pending rooms
                    // Only block inactive rooms (status = false)
                    // Note: This allows booking of rooms that are pending approval
                    // if ($room->status === false) {
                    //     return ApiResponseService::errorResponse("Room {$roomId} is currently inactive and cannot be booked", 400);
                    // }

                    // Check availability - only confirmed reservations block availability
                    // Pending/unpaid reservations do NOT block availability
                    Log::info('Checking availability for room', [
                        'roomId' => $roomId,
                        'checkInDate' => $request->check_in_date,
                        'checkOutDate' => $request->check_out_date
                    ]);
                    
                    $isAvailable = $this->reservationService->areDatesAvailable(
                        $modelType,
                        $roomId,
                        $request->check_in_date,
                        $request->check_out_date
                    );

                    Log::info('Room availability check result', [
                        'roomId' => $roomId,
                        'isAvailable' => $isAvailable
                    ]);

                    if (!$isAvailable) {
                        // NEW CONDITION: Only block if room type availability equals zero
                        // Check if there are any available rooms of the same type
                        $roomTypeId = $room->room_type_id ?? null;
                        $hasAvailableRooms = false;
                        
                        Log::info('Room not available, searching for alternatives', [
                            'roomId' => $roomId,
                            'roomTypeId' => $roomTypeId
                        ]);
                        
                        if ($roomTypeId) {
                            // Get all rooms of the same type in the same property
                            $sameTypeRooms = HotelRoom::where('room_type_id', $roomTypeId)
                                ->where('property_id', $request->property_id)
                                ->where('status', 1) // Only active rooms
                                ->where('id', '!=', $roomId) // Exclude the current room
                                ->get();
                            
                            Log::info('Found rooms of same type', [
                                'roomTypeId' => $roomTypeId,
                                'count' => $sameTypeRooms->count(),
                                'rooms' => $sameTypeRooms->pluck('id')->toArray()
                            ]);
                            
                            // Check if any room of this type is available (no confirmed reservations)
                            foreach ($sameTypeRooms as $sameTypeRoom) {
                                Log::info('Checking alternative room', [
                                    'alternativeRoomId' => $sameTypeRoom->id
                                ]);
                                
                                $roomAvailable = $this->reservationService->areDatesAvailable(
                                    $modelType,
                                    $sameTypeRoom->id,
                                    $request->check_in_date,
                                    $request->check_out_date
                                );
                                
                                Log::info('Alternative room availability', [
                                    'alternativeRoomId' => $sameTypeRoom->id,
                                    'isAvailable' => $roomAvailable
                                ]);
                                
                                if ($roomAvailable) {
                                    $hasAvailableRooms = true;
                                    // Use this available room instead
                                    $roomObject['id'] = $sameTypeRoom->id;
                                    $room = $sameTypeRoom;
                                    $roomId = $sameTypeRoom->id;
                                    $roomAmount = $roomObject['amount'];
                                    
                                    Log::info('Found available alternative room', [
                                        'originalRoomId' => $roomObject['id'],
                                        'newRoomId' => $roomId,
                                        'newAmount' => $roomAmount
                                    ]);
                                    
                                    break;
                                }
                            }
                        }
                        
                        // Only block if no rooms of this type are available (room type availability = 0)
                        if (!$hasAvailableRooms) {
                            // All rooms of this type are fully booked - return error
                            Log::error('All rooms of this type are fully booked', [
                                'requestedRoomId' => $roomId,
                                'roomTypeId' => $roomTypeId,
                                'checkInDate' => $request->check_in_date,
                                'checkOutDate' => $request->check_out_date
                            ]);
                            
                            return ApiResponseService::errorResponse(
                                "No rooms available for the selected dates. All rooms of this type are fully booked.",
                                null,
                                409
                            );
                        }
                        // If hasAvailableRooms is true, we've already updated the room to use an available one
                        // Continue with the booking process
                    }
                    
                    // Validate Guest Limits
                    $guests = $request->number_of_guests ?? 1;
                    if ($room->min_guests && $guests < $room->min_guests) {
                        return ApiResponseService::errorResponse("Room {$roomId} requires minimum {$room->min_guests} guests", null, 400);
                    }
                    if ($room->max_guests && $guests > $room->max_guests) {
                        return ApiResponseService::errorResponse("Room {$roomId} allows maximum {$room->max_guests} guests", null, 400);
                    }
                }

                unset($roomObject);

                // Calculate discount on the total payment amount
                $discountInfo = $this->calculateCustomerDiscount(
                    $customerId,
                    $modelType,
                    $request->payment['amount']
                );

                // Determine booking type (Flexible vs Non-refundable)
                $property = Property::find($request->property_id);
                $bookingType = $request->booking_type ?? null;
                $isFlexible = false;
                
                if ($bookingType === 'flexible') {
                    $isFlexible = true;
                } elseif ($bookingType === 'non_refundable') {
                    $isFlexible = false;
                } else {
                    $isFlexible = $property && $property->refund_policy === 'flexible';
                }

                // Validate discount info
                if (!isset($discountInfo['final_amount']) || $discountInfo['final_amount'] <= 0) {
                    Log::error('Invalid discount calculation result', [
                        'discount_info' => $discountInfo,
                        'payment_amount' => $request->payment['amount'],
                        'customer_id' => $customerId
                    ]);
                    return ApiResponseService::errorResponse('Invalid payment amount after discount calculation', null, 400);
                }
                
                // Create/update tier discount record if discount is available
                if (isset($discountInfo['has_available_discount']) && $discountInfo['has_available_discount'] && isset($discountInfo['tier_milestone']) && $discountInfo['tier_milestone']) {
                    try {
                        // Check if table exists before trying to create/update
                        if (class_exists('\App\Models\CustomerTierDiscount') && Schema::hasTable('customer_tier_discounts')) {
                            \App\Models\CustomerTierDiscount::updateOrCreate(
                                [
                                    'customer_id' => $customerId,
                                    'reservable_type' => $modelType,
                                    'tier_milestone' => $discountInfo['tier_milestone'],
                                ],
                                [
                                    'used' => false, // Will be marked as used when reservation is confirmed
                                ]
                            );
                            
                            Log::info('Tier discount record created/updated', [
                                'customer_id' => $customerId,
                                'reservable_type' => $modelType,
                                'tier_milestone' => $discountInfo['tier_milestone'],
                                'discount_percentage' => $discountInfo['discount_percentage']
                            ]);
                        } else {
                            Log::warning('CustomerTierDiscount table does not exist, skipping discount tracking', [
                                'customer_id' => $customerId
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to create/update tier discount record', [
                            'customer_id' => $customerId,
                            'error' => $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        // Don't fail the payment if discount tracking fails
                    }
                }

                // Use database transaction
                $payment = null;
                $mainReservation = null; // This will be the first reservation, linked to the payment
                $reservations = []; // Initialize reservations array

                DB::transaction(function () use ($request, $modelType, $roomObjects, $discountInfo, $transactionId, &$reservations, &$payment, &$mainReservation, $paymentMethod, $isFlexible, $bookingType) {
                    // Create reservations for each room
                    foreach ($roomObjects as $index => $roomObject) {
                        $roomId = $roomObject['id'];
                        $roomAmount = $roomObject['amount'];

                        // Determine refund policy for this room
                        $room = HotelRoom::find($roomId);
                        $roomIsFlexible = $isFlexible;
                        
                        // Only apply room-specific policy if booking_type was NOT explicitly provided
                        if (!$bookingType && $room && $room->refund_policy) {
                            $roomIsFlexible = $room->refund_policy === 'flexible';
                        }
                        $refundPolicy = $roomIsFlexible ? 'flexible' : 'non-refundable';

                        // For the first room, create a reservation that will be linked to the payment
                        if ($index === 0) {
                            // Calculate discount for this room (proportional to total discount)
                            // Prevent division by zero if discount_percentage is 100
                            $discountDivisor = 1 - ($discountInfo['discount_percentage'] / 100);
                            if ($discountDivisor <= 0) {
                                $roomOriginalAmount = $roomAmount;
                            } else {
                                $roomOriginalAmount = $roomAmount / $discountDivisor;
                            }
                            $roomDiscountAmount = $roomOriginalAmount - $roomAmount;
                            
                            $mainReservation = Reservation::create([
                                'customer_id' => Auth::guard('sanctum')->user()->id,
                                'reservable_id' => $roomId,
                                'reservable_type' => $modelType,
                                'property_id' => $request->property_id,
                                'check_in_date' => $request->check_in_date,
                                'check_out_date' => $request->check_out_date,
                                'number_of_guests' => $request->number_of_guests ?? 1,
                                'total_price' => $roomAmount,
                                'original_amount' => $roomOriginalAmount ?? $roomAmount,
                                'discount_percentage' => $discountInfo['discount_percentage'] ?? 0,
                                'discount_amount' => $roomDiscountAmount ?? 0,
                                'special_requests' => $request->special_requests,
                                'status' => $request->status ?? 'pending',
                                'payment_status' => $request->payment_status ?? 'unpaid',
                                'payment_method' => $paymentMethod,
                                'transaction_id' => $transactionId,
                                'review_url' => $request->review_url,
                                'refund_policy' => $refundPolicy,
                            ]);

                            if (!$mainReservation->transaction_id) {
                                Log::error('Reservation created with empty transaction_id', [
                                    'reservation_id' => $mainReservation->id,
                                    'expected_transaction_id' => $transactionId
                                ]);
                                // Force update if needed, but this shouldn't happen if fillable is correct
                                $mainReservation->transaction_id = $transactionId;
                                $mainReservation->save();
                            }

                            $reservations[] = $mainReservation;

                            // Create payment record linked to the first reservation
                            $payment = PaymobPayment::create([
                                'customer_id' => Auth::guard('sanctum')->user()->id,
                                'transaction_id' => $transactionId,
                                'amount' => $discountInfo['final_amount'], // Use the total discounted amount
                                'currency' => config('paymob.currency', 'EGP'),
                                'status' => 'pending',
                                'payment_method' => $paymentMethod,
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
                            // Calculate discount for this room (proportional to total discount)
                            // Prevent division by zero if discount_percentage is 100
                            $discountDivisor = 1 - ($discountInfo['discount_percentage'] / 100);
                            if ($discountDivisor <= 0) {
                                $roomOriginalAmount = $roomAmount;
                            } else {
                                $roomOriginalAmount = $roomAmount / $discountDivisor;
                            }
                            $roomDiscountAmount = $roomOriginalAmount - $roomAmount;
                            
                            $reservation = Reservation::create([
                                'customer_id' => Auth::guard('sanctum')->user()->id,
                                'reservable_id' => $roomId,
                                'reservable_type' => $modelType,
                                'property_id' => $request->property_id,
                                'check_in_date' => $request->check_in_date,
                                'check_out_date' => $request->check_out_date,
                                'number_of_guests' => $request->number_of_guests ?? 1,
                                'total_price' => $roomAmount,
                                'original_amount' => $roomOriginalAmount ?? $roomAmount,
                                'discount_percentage' => $discountInfo['discount_percentage'] ?? 0,
                                'discount_amount' => $roomDiscountAmount ?? 0,
                                'special_requests' => $request->special_requests,
                                'status' => $request->status ?? 'pending',
                                'payment_status' => $request->payment_status ?? 'unpaid',
                                'payment_method' => $paymentMethod,
                                'transaction_id' => $transactionId, // Same transaction ID for all reservations
                                'review_url' => $request->review_url,
                                'refund_policy' => $refundPolicy,
                            ]);

                            $reservations[] = $reservation;
                        }
                    }
                });

                // Log payment record after transaction is committed
                if (isset($payment) && $payment) {
                    Log::info('Hotel room payment record committed to database', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $payment->transaction_id,
                        'status' => $payment->status,
                        'reservation_id' => $payment->reservation_id
                    ]);
                } else {
                    Log::error('Payment record was not created in database transaction for hotel room reservation', [
                        'transaction_id' => $transactionId,
                        'main_reservation_id' => $mainReservation->id ?? null
                    ]);
                    throw new \Exception('Failed to create payment record for hotel room reservation');
                }

                // Validate mainReservation was created
                if (!isset($mainReservation) || !$mainReservation) {
                    Log::error('mainReservation was not created in database transaction for hotel room reservation', [
                        'transaction_id' => $transactionId,
                        'payment_id' => $payment->id ?? null
                    ]);
                    throw new \Exception('Failed to create main reservation for hotel room booking');
                }

                // Set the reservation variable for the payment intent creation
                $reservation = $mainReservation;
            }

            $paymentIntent = null;
            $paymentUrl = null;

            if ($paymentMethod === 'pay_at_property' || $paymentMethod === 'cash') {
                Log::info('Skipping payment gateway for pay_at_property/cash payment method', [
                    'transaction_id' => $transactionId,
                    'payment_method' => $paymentMethod
                ]);
            } elseif ($paymentMethod === 'paypal') {
                // PayPal Logic (SDK)
                $paypalSdk = new \App\Libraries\PaypalServerSdk();
                
                $currency = system_setting('paypal_currency_code');
                if (empty($currency)) {
                     $currency = env('PAYPAL_CURRENCY', 'USD');
                }

                $amount = $discountInfo['final_amount'];
                
                // Currency Conversion (EGP to USD)
                // If the target currency is USD but the system is EGP (implied by amount), convert it.
                // We assume the amount passed in $discountInfo['final_amount'] is in the system's default currency (EGP).
                if (strtoupper($currency) === 'USD') {
                    $systemCurrency = system_setting('currency_code') ?? 'EGP';
                    if (strtoupper($systemCurrency) !== 'USD') {
                        $exchangeRate = (float)system_setting('usd_exchange_rate');
                        if (!$exchangeRate || $exchangeRate <= 0) {
                            $exchangeRate = 50.0; // Default fallback if not set
                            Log::warning('USD Exchange Rate not set in system settings, using default: ' . $exchangeRate);
                        }
                        $amount = $amount / $exchangeRate;
                        $amount = round($amount, 2); // Ensure 2 decimal places
                        Log::info("Converted payment amount: {$discountInfo['final_amount']} $systemCurrency to $amount USD (Rate: $exchangeRate)");
                    }
                }

                // Return URL points to our backend callback to capture payment
                // Using url() helper to ensure correct domain
                $returnUrl = url('/api/payments/paypal/return');
                $cancelUrl = url('/'); 

                $orderData = $paypalSdk->createOrder(
                    $amount,
                    $currency,
                    $returnUrl,
                    $cancelUrl,
                    $transactionId
                );

                if (isset($orderData['success']) && $orderData['success']) {
                    $paymentUrl = $orderData['approve_link'];
                    
                    if (isset($payment)) {
                         $payment->paymob_order_id = $orderData['id']; // Store PayPal Order ID
                         $payment->save();
                    }
                    Log::info('PayPal Order Created', ['id' => $orderData['id'], 'url' => $paymentUrl]);
                } else {
                     Log::error('PayPal Order Creation Failed', ['result' => $orderData]);
                     // Fallback or error
                     throw new \Exception('Failed to initiate PayPal payment: ' . ($orderData['message'] ?? 'Unknown error'));
                }
            } else {
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
                try {
                    $paymentIntent = $paymentService->createAndFormatPaymentIntent($discountInfo['final_amount'], $metadata);
                } catch (\Exception $paymentIntentException) {
                    Log::error('Failed to create payment intent in createReservationWithPayment', [
                        'error' => $paymentIntentException->getMessage(),
                        'trace' => $paymentIntentException->getTraceAsString(),
                        'amount' => $discountInfo['final_amount'],
                        'transaction_id' => $transactionId
                    ]);
                    throw $paymentIntentException;
                }

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
            }

            // Send flexible hotel booking approval email to customer if this is a flexible booking
            // (instant_booking = false for hotel properties)
            if (isset($reservation) && $reservation) {
                try {
                    $property = $reservation->property;
                } catch (\Exception $e) {
                    Log::warning('Failed to load property relationship for reservation', [
                        'reservation_id' => $reservation->id ?? null,
                        'error' => $e->getMessage()
                    ]);
                    $property = null;
                }
                if ($property && $property->property_classification == 5 && !$property->instant_booking && $reservation->payment_status !== 'paid' && $reservation->refund_policy === 'flexible') {
                    try {
                        // Send flexible booking approval email to customer
                        $this->reservationService->sendFlexibleHotelBookingApprovalEmail($reservation);
                        
                        // Notification to property owner will be sent below for all reservation types
                        
                        Log::info('Flexible booking approval email sent during payment checkout', [
                            'reservation_id' => $reservation->id,
                            'customer_id' => $reservation->customer_id,
                            'property_id' => $property->id,
                            'instant_booking' => $property->instant_booking,
                            'booking_type' => 'flexible'
                        ]);
                    } catch (\Exception $e) {
                        // Log email error but don't fail the transaction
                        Log::error('Failed to send flexible hotel booking email during payment checkout: ' . $e->getMessage(), [
                            'reservation_id' => $reservation->id,
                            'property_id' => $property->id,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
            }

            // Send aggregated confirmation email if reservations are confirmed and paid immediately
            // (e.g. forced from frontend for specific scenarios)
            if (!empty($reservations) && 
                (($request->status ?? '') === 'confirmed' || ($reservations[0]->status ?? '') === 'confirmed') && 
                (($request->payment_status ?? '') === 'paid' || ($reservations[0]->payment_status ?? '') === 'paid')) {
                
                try {
                    $this->reservationService->sendAggregatedReservationConfirmationEmail($reservations);
                    
                    Log::info('Aggregated reservation confirmation email sent immediately', [
                        'count' => count($reservations),
                        'total_price' => collect($reservations)->sum('total_price')
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send aggregated reservation confirmation email immediately', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Prepare response based on reservation type
            if ($request->reservable_type === 'property') {
                // Send notification for Property reservation
                if (isset($reservation)) {
                     $this->sendNewBookingNotificationToOwner($reservation);
                }

                return ApiResponseService::successResponse('Reservation and payment intent created successfully', [
                    'reservation' => $reservation,
                    'payment_intent' => $paymentIntent,
                    'payment_url' => $paymentUrl,
                    'transaction_id' => $transactionId,
                    'discount_info' => $discountInfo,
                ]);
            } else {
                // Validate that mainReservation exists before accessing its id
                if (!isset($mainReservation) || !$mainReservation) {
                    Log::error('mainReservation is null when trying to return response for hotel room reservations', [
                        'reservations_count' => isset($reservations) ? count($reservations) : 0,
                        'transaction_id' => $transactionId
                    ]);
                    throw new \Exception('Failed to create main reservation for hotel room booking');
                }
                
                // Send notifications for all created reservations (Hotel Rooms)
                if (isset($reservations) && !empty($reservations)) {
                    foreach ($reservations as $res) {
                        $this->sendNewBookingNotificationToOwner($res);
                    }
                }

                return ApiResponseService::successResponse('Multiple room reservations and payment intent created successfully', [
                    'reservations' => $reservations ?? [],
                    'main_reservation_id' => $mainReservation->id,
                    'payment_intent' => $paymentIntent,
                    'payment_url' => $paymentUrl,
                    'transaction_id' => $transactionId,
                    'discount_info' => $discountInfo,
                    'rooms_count' => isset($reservations) ? count($reservations) : 0
                ]);
            }
        } catch (\Exception $e) {
            // Enhanced error logging for debugging
            Log::error('Exception in createReservationWithPayment', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['payment']),
                'reservable_type' => $request->reservable_type,
                'reservable_id' => $request->reservable_id,
                'property_id' => $request->property_id
            ]);

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

            // Log the error for debugging
            Log::error('Failed to create reservation with payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['payment'])
            ]);

            return ApiResponseService::errorResponse('Failed to create reservation with payment: ' . $e->getMessage());
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
        // Initialize metrics variables to avoid undefined variable errors
        $totalReservationsCount = 0;
        $pendingReservationsCount = 0;
        $confirmedReservationsCount = 0;
        $cancelledReservationsCount = 0;
        $totalRevenue = 0;

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

        // Calculate metrics based on the filtered query
        $totalReservationsCount = (clone $query)->count();
        $pendingReservationsCount = (clone $query)->where('status', 'pending')->count();
        $confirmedReservationsCount = (clone $query)->whereIn('status', ['confirmed', 'approved'])->count();
        $cancelledReservationsCount = (clone $query)->whereIn('status', ['cancelled', 'rejected'])->count();
        $totalRevenue = (clone $query)->whereIn('status', ['confirmed', 'approved', 'completed'])->sum('total_price');

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
                'property.category:id,category,image',
                'reservable'
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

            // Parse apartment_id and apartment_quantity from special_requests if not in database columns
            // This handles old reservations created before the migration
            if (empty($data['apartment_id']) && !empty($reservation->special_requests)) {
                $specialRequests = $reservation->special_requests;
                if (preg_match('/Apartment ID:\s*(\d+)/i', $specialRequests, $aptMatches)) {
                    $data['apartment_id'] = (int)$aptMatches[1];
                }
                if (preg_match('/Quantity:\s*(\d+)/i', $specialRequests, $qtyMatches)) {
                    $data['apartment_quantity'] = (int)$qtyMatches[1];
                }
            }

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
                $propertyClassification = $reservation->property->getRawOriginal('property_classification') ?? $reservation->property->property_classification;
                
                $data['property_info'] = [
                    'id' => $reservation->property->id,
                    'title' => $reservation->property->title,
                    'title_image' => $reservation->property->title_image,
                    'property_classification' => $propertyClassification
                ];
                
                // Also add property_classification directly to reservation for easier frontend access
                $data['property_classification'] = $propertyClassification;
                
                // Add property_details for backward compatibility
                $data['property_details'] = [
                    'property_classification' => $propertyClassification
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
            
            // Load reservable relationship for vacation homes to include property_classification
            if ($reservation->reservable_type === 'App\\Models\\Property' && !$reservation->relationLoaded('reservable')) {
                $reservation->load('reservable');
            }
            
            // Load reservable relationship for hotel rooms if not already loaded
            if (($reservation->reservable_type === 'App\\Models\\HotelRoom' || $reservation->reservable_type === 'hotel_room') && !$reservation->relationLoaded('reservable')) {
                $reservation->load('reservable');
            }
            
            // Add reservable property_classification if available
            if ($reservation->reservable && $reservation->reservable instanceof \App\Models\Property) {
                $reservableClassification = $reservation->reservable->getRawOriginal('property_classification') ?? $reservation->reservable->property_classification;
                $data['reservable'] = [
                    'id' => $reservation->reservable->id,
                    'property_classification' => $reservableClassification
                ];
            }
            
            // Load apartment data if apartment_id exists
            if (!empty($data['apartment_id'])) {
                $apartment = \App\Models\VacationApartment::find($data['apartment_id']);
                if ($apartment) {
                    $data['apartment'] = [
                        'id' => $apartment->id,
                        'apartment_number' => $apartment->apartment_number,
                        'quantity' => $apartment->quantity
                    ];
                    $data['apartment_number'] = $apartment->apartment_number;
                }
            }

            // Add specific information based on reservation type
            if ($reservation->reservable_type === 'App\\Models\\HotelRoom' || $reservation->reservable_type === 'hotel_room') {
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

                // Status mapping for flexible reservations - fix display consistency
                // Map 'approved' to 'confirmed' for flexible hotel reservations to maintain consistency
                if ($reservation->status === 'approved' && ($reservation->reservable_type === 'App\\Models\\HotelRoom' || $reservation->reservable_type === 'hotel_room')) {
                    // Check if this is a flexible reservation (cash/offline payment)
                    $isFlexible = false;
                    $paymentMethod = $reservation->payment_method ?? 'cash';
                    if (!($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment)) {
                        $isFlexible = true;
                    }
                    
                    // Also check if booking_type indicates flexible booking (post-fix reservations)
                    if ($reservation->booking_type === 'flexible_booking') {
                        $isFlexible = true;
                    }
                    
                    // For flexible reservations, show 'confirmed' instead of 'approved' for consistency
                    if ($isFlexible) {
                        $data['status'] = 'confirmed';
                        $data['display_status'] = 'confirmed'; // Add display status for frontend
                        $data['is_flexible_reservation'] = true;
                        
                        // Log the status mapping for debugging
                        \Log::info('Flexible reservation status mapped', [
                            'reservation_id' => $reservation->id,
                            'original_status' => $reservation->status,
                            'mapped_status' => 'confirmed',
                            'payment_method' => $paymentMethod,
                            'booking_type' => $reservation->booking_type,
                            'has_payment' => !empty($reservation->payment)
                        ]);
                    }
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
                'reservations' => $formattedReservations,
                'metrics' => [
                    'total_reservations' => $totalReservationsCount,
                    'pending_reservations' => $pendingReservationsCount,
                    'confirmed_reservations' => $confirmedReservationsCount,
                    'cancelled_reservations' => $cancelledReservationsCount,
                    'total_revenue' => $totalRevenue
                ]
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
     * Get reservations for a specific property (without requiring owner)
     * This is used for properties without owners or when owner ID is not available
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPropertyReservations(Request $request)
    {
        try {
            \Log::info('getPropertyReservations called', [
                'request_params' => $request->all()
            ]);

            $validator = Validator::make($request->all(), [
                'property_id' => 'required|integer|exists:propertys,id',
                'status' => 'nullable|string',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return ApiResponseService::errorResponse('Validation failed', $validator->errors());
            }

            $propertyId = $request->property_id;
            $status = $request->status && trim($request->status) !== '' ? explode(',', $request->status) : null;
            $perPage = $request->per_page ?? 100;
            $page = $request->page ?? 1;

            \Log::info('Executing property reservations query', [
                'property_id' => $propertyId,
                'status' => $status,
                'per_page' => $perPage,
                'page' => $page
            ]);

            // Build query for reservations by property ID
            $query = Reservation::where('property_id', $propertyId);

            // Add status filter if provided
            if ($status && !empty(array_filter($status))) {
                $query->whereIn('status', array_filter($status));
            }

            // Order by creation date (newest first)
            $query->orderBy('created_at', 'desc');

            // Get paginated results
            $reservations = $query->paginate($perPage, ['*'], 'page', $page);

            \Log::info('Property reservations query executed', [
                'property_id' => $propertyId,
                'total_found' => $reservations->total(),
                'current_page' => $reservations->currentPage()
            ]);

            // Format reservations with flexible reservation detection
            $formattedReservations = $reservations->getCollection()->map(function ($reservation) {
                // Determine if this is a flexible reservation
                $isFlexible = false;
                $paymentMethod = $reservation->payment_method ?? 'cash';
                
                if (!($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment)) {
                    $isFlexible = true;
                }
                
                // Also check if booking_type indicates flexible booking
                if ($reservation->booking_type === 'flexible_booking') {
                    $isFlexible = true;
                }

                // For flexible reservations, show 'confirmed' consistently in API response
                $statusOut = $reservation->status;
                $displayStatusOut = $reservation->display_status ?? $reservation->status;
                if ($isFlexible) {
                    $statusOut = 'confirmed';
                    $displayStatusOut = 'confirmed';
                }

                return [
                    'id' => $reservation->id,
                    'property_id' => $reservation->property_id,
                    'reservable_id' => $reservation->reservable_id,
                    'reservable_type' => $reservation->reservable_type,
                    'customer_id' => $reservation->customer_id,
                    'customer_name' => $reservation->customer_name,
                    'customer_email' => $reservation->customer_email,
                    'customer_phone' => $reservation->customer_phone,
                    'check_in_date' => $reservation->check_in_date,
                    'check_out_date' => $reservation->check_out_date,
                    'number_of_guests' => $reservation->number_of_guests,
                    'total_price' => $reservation->total_price,
                    'original_amount' => $reservation->original_amount,
                    'discount_percentage' => $reservation->discount_percentage,
                    'discount_amount' => $reservation->discount_amount,
                    'payment_method' => $reservation->payment_method,
                    'payment_status' => $reservation->payment_status,
                    'status' => $statusOut,
                    'display_status' => $displayStatusOut,
                    'approval_status' => $reservation->approval_status,
                    'requires_approval' => $reservation->requires_approval,
                    'booking_type' => $reservation->booking_type,
                    'refund_policy' => $reservation->refund_policy,
                    'special_requests' => $reservation->special_requests,
                    'transaction_id' => $reservation->transaction_id,
                    'is_flexible_reservation' => $isFlexible,
                    'created_at' => $reservation->created_at,
                    'updated_at' => $reservation->updated_at,
                    'reservable_data' => $reservation->reservable_data,
                ];
            });

            // Return paginated response
            $paginatedData = new \Illuminate\Pagination\LengthAwarePaginator(
                $formattedReservations,
                $reservations->total(),
                $reservations->perPage(),
                $reservations->currentPage(),
                [
                    'path' => $request->url(),
                    'pageName' => 'page',
                ]
            );

            return ApiResponseService::successResponse('Property reservations retrieved successfully', [
                'reservations' => $paginatedData,
                'total_reservations' => $paginatedData->total(),
                'current_page' => $paginatedData->currentPage(),
                'per_page' => $paginatedData->perPage(),
                'last_page' => $paginatedData->lastPage(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getPropertyReservations', [
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

            // Load reservable if not already loaded
            if (!$reservation->relationLoaded('reservable')) {
                $reservation->load('reservable');
            }
            $reservable = $reservation->reservable;

            if ($reservable instanceof \App\Models\Property) {
                $property = $reservable;
                $propertyName = $property->title;
            } elseif ($reservable instanceof \App\Models\HotelRoom) {
                $hotelRoom = $reservable;
                if ($hotelRoom->property) {
                    $propertyName = $hotelRoom->property->title;
                }
            } elseif ($reservation->reservable_type === 'property' || $reservation->reservable_type === 'App\\Models\\Property') {
                $property = Property::find($reservation->reservable_id);
                if ($property) {
                    $propertyName = $property->title;
                }
            } elseif ($reservation->reservable_type === 'hotel_room' || $reservation->reservable_type === 'App\\Models\\HotelRoom') {
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
            // Convert customer_id to integer if it's numeric
            if (is_numeric($customer_id)) {
                $customer_id = (int) $customer_id;
            }
            
            // Validate customer exists - try by ID first, then by email if ID fails
            $customer = null;
            try {
            $customer = \App\Models\Customer::find($customer_id);
                if (!$customer && filter_var($customer_id, FILTER_VALIDATE_EMAIL)) {
                    // If customer_id is an email, try to find by email
                    $customer = \App\Models\Customer::where('email', $customer_id)->first();
                    if ($customer) {
                        $customer_id = $customer->id; // Update to use the actual ID
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error finding customer', [
                    'customer_id' => $customer_id,
                    'error' => $e->getMessage()
                ]);
            }
            
            if (!$customer) {
                Log::warning('Customer not found in getCustomerReservationCounts', [
                    'customer_id' => $customer_id
                ]);
                return ApiResponseService::errorResponse('Customer not found');
            }

            // Initialize counters BEFORE try-catch to ensure they're always in scope
            $vacationHomesCount = 0;
            $hotelRoomsCount = 0;
            $vacationHomesCompleted = 0;
            $hotelRoomsCompleted = 0;
            $vacationHomesByStatus = [];
            $hotelRoomsByStatus = [];
            $totalCount = 0;

            // SIMPLIFIED COUNTING: Get all reservations for this customer in one query
            // Then count them directly in PHP - simpler, more reliable, easier to debug
            try {
                $allReservations = Reservation::where('customer_id', $customer_id)
                    ->select('id', 'reservable_type', 'status')
                    ->get();
                
                // Count directly from the collection - simple and reliable
                foreach ($allReservations as $reservation) {
                    try {
                        $reservableType = $reservation->reservable_type ?? '';
                        $status = $reservation->status ?? '';
                        
                        // Normalize status to lowercase for comparison
                        $statusLower = strtolower($status);
                        
                        // Check if it's a vacation home (Property)
                        if ($reservableType === 'App\\Models\\Property') {
                            $vacationHomesCount++;

                            // Count by status (use original status, not lowercase, for consistency)
                            if (!isset($vacationHomesByStatus[$statusLower])) {
                                $vacationHomesByStatus[$statusLower] = 0;
                            }
                            $vacationHomesByStatus[$statusLower]++;
                            
                            // Count completed (confirmed, approved, completed)
                            if (in_array($statusLower, ['confirmed', 'approved', 'completed'])) {
                                $vacationHomesCompleted++;
                            }
                        }
                        // Check if it's a hotel room (handle all variants)
                        elseif (in_array($reservableType, ['App\\Models\\HotelRoom', 'App\Models\HotelRoom', 'HotelRoom'])) {
                            $hotelRoomsCount++;
                            
                            // Count by status (use original status, not lowercase, for consistency)
                            if (!isset($hotelRoomsByStatus[$statusLower])) {
                                $hotelRoomsByStatus[$statusLower] = 0;
                            }
                            $hotelRoomsByStatus[$statusLower]++;
                            
                            // Count completed (confirmed, approved, completed)
                            if (in_array($statusLower, ['confirmed', 'approved', 'completed'])) {
                                $hotelRoomsCompleted++;
                            }
                        }
                    } catch (\Exception $e) {
                        // Skip this reservation if there's an error processing it
                        Log::warning('Error processing reservation in counting loop', [
                            'reservation_id' => $reservation->id ?? 'unknown',
                            'error' => $e->getMessage()
                        ]);
                        continue;
                    }
                }

            // Get total count
            $totalCount = $vacationHomesCount + $hotelRoomsCount;

                // Debug: Log the counts
                Log::info('Reservation counts for customer (simplified counting)', [
                    'customer_id' => $customer_id,
                    'customer_email' => $customer->email ?? 'N/A',
                    'total_reservations' => $totalCount,
                    'vacation_homes_count' => $vacationHomesCount,
                    'hotel_rooms_count' => $hotelRoomsCount,
                    'vacation_homes_completed' => $vacationHomesCompleted,
                    'hotel_rooms_completed' => $hotelRoomsCompleted,
                    'vacation_homes_by_status' => $vacationHomesByStatus,
                    'hotel_rooms_by_status' => $hotelRoomsByStatus
                ]);
                
            } catch (\Exception $e) {
                Log::error('Error fetching and counting reservations', [
                    'customer_id' => $customer_id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => substr($e->getTraceAsString(), 0, 1000)
                ]);
                
                // Variables are already initialized above, so they default to 0/[]
                // No need to reset them here
            }
            
            // Get used tier discounts - wrap in try-catch in case table doesn't exist
            $usedPropertyDiscounts = [];
            $usedHotelDiscounts = [];
            
            try {
                if (class_exists('\App\Models\CustomerTierDiscount')) {
                    $usedPropertyDiscounts = \App\Models\CustomerTierDiscount::where('customer_id', $customer_id)
                ->where('reservable_type', 'App\\Models\\Property')
                        ->where('used', true)
                        ->pluck('tier_milestone')
                ->toArray();
                }
            } catch (\Exception $e) {
                Log::warning('Could not fetch used property discounts', [
                    'customer_id' => $customer_id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                // Set empty array on error
                $usedPropertyDiscounts = [];
            }
            
            try {
                if (class_exists('\App\Models\CustomerTierDiscount')) {
                    $usedHotelDiscounts = \App\Models\CustomerTierDiscount::where('customer_id', $customer_id)
                        ->where(function($query) {
                            $query->where('reservable_type', 'App\\Models\\HotelRoom')
                                  ->orWhere('reservable_type', 'App\Models\HotelRoom')
                                  ->orWhere('reservable_type', 'HotelRoom');
                        })
                        ->where('used', true)
                        ->pluck('tier_milestone')
                ->toArray();
                }
            } catch (\Exception $e) {
                Log::warning('Could not fetch used hotel discounts', [
                    'customer_id' => $customer_id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                // Set empty array on error
                $usedHotelDiscounts = [];
            }
            
            // Calculate available discounts for vacation homes (Star Tiers)
            $propertyDiscounts = [];
            $propertyNextReservation = $vacationHomesCompleted + 1;
            $propertyTierMilestones = [
                15 => ['reservation_number' => 16, 'discount' => 10],
                10 => ['reservation_number' => 11, 'discount' => 7],
                5 => ['reservation_number' => 6, 'discount' => 3]
            ];
            
            foreach ($propertyTierMilestones as $milestone => $tierData) {
                $isUsed = in_array($milestone, $usedPropertyDiscounts);
                $isAvailable = $vacationHomesCompleted >= $milestone && $propertyNextReservation == $tierData['reservation_number'] && !$isUsed;
                
                $propertyDiscounts[] = [
                    'tier_milestone' => $milestone,
                    'reservation_number' => $tierData['reservation_number'],
                    'discount_percentage' => $tierData['discount'],
                    'is_available' => $isAvailable,
                    'is_used' => $isUsed
                ];
            }
            
            // Calculate available discounts for hotels (Moon Tiers)
            $hotelDiscounts = [];
            $hotelNextReservation = $hotelRoomsCompleted + 1;
            $hotelTierMilestones = [
                20 => ['reservation_number' => 21, 'discount' => 5],
                15 => ['reservation_number' => 16, 'discount' => 4],
                10 => ['reservation_number' => 11, 'discount' => 2]
            ];
            
            foreach ($hotelTierMilestones as $milestone => $tierData) {
                $isUsed = in_array($milestone, $usedHotelDiscounts);
                $isAvailable = $hotelRoomsCompleted >= $milestone && $hotelNextReservation == $tierData['reservation_number'] && !$isUsed;
                
                $hotelDiscounts[] = [
                    'tier_milestone' => $milestone,
                    'reservation_number' => $tierData['reservation_number'],
                    'discount_percentage' => $tierData['discount'],
                    'is_available' => $isAvailable,
                    'is_used' => $isUsed
                ];
            }

            return ApiResponseService::successResponse('Customer reservation counts retrieved successfully', [
                'customer_id' => $customer_id,
                'customer_name' => $customer->name ?? 'N/A',
                'customer_email' => $customer->email ?? 'N/A',
                'total_reservations' => $totalCount,
                'vacation_homes' => [
                    'total_count' => $vacationHomesCount,
                    'completed_count' => $vacationHomesCompleted,
                    'next_reservation_number' => $propertyNextReservation,
                    'by_status' => $vacationHomesByStatus,
                    'tier_discounts' => $propertyDiscounts,
                    'tier_count' => $vacationHomesCount
                ],
                'hotel_rooms' => [
                    'total_count' => $hotelRoomsCount,
                    'completed_count' => $hotelRoomsCompleted,
                    'next_reservation_number' => $hotelNextReservation,
                    'by_status' => $hotelRoomsByStatus,
                    'tier_discounts' => $hotelDiscounts,
                    'tier_count' => $hotelRoomsCount
                ]
            ]);

        } catch (\Exception $e) {
            // Log the full error for debugging
            try {
                Log::error('Error in getCustomerReservationCounts', [
                    'customer_id' => $customer_id ?? 'unknown',
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => substr($e->getTraceAsString(), 0, 1000) // Limit trace length
                ]);
            } catch (\Exception $logError) {
                // If logging fails, at least try to return an error
            }
            
            // Return a safe error response
            return ApiResponseService::errorResponse(
                'Failed to get customer reservation counts: ' . $e->getMessage(),
                null,
                null,
                $e
            );
        }
    }

    /**
     * Calculate customer discount based on loyalty tier milestones.
     * 
     * @param int $customerId
     * @param string $reservableType 'App\\Models\\Property' or 'App\\Models\\HotelRoom'
     * @param float $originalAmount
     * @return array
     */
    private function calculateCustomerDiscount($customerId, $reservableType, $originalAmount)
    {
        // Count all completed bookings (confirmed, approved, or completed) for discount calculation
        // This includes old reservations with discounts - all confirmed/approved/completed reservations count toward tier milestones
        // Match frontend logic: count confirmed, approved, completed (frontend shows these as "success" statuses)
        // Exclude cancelled, rejected, refunded (frontend shows these as "fail" statuses)
        // Handle hotel room reservable_type variants
        if ($reservableType === 'App\\Models\\HotelRoom') {
            $completedBookings = Reservation::where('customer_id', $customerId)
                ->whereIn('reservable_type', [
                    'App\\Models\\HotelRoom',
                    'App\Models\HotelRoom',
                    'HotelRoom'
                ])
                ->whereIn('status', ['confirmed', 'approved', 'completed'])
                ->count();
        } else {
        $completedBookings = Reservation::where('customer_id', $customerId)
            ->where('reservable_type', $reservableType)
                ->whereIn('status', ['confirmed', 'approved', 'completed'])
            ->count();
        }

        $discountPercentage = 0;
        $tierMilestone = null;

        // Define tier milestones and their discounts
        // Discount applies to specific reservation numbers (6th, 11th, 16th for Star / 11th, 16th, 21st for Moon)
        // This means: if customer has 5 completed, the 6th booking gets discount
        //             if customer has 10 completed, the 11th booking gets discount
        //             if customer has 15 completed, the 16th booking gets discount
        
        $tierMilestones = [];
        if ($reservableType === 'App\\Models\\Property') {
            // Star Tiers: 6th reservation (after 5), 11th (after 10), 16th (after 15)
            $tierMilestones = [
                15 => ['reservation_number' => 16, 'discount' => 10], // 10% discount on 16th reservation
                10 => ['reservation_number' => 11, 'discount' => 7],  // 7% discount on 11th reservation
                5 => ['reservation_number' => 6, 'discount' => 3]     // 3% discount on 6th reservation
            ];
        } elseif ($reservableType === 'App\\Models\\HotelRoom') {
            // Moon Tiers: 11th reservation (after 10), 16th (after 15), 21st (after 20)
            $tierMilestones = [
                20 => ['reservation_number' => 21, 'discount' => 5], // 5% discount on 21st reservation
                15 => ['reservation_number' => 16, 'discount' => 4], // 4% discount on 16th reservation
                10 => ['reservation_number' => 11, 'discount' => 2]  // 2% discount on 11th reservation
            ];
        }

        // Find the highest tier milestone that customer has reached and the next reservation matches
        // The next reservation number will be: completedBookings + 1
        $nextReservationNumber = $completedBookings + 1;
        
        foreach ($tierMilestones as $milestone => $tierData) {
                if ($completedBookings >= $milestone && $nextReservationNumber == $tierData['reservation_number']) {
                // Check if this discount has been used - handle hotel room reservable_type variants
                $usedDiscount = false;
                try {
                    if (class_exists('\App\Models\CustomerTierDiscount') && \Illuminate\Support\Facades\Schema::hasTable('customer_tier_discounts')) {
                        if ($reservableType === 'App\\Models\\HotelRoom') {
                            $usedDiscount = \App\Models\CustomerTierDiscount::where('customer_id', $customerId)
                                ->where('tier_milestone', $milestone)
                                ->where('used', true)
                                ->whereIn('reservable_type', [
                                    'App\\Models\\HotelRoom',
                                    'App\Models\HotelRoom',
                                    'HotelRoom'
                                ])
                                ->exists();
                        } else {
                            $usedDiscount = \App\Models\CustomerTierDiscount::where('customer_id', $customerId)
                                ->where('tier_milestone', $milestone)
                                ->where('used', true)
                                ->where('reservable_type', $reservableType)
                                ->exists();
                        }
                    }
                } catch (\Exception $e) {
                    // If table doesn't exist or query fails, assume discount is not used
                    Log::warning('Error checking used discount', [
                        'customer_id' => $customerId,
                        'error' => $e->getMessage()
                    ]);
                    $usedDiscount = false;
                }

                if (!$usedDiscount) {
                    // Found an available discount for this specific reservation number
                    $tierMilestone = $milestone;
                    $discountPercentage = $tierData['discount'];
                    break;
                }
            }
        }

        $discountAmount = ($originalAmount * $discountPercentage) / 100;
        $finalAmount = $originalAmount - $discountAmount;

        return [
            'original_amount' => $originalAmount,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'completed_bookings' => $completedBookings,
            'next_reservation_number' => $nextReservationNumber,
            'tier_milestone' => $tierMilestone,
            'has_available_discount' => $discountPercentage > 0
        ];
    }

    /**
     * Send vacation home booking request notification to property owner.
     * This is sent when instant booking is disabled and a booking request is made.
     *
     * @param \App\Models\Reservation $reservation
     * @return void
     */
    private function sendVacationHomeBookingRequestToOwner($reservation)
    {
        try {
            $property = $reservation->property;
            $propertyOwner = $property->customer;
            $customer = $reservation->customer;

            Log::info('Debug Notification', [
                'property_id' => $property->id,
                'owner_id' => $propertyOwner ? $propertyOwner->id : 'null',
                'customer_id' => $customer ? $customer->id : 'null'
            ]);

            if (app()->runningInConsole()) {
                 fwrite(STDERR, "\n[Debug] Vacation Home Request Owner ID: " . ($propertyOwner ? $propertyOwner->id : 'null') . "\n");
                 $count = $propertyOwner ? Usertokens::where('customer_id', $propertyOwner->id)->count() : 0;
                 fwrite(STDERR, "[Debug] Owner Tokens Count: " . $count . "\n");
            }

            if (!$propertyOwner || !$propertyOwner->email) {
                Log::warning('Cannot send vacation home booking request notification: property owner or email not found', [
                    'reservation_id' => $reservation->id,
                    'property_id' => $property->id
                ]);
                return;
            }

            // Get email template data
            $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes('vacation_home_owner_booking_notification');
            $templateData = system_setting('vacation_home_owner_booking_notification_mail_template');
            $appName = env('APP_NAME') ?? 'As-home';

            // Get property information
            $propertyName = $property->title ?? 'Property';
            $propertyAddress = $property->address ?? 'N/A';

            // Get currency symbol
            $currencySymbol = system_setting('currency_symbol') ?? '$';

            $variables = array(
                'app_name' => $appName,
                'property_owner_name' => $propertyOwner->name,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->mobile ?? $customer->phone ?? 'N/A',
                'property_name' => $propertyName,
                'hotel_name' => $propertyName, // Alias for template compatibility
                'property_address' => $propertyAddress,
                'hotel_address' => $propertyAddress, // Alias for template compatibility
                'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->format('d M Y') : 'N/A',
                'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A',
                'number_of_guests' => $reservation->number_of_guests,
                'total_price' => number_format($reservation->total_price, 2),
                'total_amount' => number_format($reservation->total_price, 2), // Alias for template compatibility
                'currency_symbol' => $currencySymbol,
                'payment_status' => ucfirst($reservation->payment_status),
                'special_requests' => $reservation->special_requests ?? 'None',
                'reservation_id' => $reservation->id,
                'booking_date' => now()->format('d M Y, h:i A'),
                'booking_type' => 'vacation_home_booking_request',
                'room_type' => 'Vacation Home', // For template compatibility
                'room_number' => 'N/A' // For template compatibility
            );

            if (empty($templateData)) {
                $templateData = 'Dear {property_owner_name},



A new booking request has been received for your vacation home property.



Booking Details:

• Property: {property_name}

• Address: {property_address}

• Reservation ID: {reservation_id}



Guest Information:

• Name: {customer_name}

• Email: {customer_email}

• Phone: {customer_phone}



Booking Period:

• Check-in Date: {check_in_date}

• Check-out Date: {check_out_date}

• Number of Guests: {number_of_guests}



Financial Details:

• Total Amount: {currency_symbol}{total_amount}

• Payment Status: {payment_status}



Special Requests: {special_requests}



Booking Date: {booking_date}



⏳ Action Required: Please review and approve or reject this booking request in your dashboard.



If you have any questions or need assistance, please don\'t hesitate to contact us at support@as-home-group.com or through your As-home account dashboard.



Thank you for being part of As-home!



Best regards,

As-home Asset Management Team

🌐 www.as-home-group.com';
            }

            $emailTemplate = \App\Services\HelperService::replaceEmailVariables($templateData, $variables);

            $data = array(
                'email_template' => $emailTemplate,
                'email' => $propertyOwner->email,
                'title' => $emailTypeData['title'] ?? 'Vacation Home Owner Booking Notification',
            );

            \App\Services\HelperService::sendMail($data);

            // Send Push Notification to Owner
            $owner_tokens = Usertokens::where('customer_id', $propertyOwner->id)->pluck('fcm_id')->toArray();
            if (!empty($owner_tokens)) {
                $fcmMsg = array(
                    'title' => $emailTypeData['title'] ?? 'New Booking Request',
                    'message' => "{$customer->name} have just made reservation for {$propertyName}",
                    'type' => 'vacation_home_booking_request', 
                    'body' => "{$customer->name} have just made reservation for {$propertyName}",
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'sound' => 'default',
                    'id' => (string)$reservation->id,
                    'booking_type' => 'vacation_home_booking_request'
                );
                send_push_notification($owner_tokens, $fcmMsg);
            }

            Log::info('Vacation home booking request notification sent to property owner', [
                'reservation_id' => $reservation->id,
                'property_owner_email' => $propertyOwner->email,
                'property_id' => $property->id,
                'booking_type' => 'vacation_home_booking_request'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send vacation home booking request notification to property owner: ' . $e->getMessage(), [
                'reservation_id' => $reservation->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send new booking notification to property owner.
     *
     * @param \App\Models\Reservation $reservation
     * @return void
     */
    private function sendNewBookingNotificationToOwner($reservation)
    {
        if (app()->runningInConsole()) { fwrite(STDERR, "\n[Debug] Start sendNewBookingNotificationToOwner\n"); }
        try {
            $property = $reservation->property;
            $propertyOwner = $property->customer;
            $customer = $reservation->customer;

            if (app()->runningInConsole()) {
                 fwrite(STDERR, "\n[Debug] Owner ID: " . ($propertyOwner ? $propertyOwner->id : 'null') . "\n");
                 $count = $propertyOwner ? Usertokens::where('customer_id', $propertyOwner->id)->count() : 0;
                 fwrite(STDERR, "[Debug] Owner Tokens Count: " . $count . "\n");
            }

            Log::info('Debug NewBookingNotification', [
                'property_id' => $property->id,
                'owner_id' => $propertyOwner ? $propertyOwner->id : 'null',
                'customer_id' => $customer ? $customer->id : 'null',
                'owner_tokens_count' => $propertyOwner ? Usertokens::where('customer_id', $propertyOwner->id)->count() : 0
            ]);

            if (!$propertyOwner || !$propertyOwner->email) {
                Log::warning('Cannot send new booking notification: property owner or email not found', [
                    'reservation_id' => $reservation->id,
                    'property_id' => $property->id
                ]);
                return;
            }

            // Determine notification type based on status
            $isConfirmed = in_array($reservation->status, ['confirmed', 'approved']);
            $notificationTitle = $isConfirmed ? 'New Booking Confirmed' : 'New Booking Request';
            $notificationBody = "{$customer->name} have just made reservation for {$property->title}";

            // Get email template data
            $emailTypeData = \App\Services\HelperService::getEmailTemplatesTypes('new_booking_notification');
            $templateData = system_setting('new_booking_notification_mail_template');
            $appName = env('APP_NAME') ?? 'As-home';

            // Get hotel and room information
            $hotelName = $property->title ?? 'Hotel';
            $roomType = 'Property';
            $roomNumber = 'N/A';
            $hotelAddress = $property->address ?? 'N/A';

            // Load reservable if not already loaded
            if (!$reservation->relationLoaded('reservable')) {
                $reservation->load('reservable');
            }
            $reservable = $reservation->reservable;

            if ($reservable instanceof \App\Models\HotelRoom) {
                $hotelRoom = $reservable;
                $roomNumber = $hotelRoom->room_number ?? 'N/A';
                if ($hotelRoom->roomType) {
                    $roomType = $hotelRoom->roomType->name ?? 'Standard Room';
                }
            } elseif ($reservation->reservable_type === 'hotel_room' || $reservation->reservable_type === 'App\\Models\\HotelRoom') {
                $hotelRoom = HotelRoom::find($reservation->reservable_id);
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
                $templateData = 'New flexible booking request received for {hotel_name} from {customer_name} ({customer_email}). Room Type: {room_type}. Amount: {total_price} {currency_symbol}. Check-in: {check_in_date}, Check-out: {check_out_date}. Number of Guests: {number_of_guests}. Special Requests: {special_requests}. Reservation ID: {reservation_id}. Please review and approve this booking in your dashboard.';
            }

            $emailTemplate = \App\Services\HelperService::replaceEmailVariables($templateData, $variables);

            $data = array(
                'email_template' => $emailTemplate,
                'email' => $propertyOwner->email,
                'title' => $emailTypeData['title'] ?? ($isConfirmed ? 'New Booking Confirmed' : 'New Flexible Booking Request - Approval Required'),
            );

            \App\Services\HelperService::sendMail($data);

            // Send Push Notification to Owner
            $owner_tokens = Usertokens::where('customer_id', $propertyOwner->id)->pluck('fcm_id')->toArray();
            if (!empty($owner_tokens)) {
                $fcmMsg = array(
                    'title' => $notificationTitle,
                    'message' => $notificationBody,
                    'type' => 'reservation_request', 
                    'body' => $notificationBody,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'sound' => 'default',
                    'id' => (string)$reservation->id,
                    'booking_type' => 'flexible_booking'
                );
                send_push_notification($owner_tokens, $fcmMsg);
            }

            Log::info('New booking notification sent to property owner', [
                'reservation_id' => $reservation->id,
                'property_owner_email' => $propertyOwner->email,
                'property_id' => $property->id,
                'booking_type' => 'flexible',
                'status' => $reservation->status
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
