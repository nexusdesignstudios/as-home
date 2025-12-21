# Vacation Homes Multi-Unit Functionality & Reservations

## Overview

Vacation Homes (Property Classification = 4) support **multi-unit functionality** through the `VacationApartment` model. This allows a single vacation home property to have multiple apartments/units, each with its own pricing, availability, and booking capabilities.

---

## Database Structure

### Vacation Apartments Table

```sql
vacation_apartments
├── id
├── property_id (FK → propertys.id)
├── apartment_number (string)
├── price_per_night (decimal 10,2)
├── discount_percentage (decimal 5,2, default: 0)
├── description (text, nullable)
├── status (boolean, default: 1)
├── availability_type (tinyint, nullable)
│   └── 1: Available Days
│   └── 2: Busy Days
├── available_dates (json, nullable)
│   └── Array of date ranges: [{from: "YYYY-MM-DD", to: "YYYY-MM-DD", price: float, type: "open|dead|reserved"}]
├── max_guests (integer, nullable)
├── bedrooms (integer, nullable)
├── bathrooms (integer, nullable)
├── quantity (integer, default: 1) -- Number of identical units
└── timestamps
```

---

## Key Relationships

### Property → Vacation Apartments

```php
// Property Model
public function vacationApartments()
{
    return $this->hasMany(VacationApartment::class, 'property_id');
}

// Only returns apartments if property_classification == 4
public function getVacationApartmentsAttribute()
{
    if ($this->getRawOriginal('property_classification') == 4) {
        return $this->vacationApartments()->get();
    }
    return null;
}
```

### Vacation Apartment → Property

```php
// VacationApartment Model
public function property()
{
    return $this->belongsTo(Property::class);
}

// Vacation Apartment → Reservations (Polymorphic)
public function reservations()
{
    return $this->morphMany(Reservation::class, 'reservable');
}
```

---

## How Multi-Unit Works

### 1. **Property Creation/Update**

When creating or updating a vacation home property (classification = 4), you can add multiple apartments:

**API Endpoint:** `POST /api/post-property` or `POST /api/update-post-property`

**Request Body:**
```json
{
  "property_classification": 4,
  "title": "Beachfront Resort",
  "price": 1000,  // Base price (used if no apartment selected)
  "vacation_apartments": [
    {
      "apartment_number": "A101",
      "price_per_night": 150,
      "discount_percentage": 10,
      "availability_type": 1,  // 1: Available Days, 2: Busy Days
      "available_dates": [
        {
          "from": "2025-01-01",
          "to": "2025-12-31",
          "price": 150,
          "type": "open"
        }
      ],
      "max_guests": 4,
      "bedrooms": 2,
      "bathrooms": 1,
      "quantity": 1,
      "description": "Ocean view apartment",
      "status": 1
    },
    {
      "apartment_number": "A102",
      "price_per_night": 200,
      "discount_percentage": 0,
      "availability_type": 1,
      "available_dates": [...],
      "max_guests": 6,
      "bedrooms": 3,
      "bathrooms": 2,
      "quantity": 1,
      "description": "Premium suite",
      "status": 1
    }
  ]
}
```

### 2. **Property Listing**

When listing vacation homes with apartments, the system **expands** them into separate listing items:

**API Endpoint:** `GET /api/get-property-list`

**Behavior:**
- If a vacation home has apartments, each apartment becomes a separate listing item
- Each apartment listing includes:
  - `apartment_id`: The apartment's ID
  - `parent_property_id`: The original property ID
  - `title`: Property title + " - " + apartment number
  - `price`: Apartment's `price_per_night` (not property's base price)
  - `apartment_number`: The apartment number
  - `bedrooms`, `bathrooms`, `max_guests`: From apartment
  - `is_apartment`: true
  - `selected_apartment`: Full apartment data

