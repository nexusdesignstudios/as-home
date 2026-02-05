# Backend Integration Instructions for Flutter Developer

## Overview
This document outlines the backend structure, API endpoints, and business logic required to integrate the "As Home" mobile app with the backend.

## 1. Property Structure & Classification
The backend uses a single `propertys` table but distinguishes types via `property_classification`:
- **Hotels (`property_classification = 5`)**:
  - Have associated `HotelRoom` models (1-to-many).
  - Availability and pricing are defined at the *room* level.
  - Search should use the specialized `searchHotelsWithDates` endpoint.
- **Vacation Homes (`property_classification = 4`)**:
  - Have associated `VacationApartment` models (1-to-many).
  - A single "Property" (e.g., "Sunshine Building") contains multiple "Apartments" (e.g., "Unit 101", "Unit 102").
  - The `get-property-list` endpoint automatically "expands" these into individual listings.
- **Regular Properties (Rent/Sell)**:
  - Single unit properties.
  - Attributes (bedrooms, bathrooms) are stored in `assign_parameters` table.

## 2. API Endpoints

### A. General Property Search
**Endpoint:** `GET /get-property-list`
**Used For:** Vacation homes, rental apartments, properties for sale.
**Key Parameters:**
- `offset`, `limit`: Pagination.
- `check_in_date`, `check_out_date`: Format `YYYY-MM-DD`.
- `bedrooms`, `bathrooms`: Integer filters.
- `min_price`, `max_price`: Range filter.
- `category_slug_id`: Filter by category (e.g., "vacation-homes").
- `search`: Text search (title, address).

**Important Logic:**
- **Vacation Home Expansion:** The API returns "expanded" results. A single backend Property with 5 apartments will return **5 separate items** in the `data` array.
  - Each item has `is_apartment = true`.
  - `apartment_id` field identifies the specific unit.
  - `parent_property_id` links back to the main building.
  - `title` is appended with the apartment number.
- **Bedroom/Bathroom Filter:**
  - For **Vacation Homes**: Filters against `vacation_apartments` table.
  - For **Regular Properties**: Filters against `assign_parameters` (polymorphic relation).

### B. Hotel Search
**Endpoint:** `GET /search-hotels-with-dates`
**Used For:** Hotel booking flows.
**Key Parameters:**
- `check_in_date`, `check_out_date` (Required).
- `latitude`, `longitude`, `radius`: Geo-search.
- `min_price`, `max_price`.
- `city`, `cityVariations[]`: Location filter.

**Important Logic:**
- **Availability Check:**
  - Checks individual `HotelRoom` availability.
  - **Gaps are Available:** If `available_dates` has gaps (e.g., available Jan 1-5 and Jan 10-15), the gap (Jan 6-9) is treated as **available** (standard hotel logic).
  - Checks for confirmed reservations overlap.
- **Pricing:**
  - Filters based on `price_per_night` of the hotel rooms, not the base property price.

### C. Reservation Creation
**Endpoint:** `POST /create-reservation`
**Payload:**
```json
{
  "reservable_type": "property" | "hotel_room",
  "property_id": 123,
  "check_in_date": "2024-01-01",
  "check_out_date": "2024-01-05",
  "number_of_guests": 2,
  // For Vacation Homes:
  "apartment_id": 456,
  "apartment_quantity": 1,
  // For Hotels:
  "room_id": 789,
  "room_quantity": 1
}
```

## 3. Data Models & Relationships

### Property (`App\Models\Property`)
- **Relationships:**
  - `hotelRooms`: HasMany `HotelRoom` (for classification 5).
  - `vacationApartments`: HasMany `VacationApartment` (for classification 4).
  - `assignParameter`: MorphMany `AssignParameters` (attributes like AC, Wifi, etc.).
  - `category`: BelongsTo `Category`.

### Reservation (`App\Models\Reservation`)
- **Polymorphic Relation:** `reservable` can be `Property` or `HotelRoom`.
- **Status:** `pending`, `confirmed`, `cancelled`.
- **Date Logic:**
  - Check-in is inclusive.
  - Check-out is exclusive (e.g., leaving on the 5th means the room is free for a new guest on the 5th).

## 4. Mobile App Specifics
- **Map View:** When implementing map view, be aware that expanded vacation apartments will have the **same latitude/longitude** (parent property location). You may need to cluster them.
- **Images:** Base URLs are constructed using `config('global.IMG_PATH')`. Ensure the app handles relative paths correctly by prepending the base domain.
- **Filters:**
  - The "Studio" filter (bedrooms=0) explicitly excludes Vacation Homes (`property_classification != 4`).
  - Star ratings for hotels are filtered via Category names (e.g., "5 Star"), not a numeric column.

## 5. Web vs Mobile Data Discrepancies
**Crucial for Data Consistency:**

- **Source of Truth for Mobile:** The function `get_property_details` in `app/Helpers/custom_helper.php` dictates the JSON structure for the Mobile App.
- **Admin Panel (Web) vs Mobile App:**
  - **List View:**
    - **Admin Panel (`PropertController::getPropertyList`)**: Returns a "flat" list of properties. It does *not* nest `hotel_rooms` or `vacation_apartments` in the JSON response. It focuses on Admin actions (Edit/Delete).
    - **Mobile App (`ApiController::get_property`)**: Returns a deep nested structure. It includes `hotel_rooms` (for hotels) and expands `vacation_apartments` (for vacation homes) so the user can see unit-level details immediately.
  - **Detail View:**
    - **Admin Panel (`PropertController::edit`)**: Loads the full model with relationships into the View. The data exists in the backend but is rendered server-side.
    - **Mobile App**: Receives full details via API.

**Recommendation:**
- If building a **User-Facing Web Client** (e.g., a React/Vue website for customers), **do not use** `PropertController` endpoints. They are tailored for the Admin Dashboard.
- Instead, use the **same `ApiController` endpoints** (`get-property-list`, `search-hotels-with-dates`) used by the Flutter App. This ensures that the Website and App show identical data, availability, and pricing.

## 6. Testing & Validation
- **Availability Testing:**
  - Use `GET /test-hotel-room-availability` to debug hotel availability ranges.
- **Date Formats:** Always use `YYYY-MM-DD`. Timestamps are handled in the application timezone (default UTC).
