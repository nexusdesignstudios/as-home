<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Property;
use App\Models\HotelRoom;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Services\ReservationService;
use App\Services\ApiResponseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
}
