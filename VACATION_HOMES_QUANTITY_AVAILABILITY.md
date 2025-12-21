# Vacation Homes: 4 Identical Units - How Availability Works

## Scenario
- **Vacation Apartment**: "A101"
- **Quantity**: 4 (4 identical units)
- **User makes**: 1 reservation for 1 unit

## Current Implementation Analysis

### How It Currently Works

#### 1. **Reservation Creation**

When a user makes 1 reservation for 1 unit:

**Request:**
```json
{
  "reservable_type": "property",
  "property_id": 1,
  "reservable_id": 1,
  "apartment_id": 5,  // Apartment "A101" with quantity = 4
  "apartment_quantity": 1,  // Booking 1 unit
  "check_in_date": "2025-02-01",
  "check_out_date": "2025-02-05"
}
```

**What Happens:**
```php
// Price calculation
$pricePerNight = $apartment->price_per_night;  // e.g., 150
$discount = $apartment->discount_percentage ?? 0;  // e.g., 10%
$discountedPrice = $pricePerNight * (1 - ($discount / 100));  // 135
$numberOfDays = 4;  // Feb 1-5
$totalPrice = $discountedPrice * $numberOfDays * $apartmentQuantity;  // 135 * 4 * 1 = 540

// Reservation stored
$reservationData = [
    'reservable_id' => $property->id,  // Property ID, NOT apartment ID
    'reservable_type' => 'App\Models\Property',
    'total_price' => 540,
    'special_requests' => '... Apartment ID: 5, Quantity: 1'  // Info stored here
];
```

#### 2. **Availability Checking**

**Current Logic (`areDatesAvailable`):**

```php
// In ReservationService::areDatesAvailable()
public function areDatesAvailable($modelType, $modelId, $checkInDate, $checkOutDate, $excludeReservationId = null)
{
    // For properties (vacation homes)
    if ($modelType === 'App\Models\Property') {
        // Checks if there's ANY confirmed reservation for the PROPERTY on these dates
        $hasOverlap = Reservation::datesOverlap(
            $checkInDate, 
            $checkOutDate, 
            $modelId,  // Property ID
            $modelType
        );
        
        if ($hasOverlap) {
            return false;  // ❌ BLOCKS ALL FUTURE BOOKINGS
        }
    }
    
    return true;  // ✅ Available
}
```

**The Problem:**
- Availability is checked at the **property level**, not per apartment or per unit
- If there's **1 confirmed reservation** for the property on those dates, it blocks **ALL** future bookings
- It doesn't check:
  - Which apartment was booked
  - How many units were booked
  - How many units are still available

---

## Example Scenario

### Setup
- **Property**: "Beachfront Resort" (ID: 1)
- **Apartment**: "A101" (ID: 5, quantity: 4)
- **Dates**: Feb 1-5, 2025

### Step 1: First Reservation (1 unit)
```
User 1 books: 1 unit of A101 for Feb 1-5
Reservation created:
  - reservable_id: 1 (Property ID)
  - special_requests: "Apartment ID: 5, Quantity: 1"
  - status: "confirmed"
```

### Step 2: Availability Check for Second User
```
User 2 tries to book: 1 unit of A101 for Feb 1-5

System checks:
  - Are there confirmed reservations for Property ID 1 on Feb 1-5?
  - Answer: YES (User 1's reservation)
  - Result: ❌ NOT AVAILABLE (blocks all bookings)
```

**Expected Behavior:**
- Should check: How many units of A101 are booked for Feb 1-5?
- Should find: 1 unit booked, 3 units still available
- Should allow: User 2's booking (1 unit) ✅

**Actual Behavior:**
- Finds: 1 confirmed reservation for property
- Blocks: ALL future bookings ❌
- Result: User 2 cannot book, even though 3 units are available

---

## Why This Happens

### 1. **Reservation Storage**
- Reservations are stored with `reservable_id = property_id`
- Apartment info is only in `special_requests` (text field)
- No direct link between reservation and apartment

### 2. **Availability Logic**
- `areDatesAvailable()` checks property-level reservations
- Doesn't parse `special_requests` to extract apartment info
- Doesn't count units per apartment

### 3. **No Unit Tracking**
- The `quantity` field exists but isn't used in availability checks
- No system to track "X units booked, Y units available"

---

## How It Should Work (Ideal Implementation)

### Option 1: Count Units from Reservations

