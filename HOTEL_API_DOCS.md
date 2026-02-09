# Hotel API Documentation for Mobile App (Flutter)

This documentation outlines the API endpoints required for implementing hotel reservations in the mobile app, including guest limit validation and booking type selection (Flexible/Non-refundable).

## Base URL
`https://your-domain.com/api`

## Authentication
All reservation creation endpoints require a valid Bearer Token (Sanctum).
`Authorization: Bearer <your_access_token>`

---

## 1. Search Available Rooms
Get a list of available rooms for a property within a date range. This endpoint provides the room details including guest limits (`min_guests`, `max_guests`) needed for UI validation.

**Endpoint:** `GET /search-available-rooms`

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `property_id` | int | Yes | The ID of the hotel property |
| `from_date` | string | Yes | Check-in date (YYYY-MM-DD) |
| `to_date` | string | Yes | Check-out date (YYYY-MM-DD) |
| `room_type_id` | int | No | Filter by specific room type |

**Response Example:**
```json
[
  {
    "id": 978,
    "property_id": 517,
    "room_type_id": 19,
    "price_per_night": 1600,
    "min_guests": 2,          // <--- Minimum guests allowed
    "max_guests": 4,          // <--- Maximum guests allowed
    "refund_policy": "flexible",
    "nonrefundable_percentage": 90,
    "available_rooms": 5,
    "description": "Ocean View Suite",
    "room_type": {
      "id": 19,
      "name": "Double Bedroom Suite"
    }
    // ... other fields
  }
]
```

**UI Logic:**
- Use `min_guests` and `max_guests` to limit the number of guests the user can select in the UI.
- If `refund_policy` is "flexible", show options to the user to choose between "Flexible" (free cancellation usually) or "Non-refundable" (often cheaper or specific terms).

---

## 2. Create Reservation (Pay Later / Manual)
Create a reservation without initiating an online payment immediately.

**Endpoint:** `POST /reservations`
**Headers:** `Authorization: Bearer <token>`

**Request Body:**
```json
{
  "property_id": 517,
  "reservable_type": "hotel_room",
  "check_in_date": "2026-02-15",
  "check_out_date": "2026-02-20",
  "number_of_guests": 2,            // <--- Must be between min_guests and max_guests
  "booking_type": "flexible",       // <--- Options: "flexible" or "non_refundable"
  "reservable_id": [
    {
      "id": 978,                    // <--- Room ID
      "amount": 1600                // <--- Price per night or total (backend calculates total usually, but pass expected amount)
    }
  ]
}
```

**Validation Errors (400 Bad Request):**
If `number_of_guests` violates the room's limits:
```json
{
  "status": false,
  "message": "Room 978 requires minimum 2 guests",
  "code": 400
}
```

---

## 3. Create Reservation with Payment (PayPal/Paymob)
Create a reservation and immediately generate a payment link.

**Endpoint:** `POST /reservations/with-payment`
**Headers:** `Authorization: Bearer <token>`

**Request Body:**
Same as standard reservation, plus `payment_gateway`.

```json
{
  "property_id": 517,
  "reservable_type": "hotel_room",
  "check_in_date": "2026-02-15",
  "check_out_date": "2026-02-20",
  "number_of_guests": 3,
  "booking_type": "non_refundable", // <--- User selected non-refundable rate
  "payment_gateway": "paymob",      // <--- Options: "paymob" (Default) or "paypal"
  "reservable_id": [
    {
      "id": 978,
      "amount": 1600
    }
  ]
}
```

**Note on Payment Gateway:**
- `payment_gateway`: (Optional) Specifies the payment provider.
  - **Values**: `paymob`, `paypal`
  - **Default**: `paymob` (If this field is omitted, the system defaults to Paymob).

**Response (Success - PayPal):**
```json
{
  "payment_url": "https://www.sandbox.paypal.com/checkoutnow?token=1DB5534745685115N", // <--- Redirect user here
  "transaction_id": "RES_1770639410_65_3005",
  "code": 200
}
```

**Payment Flow:**
1. Call this endpoint.
2. Open `payment_url` in a WebView or browser.
3. User completes payment.
4. User is redirected to the app's return URL (or deep link if configured).
5. Backend receives IPN/Webhook and updates reservation status to `paid`.

---

## Summary of New Features for App Team

1.  **Guest Limits**:
    *   **Field**: `number_of_guests` in POST request.
    *   **Validation**: Backend checks against `min_guests` and `max_guests` of the room.
    *   **Error**: Returns descriptive error message if invalid.

2.  **Booking Type**:
    *   **Field**: `booking_type` in POST request.
    *   **Values**: `flexible` or `non_refundable`.
    *   **Logic**: Overrides the default property policy if provided.

3.  **Payment Integration**:
    *   Use `/reservations/with-payment` endpoint.
    *   Supports `paypal` gateway.
    *   Returns a direct `payment_url`.
