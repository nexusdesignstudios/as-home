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
            // If availability_type is 1 (available_days), check if search date range falls within any of the available date ranges
            if ($room->availability_type == 1) {
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
            // If availability_type is 2 (busy_days), check if search date range doesn't overlap with any of the busy date ranges
            else if ($room->availability_type == 2) {
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
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $rooms = HotelRoom::with('roomType', 'property')->get();

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
            'refund_policy' => 'nullable|string|in:flexible,non-refundable',
            'description' => 'nullable|string',
            'status' => 'nullable|boolean',
            'availability_type' => 'required|integer|in:1,2',
            'available_dates' => 'required|array',
            'weekend_commission' => 'nullable|numeric|min:0|max:100'
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
            'refund_policy' => 'nullable|string|in:flexible,non-refundable',
            'description' => 'nullable|string',
            'status' => 'nullable|boolean',
            'availability_type' => 'nullable|integer|in:1,2',
            'available_dates' => 'nullable|array',
            'weekend_commission' => 'nullable|numeric|min:0|max:100'
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