**Example Response:**
```json
{
  "data": [
    {
      "id": 1,
      "parent_property_id": 1,
      "apartment_id": 5,
      "title": "Beachfront Resort - A101",
      "price": 150,
      "apartment_number": "A101",
      "bedrooms": 2,
      "bathrooms": 1,
      "max_guests": 4,
      "is_apartment": true,
      "selected_apartment": {
        "id": 5,
        "apartment_number": "A101",
        "price_per_night": 150,
        "discount_percentage": 10,
        ...
      }
    },
    {
      "id": 1,
      "parent_property_id": 1,
      "apartment_id": 6,
      "title": "Beachfront Resort - A102",
      "price": 200,
      "apartment_number": "A102",
      "bedrooms": 3,
      "bathrooms": 2,
      "max_guests": 6,
      "is_apartment": true,
      "selected_apartment": {...}
    }
  ]
}
```

---

## How Reservations Work

### 1. **Reservation Creation**

**API Endpoint:** `POST /api/create-reservation`

**Request Body for Vacation Home with Apartment:**
```json
{
  "reservable_type": "property",
  "property_id": 1,
  "reservable_id": 1,  // Property ID
  "check_in_date": "2025-02-01",
  "check_out_date": "2025-02-05",
  "number_of_guests": 4,
  "special_requests": "Late check-in requested",
  "apartment_id": 5,  // ✅ Vacation apartment ID (optional but recommended)
  "apartment_quantity": 1  // Number of units to book (default: 1)
}
```

### 2. **Pricing Calculation**

The reservation price is calculated based on:

**If apartment is selected:**
```php
$pricePerNight = $apartment->price_per_night;
$discount = $apartment->discount_percentage ?? 0;
$discountedPrice = $pricePerNight * (1 - ($discount / 100));
$numberOfDays = $checkIn->diffInDays($checkOut);
$totalPrice = $discountedPrice * $numberOfDays * $apartmentQuantity;
```

**If no apartment selected (uses property base price):**
```php
$totalPrice = $property->price * $numberOfDays;
```

### 3. **Reservation Data Structure**

```php
$reservationData = [
    'customer_id' => Auth::user()->id,
    'reservable_id' => $property->id,  // Property ID (not apartment ID)
    'reservable_type' => 'App\Models\Property',
    'property_id' => $property->id,
    'check_in_date' => $request->check_in_date,
    'check_out_date' => $request->check_out_date,
    'number_of_guests' => $request->number_of_guests ?? 1,
    'total_price' => $totalPrice,  // Calculated based on apartment if selected
    'special_requests' => $request->special_requests . 
        ' Apartment ID: ' . $apartmentId . 
        ', Quantity: ' . $apartmentQuantity,  // Stored in special_requests
    'status' => 'pending',
    'payment_status' => 'unpaid',
];
```

**Important Notes:**
- The `reservable_id` is always the **Property ID**, not the apartment ID
- Apartment information is stored in `special_requests` for traceability
- The reservation is linked to the property, not directly to the apartment

### 4. **Availability Checking**

**API Endpoint:** `POST /api/check-availability`

**Request:**
```json
{
  "reservable_id": 1,  // Property ID
  "reservable_type": "property",
  "check_in_date": "2025-02-01",
  "check_out_date": "2025-02-05"
}
```

**How it works:**
- Checks for overlapping **confirmed** reservations for the property
- Uses `Reservation::datesOverlap()` to check date conflicts
- Only **confirmed** reservations block availability (pending/unpaid do not)

**Note:** Currently, availability checking is done at the **property level**, not per apartment. This means:
- If one apartment is booked, it doesn't block other apartments
- However, the system checks all reservations for the property
- For true per-apartment availability, you'd need to check `special_requests` for apartment IDs

---

## Availability Types

### 1. Available Days (availability_type = 1)

- Property is available **only** on the dates specified in `available_dates`
- Dates not in the list are considered unavailable
- Example: Only available Jan 1-15 and Feb 1-28

### 2. Busy Days (availability_type = 2)

- Property is available **except** on the dates specified in `available_dates`
- Dates in the list are blocked/unavailable
- Example: Available all year except Dec 24-31

---

## Available Dates Structure

```json
[
  {
    "from": "2025-01-01",
    "to": "2025-01-15",
    "price": 150,
    "type": "open"  // "open" | "dead" | "reserved"
  },
  {
    "from": "2025-02-01",
    "to": "2025-02-28",
    "price": 200,  // Different price for this period
    "type": "open"
  }
]
```

