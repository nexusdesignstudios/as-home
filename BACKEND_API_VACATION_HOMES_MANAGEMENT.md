# Backend API Requirements for Vacation Homes Management Page

## 📋 Overview
This document outlines the backend API requirements for the Vacation Homes Management page (`/user/vacation-homes-management/`). **Most APIs already exist**, but some may need minor modifications to support vacation homes properly.

---

## ✅ APIs Currently Used (Already Exist)

### **1. Get Added Properties API**
**Status:** ✅ **EXISTS** - May need minor modifications

**Endpoint:** `GET /api/get-added-properties`

**Current Request:**
```
GET /api/get-added-properties?offset=0&limit=100
Headers: Authorization: Bearer {token}
```

**Required Modifications:**

#### **A. Filter by Logged-In User**
- ✅ **MUST:** API should automatically filter properties by the logged-in user's `user_id` from JWT token
- ✅ **MUST:** Only return properties where `properties.user_id = authenticated_user_id`

#### **B. Include Vacation Apartments**
- ✅ **MUST:** Include `vacation_apartments` array in each property response
- ✅ **MUST:** Return empty array `[]` if no apartments exist (don't omit the field)

**Expected Response Structure:**
```json
{
  "error": false,
  "data": [
    {
      "id": 123,
      "title": "Beachfront Villa",
      "title_ar": "فيلا على الشاطئ",
      "property_classification": 4,
      "address": "123 Beach Road",
      "city": "Hurghada",
      "state": "Red Sea",
      "status": 1,
      "title_image": "https://example.com/image.jpg",
      "total_click": 150,
      "user_id": 45,
      "vacation_apartments": [
        {
          "id": 1,
          "property_id": 123,
          "apartment_number": "A101",
          "price_per_night": 150,
          "quantity": 4,
          "max_guests": 4,
          "bedrooms": 2,
          "bathrooms": 1,
          "discount_percentage": 0,
          "status": true,
          "availability_type": 1,
          "available_dates": []
        },
        {
          "id": 2,
          "property_id": 123,
          "apartment_number": "A102",
          "price_per_night": 200,
          "quantity": 1,
          "max_guests": 6,
          "bedrooms": 3,
          "bathrooms": 2,
          "discount_percentage": 10,
          "status": true,
          "availability_type": 1,
          "available_dates": []
        }
      ]
    }
  ]
}
```

**Alternative Field Names (Both Accepted):**
- `vacation_apartments` (snake_case) ✅
- `vacationApartments` (camelCase) ✅

**Key Fields Required:**
- ✅ `id` - Property ID
- ✅ `title` - Property title
- ✅ `title_ar` - Arabic title (optional)
- ✅ `property_classification` - Must be `4` for vacation homes
- ✅ `address`, `city`, `state` - Location fields
- ✅ `status` - Property status (1 = active, 0 = inactive)
- ✅ `title_image` - Main property image URL
- ✅ `total_click` - View count (optional, can be 0)
- ✅ `vacation_apartments` - **REQUIRED** array (can be empty `[]`)

**Vacation Apartments Fields:**
- ✅ `id` - Apartment ID
- ✅ `property_id` - Property ID
- ✅ `apartment_number` - Apartment identifier (e.g., "A101")
- ✅ `price_per_night` - Price per night
- ✅ `quantity` - **REQUIRED** - Number of identical units (integer, default: 1)
- ✅ `max_guests` - Maximum guests
- ✅ `bedrooms` - Number of bedrooms
- ✅ `bathrooms` - Number of bathrooms
- ✅ `discount_percentage` - Discount percentage (0-100)
- ✅ `status` - Apartment status (boolean)
- ✅ `availability_type` - Availability type (1 or 2)
- ✅ `available_dates` - Array of available date ranges (can be empty `[]`)

---

### **2. Get Owner Reservations API**
**Status:** ✅ **EXISTS** - May need minor modifications

**Endpoint:** `GET /api/property-owner-reservations/{ownerId}`

**Current Request:**
```
GET /api/property-owner-reservations/45?per_page=100&page=1
Headers: Authorization: Bearer {token}
```

**Required Modifications:**

#### **A. Include Apartment Information for Vacation Homes**
- ✅ **MUST:** Include `apartment_id` field in reservation response
- ✅ **MUST:** Include `apartment_quantity` field in reservation response
- ✅ **MUST:** Include `apartment_number` field (from vacation_apartments table)

**Expected Response Structure:**
```json
{
  "error": false,
  "data": {
    "reservations": {
      "data": [
        {
          "id": 789,
          "property_id": 123,
          "reservable_id": 123,
          "reservable_type": "App\\Models\\Property",
          "check_in_date": "2025-01-15",
          "check_out_date": "2025-01-20",
          "number_of_guests": 4,
          "total_price": 750,
          "status": "confirmed",
          "payment_status": "paid",
          "payment_method": "paymob",
          "customer_id": 67,
          "apartment_id": 1,
          "apartment_quantity": 2,
          "apartment_number": "A101",
          "customer": {
            "id": 67,
            "name": "John Doe",
            "email": "john@example.com",
            "mobile": "+201234567890"
          },
          "reservable": {
            "id": 123,
            "title": "Beachfront Villa",
            "property_classification": 4
          },
          "property_details": {
            "id": 123,
            "title": "Beachfront Villa",
            "property_classification": 4
          },
          "created_at": "2025-01-10T10:00:00Z",
          "updated_at": "2025-01-10T10:00:00Z"
        }
      ],
      "total": 1,
      "per_page": 100,
      "current_page": 1
    }
  }
}
```

**Key Fields Required for Vacation Homes:**
- ✅ `apartment_id` - **REQUIRED** for vacation home reservations
- ✅ `apartment_quantity` - **REQUIRED** for vacation home reservations (number of units booked)
- ✅ `apartment_number` - **RECOMMENDED** - Apartment identifier for display
- ✅ `reservable_type` - Must be `"App\\Models\\Property"` for vacation homes
- ✅ `property_id` - Property ID
- ✅ `check_in_date` - Check-in date
- ✅ `check_out_date` - Check-out date
- ✅ `status` - Reservation status (e.g., "confirmed", "pending", "cancelled")
- ✅ `customer` or `user` - Customer information object

**Filtering Logic (Frontend Handles):**
- Frontend filters by `reservable_type === "App\\Models\\Property"`
- Frontend filters by `property_id` to show only reservations for selected property
- Frontend filters by `property_classification === 4` (from nested objects)

---

## 🔍 Database Requirements

### **Properties Table**
```sql
-- Required fields for vacation homes management
SELECT 
  id,
  user_id,              -- MUST match authenticated user
  title,
  title_ar,
  property_classification,  -- MUST be 4 for vacation homes
  address,
  city,
  state,
  status,              -- 1 = active, 0 = inactive
  title_image,
  total_click
FROM properties
WHERE user_id = {authenticated_user_id}
  AND property_classification = 4;
```

### **Vacation Apartments Table**
```sql
-- Required fields for vacation homes management
SELECT 
  id,
  property_id,
  apartment_number,
  price_per_night,
  quantity,            -- MUST be integer (default: 1)
  max_guests,
  bedrooms,
  bathrooms,
  discount_percentage,
  status,
  availability_type,
  available_dates
FROM vacation_apartments
WHERE property_id IN (
  SELECT id FROM properties 
  WHERE user_id = {authenticated_user_id}
    AND property_classification = 4
);
```

### **Reservations Table**
```sql
-- Required fields for vacation homes bookings
SELECT 
  r.id,
  r.property_id,
  r.reservable_id,
  r.reservable_type,      -- MUST be "App\Models\Property" for vacation homes
  r.check_in_date,
  r.check_out_date,
  r.number_of_guests,
  r.total_price,
  r.status,
  r.payment_status,
  r.payment_method,
  r.customer_id,
  r.apartment_id,         -- REQUIRED for vacation homes
  r.apartment_quantity,    -- REQUIRED for vacation homes (number of units)
  r.created_at,
  r.updated_at
FROM reservations r
INNER JOIN properties p ON p.id = r.property_id
WHERE p.user_id = {owner_id}
  AND r.reservable_type = 'App\\Models\\Property'
  AND p.property_classification = 4;
```

**Note:** If `apartment_id` and `apartment_quantity` are not yet in the `reservations` table, they may be stored in `special_requests` field. In that case, backend should parse `special_requests` to extract this information.

---

## 📝 Backend Implementation Checklist

### **For `/api/get-added-properties` Endpoint:**

- [ ] **Authentication:** Verify JWT token and extract `user_id`
- [ ] **User Filtering:** Filter properties by `user_id = authenticated_user_id`
- [ ] **Include Vacation Apartments:** Join with `vacation_apartments` table
- [ ] **Return Structure:** Return array in `data` field
- [ ] **Empty Arrays:** Return `vacation_apartments: []` if no apartments exist
- [ ] **Field Names:** Use `vacation_apartments` (snake_case) or `vacationApartments` (camelCase) consistently
- [ ] **Quantity Field:** Ensure `quantity` is integer (not string)
- [ ] **Pagination:** Support `offset` and `limit` parameters

**Example Backend Query (Laravel/PHP):**
```php
public function getAddedProperties(Request $request)
{
    $user = auth()->user();
    
    $offset = $request->input('offset', 0);
    $limit = $request->input('limit', 100);
    
    $properties = Property::where('user_id', $user->id)
        ->with(['vacationApartments' => function($query) {
            $query->orderBy('apartment_number');
        }])
        ->offset($offset)
        ->limit($limit)
        ->get();
    
    // Ensure vacation_apartments is always an array (even if empty)
    $properties->each(function($property) {
        if (!$property->relationLoaded('vacationApartments')) {
            $property->setRelation('vacationApartments', collect([]));
        }
    });
    
    return response()->json([
        'error' => false,
        'data' => $properties
    ]);
}
```

**Alternative Implementation (If relationship doesn't exist):**
```php
public function getAddedProperties(Request $request)
{
    $user = auth()->user();
    
    $offset = $request->input('offset', 0);
    $limit = $request->input('limit', 100);
    
    $properties = Property::where('user_id', $user->id)
        ->offset($offset)
        ->limit($limit)
        ->get();
    
    // Manually load vacation apartments
    $propertyIds = $properties->pluck('id');
    $apartments = VacationApartment::whereIn('property_id', $propertyIds)
        ->get()
        ->groupBy('property_id');
    
    // Attach apartments to properties
    $properties->each(function($property) use ($apartments) {
        $property->vacation_apartments = $apartments->get($property->id, collect([]))->values();
    });
    
    return response()->json([
        'error' => false,
        'data' => $properties
    ]);
}
```

---

### **For `/api/property-owner-reservations/{ownerId}` Endpoint:**

- [ ] **Authentication:** Verify JWT token and verify `ownerId` matches authenticated user
- [ ] **Include Apartment Fields:** Join with `vacation_apartments` table to get `apartment_number`
- [ ] **Return Structure:** Include `apartment_id` and `apartment_quantity` in response
- [ ] **Filter by Property:** Support filtering by `property_id` (frontend handles this, but backend can optimize)
- [ ] **Pagination:** Support `per_page` and `page` parameters
- [ ] **Parse special_requests:** If `apartment_id` not in table, parse from `special_requests` field

**Example Backend Query (Laravel/PHP):**
```php
public function getOwnerReservations(Request $request, $ownerId)
{
    // Verify owner ID matches authenticated user
    $user = auth()->user();
    if ($user->id != $ownerId) {
        return response()->json([
            'error' => true,
            'message' => 'Unauthorized'
        ], 403);
    }
    
    $perPage = $request->input('per_page', 100);
    $page = $request->input('page', 1);
    
    $reservations = Reservation::whereHas('property', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        })
        ->with(['customer', 'property'])
        ->where('reservable_type', 'App\\Models\\Property')
        ->paginate($perPage, ['*'], 'page', $page);
    
    // Add apartment information to each reservation
    $reservations->getCollection()->transform(function($reservation) {
        // If apartment_id exists in table, use it
        if (isset($reservation->apartment_id) && $reservation->apartment_id) {
            $apartment = VacationApartment::find($reservation->apartment_id);
            if ($apartment) {
                $reservation->apartment_number = $apartment->apartment_number;
            }
        } else {
            // Parse from special_requests if apartment_id not in table
            $specialRequests = $reservation->special_requests ?? '';
            if (preg_match('/Apartment ID:\s*(\d+)/', $specialRequests, $matches)) {
                $apartmentId = (int)$matches[1];
                $reservation->apartment_id = $apartmentId;
                
                $apartment = VacationApartment::find($apartmentId);
                if ($apartment) {
                    $reservation->apartment_number = $apartment->apartment_number;
                }
            }
            
            // Extract quantity from special_requests
            if (preg_match('/Quantity:\s*(\d+)/', $specialRequests, $matches)) {
                $reservation->apartment_quantity = (int)$matches[1];
            } else {
                $reservation->apartment_quantity = 1; // Default
            }
        }
        
        return $reservation;
    });
    
    return response()->json([
        'error' => false,
        'data' => [
            'reservations' => $reservations
        ]
    ]);
}
```

**If `apartment_id` and `apartment_quantity` columns exist in reservations table:**
```php
public function getOwnerReservations(Request $request, $ownerId)
{
    $user = auth()->user();
    if ($user->id != $ownerId) {
        return response()->json(['error' => true, 'message' => 'Unauthorized'], 403);
    }
    
    $perPage = $request->input('per_page', 100);
    $page = $request->input('page', 1);
    
    $reservations = Reservation::whereHas('property', function($query) use ($ownerId) {
            $query->where('user_id', $ownerId);
        })
        ->with(['customer', 'property', 'vacationApartment']) // If relationship exists
        ->where('reservable_type', 'App\\Models\\Property')
        ->paginate($perPage, ['*'], 'page', $page);
    
    // Add apartment_number from relationship
    $reservations->getCollection()->transform(function($reservation) {
        if ($reservation->apartment_id && $reservation->vacationApartment) {
            $reservation->apartment_number = $reservation->vacationApartment->apartment_number;
        }
        return $reservation;
    });
    
    return response()->json([
        'error' => false,
        'data' => [
            'reservations' => $reservations
        ]
    ]);
}
```

---

## 🚫 APIs NOT Required (Frontend Handles)

The following functionality is handled entirely by the frontend and **does NOT require new backend endpoints**:

1. ✅ **Filtering by Property Classification** - Frontend filters `property_classification === 4`
2. ✅ **Filtering Reservations by Property** - Frontend filters by `property_id`
3. ✅ **Calculating Availability** - Frontend calculates from reservations data
4. ✅ **Date Range Filtering** - Frontend filters reservations by date
5. ✅ **Payment Method Filtering** - Frontend filters by `payment_method`
6. ✅ **Month Filtering** - Frontend filters by month

---

## 🔧 Database Schema Check

### **Check if `apartment_id` and `apartment_quantity` exist in reservations table:**

```sql
-- Check if columns exist
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'reservations' 
  AND COLUMN_NAME IN ('apartment_id', 'apartment_quantity');
```

**If columns don't exist, you have two options:**

#### **Option 1: Add columns (Recommended)**
```sql
ALTER TABLE reservations 
ADD COLUMN apartment_id INT NULL AFTER property_id,
ADD COLUMN apartment_quantity INT DEFAULT 1 AFTER apartment_id,
ADD FOREIGN KEY (apartment_id) REFERENCES vacation_apartments(id) ON DELETE SET NULL;
```

#### **Option 2: Parse from `special_requests` (Temporary)**
- Parse `special_requests` field to extract `apartment_id` and `apartment_quantity`
- Use regex: `/Apartment ID:\s*(\d+)/` and `/Quantity:\s*(\d+)/`
- This is less efficient but works without schema changes

---

## 🧪 Testing Checklist

### **Test 1: Get Added Properties API**
```bash
# Test with authenticated user
curl -X GET "https://your-api.com/api/get-added-properties?offset=0&limit=100" \
  -H "Authorization: Bearer {token}"

# Expected: Returns properties with vacation_apartments array
# Verify:
# - Only returns properties where user_id matches authenticated user
# - Each property includes vacation_apartments array (even if empty)
# - quantity field is integer, not string
```

### **Test 2: Get Owner Reservations API**
```bash
# Test with owner ID
curl -X GET "https://your-api.com/api/property-owner-reservations/45?per_page=100&page=1" \
  -H "Authorization: Bearer {token}"

# Expected: Returns reservations with apartment_id and apartment_quantity
# Verify:
# - Only returns reservations for properties owned by user_id = 45
# - Each vacation home reservation includes apartment_id
# - Each vacation home reservation includes apartment_quantity
# - apartment_number is included for display
```

### **Test 3: Database Verification**
```sql
-- Verify user has vacation homes
SELECT COUNT(*) FROM properties 
WHERE user_id = 45 AND property_classification = 4;

-- Verify vacation apartments exist
SELECT COUNT(*) FROM vacation_apartments va
INNER JOIN properties p ON p.id = va.property_id
WHERE p.user_id = 45 AND p.property_classification = 4;

-- Verify reservations include apartment fields
SELECT COUNT(*) FROM reservations r
INNER JOIN properties p ON p.id = r.property_id
WHERE p.user_id = 45 
  AND r.reservable_type = 'App\\Models\\Property'
  AND (r.apartment_id IS NOT NULL OR r.special_requests LIKE '%Apartment ID%');
```

### **Test 4: Edge Cases**
- [ ] User with no vacation homes returns empty array
- [ ] Property with no apartments returns `vacation_apartments: []`
- [ ] Reservation without apartment info handles gracefully
- [ ] Pagination works correctly
- [ ] Authentication fails for wrong user_id

---

## 📊 Summary

### **APIs That Need Modifications:**
1. ✅ **`GET /api/get-added-properties`**
   - Must filter by authenticated user's `user_id`
   - Must include `vacation_apartments` array in response
   - Must return empty array `[]` if no apartments exist

2. ✅ **`GET /api/property-owner-reservations/{ownerId}`**
   - Must include `apartment_id` in response
   - Must include `apartment_quantity` in response
   - Should include `apartment_number` for display
   - May need to parse from `special_requests` if columns don't exist

### **APIs That Are Fine As-Is:**
- ✅ No new endpoints required
- ✅ All existing endpoints can be used with minor modifications

### **Frontend Handles:**
- ✅ Filtering by property classification
- ✅ Filtering by property ID
- ✅ Calculating availability
- ✅ Date/month/payment filtering
- ✅ Display logic

### **Database Considerations:**
- ⚠️ Check if `apartment_id` and `apartment_quantity` columns exist in `reservations` table
- ⚠️ If not, either add columns or parse from `special_requests` field

---

## 🎯 Priority

**HIGH PRIORITY:**
- ✅ Ensure `get-added-properties` includes `vacation_apartments`
- ✅ Ensure `property-owner-reservations` includes `apartment_id` and `apartment_quantity`
- ✅ Filter properties by authenticated user's `user_id`

**MEDIUM PRIORITY:**
- ✅ Include `apartment_number` in reservations response for better display
- ✅ Add `apartment_id` and `apartment_quantity` columns to `reservations` table (if not exist)

**LOW PRIORITY:**
- ✅ Consistent field naming (snake_case vs camelCase)
- ✅ Optimize queries with proper indexes

---

## 📞 Questions to Clarify

1. **Do `apartment_id` and `apartment_quantity` columns exist in `reservations` table?**
   - If yes: Use direct columns
   - If no: Parse from `special_requests` or add columns

2. **Does the `Property` model have a `vacationApartments` relationship?**
   - If yes: Use eager loading
   - If no: Manually join and attach

3. **What is the exact field name convention?**
   - `vacation_apartments` (snake_case) or `vacationApartments` (camelCase)?

---

**Last Updated:** [Current Date]
**Status:** ⚠️ Backend Modifications Required (Minor)
**Frontend Status:** ✅ Ready - Awaiting Backend Updates
**Estimated Implementation Time:** 2-4 hours

