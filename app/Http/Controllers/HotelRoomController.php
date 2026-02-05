<?php

namespace App\Http\Controllers;

use App\Models\HotelRoom;
use App\Models\HotelRoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class HotelRoomController extends Controller
{
    /**
     * Search for available hotel rooms based on room type and date range
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function searchAvailableRooms(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'room_type_id' => 'nullable|exists:hotel_room_types,id',
            'from_date' => 'required|date_format:Y-m-d',
            'to_date' => 'required|date_format:Y-m-d|after_or_equal:from_date',
            'property_id' => 'nullable|exists:propertys,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Parse dates
        $fromDate = Carbon::parse($request->from_date);
        $toDate = Carbon::parse($request->to_date);

        // Start query
        $query = HotelRoom::query()
            ->where('status', true)
            ->with('roomType', 'property');

        // Filter by room type if provided
        if ($request->has('room_type_id')) {
            $query->where('room_type_id', $request->room_type_id);
        }

        // Filter by property if provided
        if ($request->has('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        // Get all rooms that match the initial criteria
        $rooms = $query->get();

        // Filter rooms based on availability
        $availableRooms = $rooms->filter(function ($room) use ($fromDate, $toDate) {
            // First check if the room has any existing reservations that conflict with the requested dates
            // Check both possible reservable_type values: 'App\\Models\\HotelRoom' and 'hotel_room'
            $hasConflictingReservation = \App\Models\Reservation::where('reservable_id', $room->id)
                ->where('property_id', $room->property_id)
                ->where(function($query) {
                    $query->where('reservable_type', 'App\\Models\\HotelRoom')
                          ->orWhere('reservable_type', 'hotel_room');
                })
                ->whereIn('status', ['confirmed', 'approved', 'active'])
                ->where(function($query) use ($fromDate, $toDate) {
                    $query->where('check_in_date', '<=', $toDate->format('Y-m-d'))
                          ->where('check_out_date', '>', $fromDate->format('Y-m-d'));
                })
                ->exists();
            
            // If there's a conflicting reservation, room is not available
            if ($hasConflictingReservation) {
                return false;
            }
            
            // If availability_type is 1 or 'available_days', check if search date range falls within any of the available date ranges
            if ($room->availability_type == 1 || $room->availability_type == 'available_days') {
                $availableDateRanges = $room->available_dates;

                // Check if the requested date range is covered by any of the available date ranges
                foreach ($availableDateRanges as $dateRange) {
                    $rangeFromDate = Carbon::parse($dateRange['from']);
                    $rangeToDate = Carbon::parse($dateRange['to']);

                    // If the entire search range is within this available range, the room is available
                    if ($fromDate->greaterThanOrEqualTo($rangeFromDate) && $toDate->lessThanOrEqualTo($rangeToDate)) {
                        return true;
                    }
                }

                return false;
            }
            // If availability_type is 2 or 'busy_days', check if search date range doesn't overlap with any of the busy date ranges
            else if ($room->availability_type == 2 || $room->availability_type == 'busy_days') {
                $busyDateRanges = $room->available_dates;

                // Check if the requested date range overlaps with any busy date range
                foreach ($busyDateRanges as $dateRange) {
                    $rangeFromDate = Carbon::parse($dateRange['from']);
                    $rangeToDate = Carbon::parse($dateRange['to']);

                    // If there's any overlap between the search range and this busy range, the room is not available
                    // Check if the ranges overlap (one range starts before the other ends)
                    if ($fromDate->lessThanOrEqualTo($rangeToDate) && $toDate->greaterThanOrEqualTo($rangeFromDate)) {
                        return false;
                    }
                }

                return true;
            }

            return false;
        });

        return response()->json([
            'error' => false,
            'message' => 'Available rooms fetched successfully',
            'data' => $availableRooms->values()
        ]);
    }

    /**
     * Update the availability of the specified room.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:hotel_rooms,id',
            'property_id' => 'required|exists:propertys,id',
            'available_dates' => 'nullable', // Can be array or JSON string
            'availability_type' => 'nullable|integer|in:1,2',
            'price_per_night' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $room = HotelRoom::findOrFail($request->room_id);
            
            $updateData = [];
            if ($request->has('availability_type')) {
                $updateData['availability_type'] = $request->availability_type;
            }
            if ($request->has('price_per_night')) {
                $updateData['price_per_night'] = $request->price_per_night;
            }
            
            // Handle available_dates
            if ($request->has('available_dates')) {
                $availableDates = $request->available_dates;
                if (is_string($availableDates)) {
                    $availableDates = json_decode($availableDates, true);
                }
                
                if (!is_array($availableDates)) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Invalid available_dates format'
                    ], 422);
                }

                // Update legacy column
                $updateData['available_dates'] = $availableDates;

                // Sync with available_dates_hotel_rooms table
                // First, delete existing entries for this room
                \App\Models\AvailableDatesHotelRoom::where('hotel_room_id', $room->id)->delete();

                // Insert new entries
                foreach ($availableDates as $dateInfo) {
                    // Map frontend keys to DB columns
                    $fromDate = $dateInfo['from'] ?? $dateInfo['from_date'] ?? $dateInfo['start_date'] ?? null;
                    $toDate = $dateInfo['to'] ?? $dateInfo['to_date'] ?? $dateInfo['end_date'] ?? null;
                    
                    if ($fromDate && $toDate) {
                        \App\Models\AvailableDatesHotelRoom::create([
                            'property_id' => $request->property_id,
                            'hotel_room_id' => $room->id,
                            'from_date' => $fromDate,
                            'to_date' => $toDate,
                            'price' => $dateInfo['price'] ?? 0,
                            'type' => $dateInfo['type'] ?? 'open',
                            'nonrefundable_percentage' => $dateInfo['nonrefundable_percentage'] ?? 0,
                        ]);
                    }
                }
            }
            
            if (!empty($updateData)) {
                $room->update($updateData);
            }

            return response()->json([
                'error' => false,
                'message' => 'Hotel room availability updated successfully',
                'data' => $room->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get hotel rooms from properties added by the authenticated user
        $rooms = HotelRoom::with('roomType', 'property')
            ->whereHas('property', function ($query) use ($user) {
                $query->where('added_by', $user->id);
            })
            ->get();

        return response()->json([
            'error' => false,
            'message' => 'Hotel rooms fetched successfully',
            'data' => $rooms
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:propertys,id',
            'room_type_id' => 'required|exists:hotel_room_types,id',
            'room_number' => 'required|string|max:50',
            'price_per_night' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'nonrefundable_percentage' => 'nullable|numeric|min:0|max:100',
            'refund_policy' => 'nullable|string|in:flexible,non-refundable',
            'description' => 'nullable|string',
            'status' => 'nullable|boolean',
            'availability_type' => 'required|integer|in:1,2',
            'available_dates' => 'required|array',
            'weekend_commission' => 'nullable|numeric|min:0|max:100',
            'max_guests' => 'nullable|integer|min:1',
            'min_guests' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $room = HotelRoom::create($request->all());

            return response()->json([
                'error' => false,
                'message' => 'Hotel room created successfully',
                'data' => $room
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $room = HotelRoom::with('roomType', 'property')->findOrFail($id);

            return response()->json([
                'error' => false,
                'message' => 'Hotel room fetched successfully',
                'data' => $room
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Hotel room not found'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'nullable|exists:propertys,id',
            'room_type_id' => 'nullable|exists:hotel_room_types,id',
            'room_number' => 'nullable|string|max:50',
            'price_per_night' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'nonrefundable_percentage' => 'nullable|numeric|min:0|max:100',
            'refund_policy' => 'nullable|string|in:flexible,non-refundable',
            'description' => 'nullable|string',
            'status' => 'nullable|boolean',
            'availability_type' => 'nullable|integer|in:1,2',
            'available_dates' => 'nullable|array',
            'weekend_commission' => 'nullable|numeric|min:0|max:100',
            'max_guests' => 'nullable|integer|min:1',
            'min_guests' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $room = HotelRoom::findOrFail($id);
            $room->update($request->all());

            return response()->json([
                'error' => false,
                'message' => 'Hotel room updated successfully',
                'data' => $room
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $room = HotelRoom::findOrFail($id);
            $room->delete();

            return response()->json([
                'error' => false,
                'message' => 'Hotel room deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }
}