```php
public function areDatesAvailable($modelType, $modelId, $checkInDate, $checkOutDate, $apartmentId = null, $requestedQuantity = 1)
{
    if ($modelType === 'App\Models\Property' && $apartmentId) {
        // Get the apartment
        $apartment = VacationApartment::find($apartmentId);
        if (!$apartment) {
            return false;
        }
        
        $totalUnits = $apartment->quantity;  // 4
        
        // Count how many units are booked for these dates
        $bookedUnits = Reservation::where('reservable_type', 'App\Models\Property')
            ->where('reservable_id', $modelId)
            ->where('status', 'confirmed')
            ->where(function($query) use ($checkInDate, $checkOutDate) {
                // Check date overlap
                $query->where(function($q) use ($checkInDate, $checkOutDate) {
                    $q->where('check_in_date', '<', $checkOutDate)
                      ->where('check_out_date', '>', $checkInDate);
                });
            })
            ->get()
            ->sum(function($reservation) use ($apartmentId) {
                // Parse special_requests to extract apartment quantity
                $specialRequests = $reservation->special_requests ?? '';
                if (strpos($specialRequests, "Apartment ID: {$apartmentId}") !== false) {
                    // Extract quantity from special_requests
                    preg_match('/Quantity:\s*(\d+)/', $specialRequests, $matches);
                    return isset($matches[1]) ? (int)$matches[1] : 0;
                }
                return 0;
            });
        
        $availableUnits = $totalUnits - $bookedUnits;  // 4 - 1 = 3
        
        // Check if requested quantity is available
        return $availableUnits >= $requestedQuantity;  // 3 >= 1 ✅
    }
    
    // Fallback to current logic
    return !Reservation::datesOverlap($checkInDate, $checkOutDate, $modelId, $modelType);
}
```

### Option 2: Store Apartment ID in Reservation

**Better Approach:** Add `apartment_id` and `apartment_quantity` fields to reservations table:

```php
// Migration
Schema::table('reservations', function (Blueprint $table) {
    $table->foreignId('apartment_id')->nullable()->after('property_id')
        ->constrained('vacation_apartments')->onDelete('cascade');
    $table->integer('apartment_quantity')->default(1)->after('apartment_id');
});

// Then availability check becomes:
public function areDatesAvailable($modelType, $modelId, $checkInDate, $checkOutDate, $apartmentId = null, $requestedQuantity = 1)
{
    if ($modelType === 'App\Models\Property' && $apartmentId) {
        $apartment = VacationApartment::find($apartmentId);
        $totalUnits = $apartment->quantity;
        
        $bookedUnits = Reservation::where('reservable_type', 'App\Models\Property')
            ->where('reservable_id', $modelId)
            ->where('apartment_id', $apartmentId)  // Direct link!
            ->where('status', 'confirmed')
            ->where(function($query) use ($checkInDate, $checkOutDate) {
                $query->where('check_in_date', '<', $checkOutDate)
                      ->where('check_out_date', '>', $checkInDate);
            })
            ->sum('apartment_quantity');  // Sum booked quantities
        
        $availableUnits = $totalUnits - $bookedUnits;
        return $availableUnits >= $requestedQuantity;
    }
    
    // Fallback
    return !Reservation::datesOverlap($checkInDate, $checkOutDate, $modelId, $modelType);
}
```

---

## Current Limitations

### ❌ What Doesn't Work
1. **Unit-level availability**: Can't check if specific units are available
2. **Quantity tracking**: Doesn't count how many units are booked vs available
3. **Multiple bookings**: One booking blocks all future bookings for those dates
4. **Apartment-specific**: Doesn't differentiate between different apartments

### ✅ What Works
1. **Pricing**: Correctly calculates price based on `apartment_quantity`
2. **Reservation storage**: Stores apartment info in `special_requests`
3. **Property-level blocking**: Prevents double-booking at property level (too aggressive)

---

## Recommendations

### Short-term Fix (Without Schema Changes)
1. Parse `special_requests` in availability checks
2. Count units booked per apartment
3. Compare with `apartment->quantity`

### Long-term Fix (With Schema Changes)
1. Add `apartment_id` and `apartment_quantity` to `reservations` table
2. Update reservation creation to store these fields
3. Update availability logic to use direct apartment link
4. Add index on `apartment_id` for performance

---

## Summary

**Current Behavior:**
- 4 identical units
- 1 reservation made
- **Result**: All 4 units become "unavailable" for those dates ❌

**Expected Behavior:**
- 4 identical units
- 1 reservation made (1 unit)
- **Result**: 3 units still available, can accept 3 more bookings ✅

**Root Cause:**
- Availability checking is at property level, not unit level
- No counting of booked vs available units
- Apartment info stored in text field, not directly queryable

---

**Last Updated:** After analyzing quantity-based availability
**Status:** ⚠️ Limitation identified - needs improvement

