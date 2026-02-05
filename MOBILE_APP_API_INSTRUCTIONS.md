# Mobile App API Integration Guide

This guide provides instructions for integrating the mobile app with the backend APIs for Hotel Listings, Room Details, Reservations, and Payments (Paymob).

## 1. Hotel Listing & Search

To display a list of hotels with availability, use the following endpoints.

### **Search Hotels with Dates**
*   **Endpoint:** `GET /api/search-hotels-with-dates`
*   **Description:** Returns a list of properties (hotels) available for the selected dates.
*   **Parameters:**
    *   `check_in_date` (Required, YYYY-MM-DD)
    *   `check_out_date` (Required, YYYY-MM-DD)
    *   `city_id` (Optional)
    *   `guests` (Optional, number of guests)

### **Get All Properties**
*   **Endpoint:** `GET /api/get-property-list`
*   **Description:** Returns a paginated list of all active properties.
*   **Parameters:**
    *   `offset` (Optional)
    *   `limit` (Optional)

---

## 2. Room Details & Availability

To show the "Hotel Table Booking" and "Simple Dropdown" for rooms.

### **Search Available Rooms**
*   **Endpoint:** `GET /api/search-available-rooms`
*   **Description:** Fetches available rooms for a specific hotel within a date range. This is the primary endpoint for the "Room Preview" screen.
*   **Parameters:**
    *   `property_id` (Required, Integer)
    *   `from_date` (Required, YYYY-MM-DD)
    *   `to_date` (Required, YYYY-MM-DD)
    *   `room_type_id` (Optional, Integer) - To filter by specific room type (e.g., Deluxe, Suite).
*   **Response:** Returns a list of room objects containing:
    *   `id` (Room ID)
    *   `room_number`
    *   `price_per_night`
    *   `room_type` (Object with name, capacity, etc.)
    *   `amenities`

---

## 3. Reservations

### **Check Availability (Pre-check)**
*   **Endpoint:** `POST /api/check-availability`
*   **Description:** Verifies if a room or property is available before attempting to book.
*   **Body:**
    ```json
    {
        "reservable_id": 123, // Room ID or Property ID
        "reservable_type": "hotel_room", // or "property"
        "check_in_date": "2023-12-01",
        "check_out_date": "2023-12-05"
    }
    ```

### **Create Reservation**
*   **Endpoint:** `POST /api/reservations`
*   **Description:** Creates a new reservation booking.
*   **Headers:** `Authorization: Bearer {token}`
*   **Body:**
    ```json
    {
        "customer_id": 1,
        "reservable_id": 123, // Room ID
        "reservable_type": "App\\Models\\HotelRoom", // Note the namespace
        "check_in_date": "2023-12-01",
        "check_out_date": "2023-12-05",
        "number_of_guests": 2,
        "total_price": 500.00,
        "property_id": 10,
        "booking_type": "flexible_booking", // Options: "flexible_booking" or null (standard)
        "special_requests": "Late check-in"
    }
    ```

### **Flexible Booking Logic**
*   **Concept:** If `booking_type` is set to `"flexible_booking"`, the system will **block the dates** immediately upon confirmation, even if the payment status is still `unpaid` or partial.
*   **Usage:** Use this for the "Flexible Booking" option in the app where users might pay later or pay a deposit.

---

## 4. Vacation Homes (New)

For listed vacation homes, the process is slightly different from hotels.

### **Identifying Vacation Homes**
*   Properties with `property_classification` **value 4** are Vacation Homes.
*   They contain "Vacation Apartments" instead of "Hotel Rooms".

### **Get Vacation Home Details**
*   **Endpoint:** `GET /api/get_property`
*   **Parameters:** `id` (Property ID)
*   **Response:** Look for the `vacation_apartments` array in the response object.
    *   Each object contains: `id`, `apartment_number`, `quantity` (total units), `price_per_night`, `max_guests`, `bedrooms`, `bathrooms`.

### **Check Availability (Vacation Home)**
*   **Endpoint:** `POST /api/check-availability`
*   **Body:**
    ```json
    {
        "reservable_id": 789, // Property ID (Vacation Home ID)
        "reservable_type": "property", // MUST be "property"
        "check_in_date": "2023-12-01",
        "check_out_date": "2023-12-05",
        "apartment_id": 45, // ID of the specific apartment type within the home
        "apartment_quantity": 1 // Number of units to book
    }
    ```

### **Create Reservation (Vacation Home)**
*   **Endpoint:** `POST /api/reservations`
*   **Body:**
    ```json
    {
        "customer_id": 1,
        "reservable_id": 789, // Property ID (Vacation Home ID)
        "reservable_type": "App\\Models\\Property", // Note the namespace
        "check_in_date": "2023-12-01",
        "check_out_date": "2023-12-05",
        "number_of_guests": 4,
        "total_price": 1200.00,
        "property_id": 789, // Same as reservable_id
        "apartment_id": 45, // ID of the apartment type
        "apartment_quantity": 1
    }
    ```

---

## 5. Payments (Paymob Integration)

To link reservations correctly via Paymob.

### **Step 1: Create Reservation**
First, create the reservation using the `POST /api/reservations` endpoint (as above) to get the `reservation_id`.

### **Step 2: Initiate Payment**
*   **Endpoint:** `POST /api/create-paymob-payment`
*   **Description:** Generates a payment intent/token for Paymob.
*   **Body:**
    ```json
    {
        "reservation_id": 456, // ID from Step 1
        "amount": 500.00,
        "currency": "EGP",
        "billing_data": {
            "first_name": "John",
            "last_name": "Doe",
            "email": "john@example.com",
            "phone_number": "+201000000000"
        }
    }
    ```
*   **Response:** Returns a `payment_key` or `iframe_url` to display the Paymob payment page to the user.

### **Step 3: Handle Callback**
*   **Endpoint:** `POST /api/payments/paymob/callback`
*   **Description:** Paymob will call this webhook automatically after the payment is processed.
*   **Mobile Action:**
    1.  After the user completes payment in the WebView/SDK, Paymob redirects them to the return URL.
    2.  The backend will handle the status update (setting reservation to `paid`).
    3.  The mobile app should poll the reservation status (`GET /api/reservations/{id}`) or listen for a success response from the WebView.

### **Step 4: Reservation with Payment (Alternative)**
*   **Endpoint:** `POST /api/reservations/with-payment`
*   **Description:** Creates a reservation and initiates payment in a single step.
*   **Body:** Combines fields from Reservation and Payment endpoints.