**Date Entry Fields:**
- `from`: Start date (YYYY-MM-DD)
- `to`: End date (YYYY-MM-DD)
- `price`: Price for this date range (optional, defaults to `price_per_night`)
- `type`: 
  - `"open"`: Available for booking
  - `"dead"`: Blocked/unavailable
  - `"reserved"`: Reserved (should include `reservation_id`)

---

## Quantity Field

The `quantity` field in `vacation_apartments` represents:
- **Number of identical units** of the same apartment type
- Example: If `quantity = 3` for apartment "A101", there are 3 identical "A101" units
- When booking, `apartment_quantity` specifies how many of these units to reserve

**Pricing with Quantity:**
```php
$totalPrice = $discountedPrice * $numberOfDays * $apartmentQuantity;
```

---

## Key Differences: Vacation Homes vs Hotels

| Feature | Vacation Homes (Classification 4) | Hotels (Classification 5) |
|---------|-----------------------------------|----------------------------|
| **Multi-Unit Model** | `VacationApartment` | `HotelRoom` |
| **Reservation Type** | `reservable_type = "property"` | `reservable_type = "hotel_room"` |
| **Reservable ID** | Property ID | Hotel Room ID (can be array) |
| **Pricing** | Per apartment unit | Per room |
| **Availability** | Property-level checking | Room-level checking |
| **Listing** | Expanded into separate items | Shown as property with rooms |

---

## API Endpoints Summary

### Property Management
- `POST /api/post-property` - Create vacation home with apartments
- `POST /api/update-post-property` - Update vacation home and apartments
- `GET /api/get-property-list` - List properties (expands apartments)

### Reservations
- `POST /api/create-reservation` - Create reservation (include `apartment_id` and `apartment_quantity`)
- `POST /api/check-availability` - Check if dates are available
- `GET /api/get-reservations` - Get user's reservations

### Property Details
- `GET /api/get-property-detail` - Get property details (includes `vacationApartments` if classification = 4)

---

## Code Locations

### Models
- `app/Models/VacationApartment.php` - Vacation apartment model
- `app/Models/Property.php` - Property model (lines 287-316 for vacation apartments)
- `app/Models/Reservation.php` - Reservation model

### Controllers
- `app/Http/Controllers/ApiController.php` - Property CRUD and listing (lines 1494-1522, 2575-2650, 6979-7050)
- `app/Http/Controllers/ReservationController.php` - Reservation creation and availability (lines 348-470)

### Database
- `database/migrations/2025_01_20_000000_create_vacation_apartments_table.php` - Initial table
- `database/migrations/2025_12_20_000001_add_quantity_to_vacation_apartments_table.php` - Quantity field

---

## Important Notes

1. **Reservation Storage**: Reservations are stored with `reservable_id = property_id`, not apartment_id. Apartment info is in `special_requests`.

2. **Availability Checking**: Currently checks at property level. For true per-apartment availability, you'd need to:
   - Parse `special_requests` to extract apartment IDs
   - Check availability per apartment
   - Or modify the reservation system to support apartment-level reservations

3. **Quantity**: The `quantity` field allows multiple identical units. When booking, specify `apartment_quantity` to book multiple units.

4. **Price Priority**: If `apartment_id` is provided, apartment pricing is used. Otherwise, property base price is used.

5. **Listing Expansion**: Vacation homes with apartments are automatically expanded into separate listing items in the property list API.

---

## Example Flow

1. **Admin creates vacation home property** with 3 apartments (A101, A102, A103)
2. **Frontend calls** `GET /api/get-property-list` → Receives 3 separate listing items
3. **User selects** "Beachfront Resort - A101" from the list
4. **User checks availability** for dates Feb 1-5
5. **User creates reservation** with `apartment_id: 5, apartment_quantity: 1`
6. **System calculates price** using apartment's `price_per_night` and `discount_percentage`
7. **Reservation created** with `reservable_id = property_id`, apartment info in `special_requests`
8. **Payment processed** and reservation confirmed

---

**Last Updated:** After reviewing vacation homes multi-unit functionality
**Status:** ✅ Documented

