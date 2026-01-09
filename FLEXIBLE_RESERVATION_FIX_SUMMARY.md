# Flexible Hotel Reservation Room Assignment Fix

## Problem Description

When making flexible reservations for hotels (classification 5), the system was assigning multiple reservations to the same room ID instead of finding available rooms. This caused conflicts where multiple flexible reservations would be booked for the same room and dates.

### Example Issue:
- Reservation #1003: Room #812, Jan 8-10, 2026
- Reservation #1004: Room #812, Jan 8-10, 2026
- Both reservations assigned to same room instead of finding available rooms

## Root Cause

In `ApiController.php` method `submitPaymentForm`, line 12656:

```php
// OLD CODE - Problematic
$reservableId = $request->reservable_data[0]['id'] ?? $request->property_id;
```

This code always used the **first room** from `reservable_data` without checking if that room was actually available for the requested dates.

## Solution Implemented

### 1. Updated Room Assignment Logic

Modified room assignment in `submitPaymentForm` method to check for flexible bookings:

```php
// NEW CODE - Fixed
if ($request->reservable_type === 'hotel_room' && !empty($request->reservable_data)) {
    // Check if this is a flexible booking that needs room assignment
    $isFlexibleBooking = $request->has('booking_type') && $request->booking_type === 'flexible_booking';
    
    if ($isFlexibleBooking) {
        // For flexible bookings, find an available room instead of using first room
        $availableRoom = $this->findAvailableHotelRoom(
            $request->property_id,
            $request->check_in_date,
            $request->check_out_date,
            $request->reservable_data
        );
        
        if (!$availableRoom) {
            return response()->json([
                'error' => true,
                'message' => 'No available rooms found for selected dates. Please choose different dates.'
            ], 400);
        }
        
        $reservableId = $availableRoom->id;
        // ... logging code
    } else {
        // For non-flexible bookings, use first room's ID as before
        $reservableId = $request->reservable_data[0]['id'] ?? $request->property_id;
    }
}
```

### 2. Added Room Availability Checker Method

Added new private method `findAvailableHotelRoom()` to `ApiController`:

```php
private function findAvailableHotelRoom($propertyId, $checkInDate, $checkOutDate, $reservableData)
{
    // Extract room IDs from reservable_data
    $roomIds = array_column($reservableData, 'id');
    
    // Find an available room from provided room IDs
    $availableRoom = HotelRoom::where('property_id', $propertyId)
        ->whereIn('id', $roomIds)
        ->where('status', 1)
        ->whereDoesntHave('reservations', function ($query) use ($checkInDate, $checkOutDate) {
            $query->where('status', 'confirmed')
                ->where(function ($dateQuery) use ($checkInDate, $checkOutDate) {
                    // Check for date overlaps
                    $dateQuery->where(function ($q) use ($checkInDate, $checkOutDate) {
                        $q->where('check_in_date', '<=', $checkInDate)
                            ->where('check_out_date', '>', $checkInDate);
                    })
                    ->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                        $q->where('check_in_date', '<', $checkOutDate)
                            ->where('check_out_date', '>=', $checkOutDate);
                    })
                    ->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
                        $q->where('check_in_date', '>=', $checkInDate)
                            ->where('check_out_date', '<=', $checkOutDate);
                    });
                });
        })
        ->first();
    
    return $availableRoom;
}
```

## Key Features of the Fix

### ✅ Room Availability Checking
- Checks existing confirmed reservations for date overlaps
- Only considers active rooms (`status = 1`)
- Searches within provided room IDs from `reservable_data`

### ✅ Proper Error Handling
- Returns clear error message if no rooms available
- Prevents creation of conflicting reservations
- HTTP 400 status code for client handling

### ✅ Comprehensive Logging
- Logs room assignment process for debugging
- Tracks available room searches
- Records conflicts and resolutions

### ✅ Backward Compatibility
- Non-flexible bookings continue to work as before
- Only affects flexible bookings (`booking_type = 'flexible_booking'`)
- No breaking changes to existing API

## How It Works

1. **Flexible Booking Detection**: Checks if `booking_type` is `'flexible_booking'`
2. **Room Search**: Finds available room from provided room options
3. **Conflict Prevention**: Ensures no overlapping confirmed reservations
4. **Error Response**: Returns error if no rooms available
5. **Logging**: Records process for debugging and monitoring

## Testing

Created test scripts to verify:
- ✅ Room availability logic works correctly
- ✅ Existing reservations are properly detected
- ✅ Alternative rooms are found when needed
- ✅ Error handling works when no rooms available
- ✅ Current system conflicts are resolved

## Files Modified

1. **`app/Http/Controllers/ApiController.php`**
   - Updated `submitPaymentForm()` method (lines 12652-12686)
   - Added `findAvailableHotelRoom()` method (lines 13023-13091)

## Expected Outcome

After this fix:
- Flexible reservations will be assigned to available rooms only
- No more double bookings for same room and dates
- Clear error messages when no rooms available
- Better user experience and data integrity
- Comprehensive logging for debugging

## Verification

To test the fix:
1. Create a flexible reservation for a hotel
2. Create another flexible reservation for same hotel and dates
3. Second reservation should be assigned to a different available room
4. If no rooms available, system should return error message
