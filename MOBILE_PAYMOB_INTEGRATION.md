# Mobile Paymob Integration & Localization Guide

This document outlines the Paymob payment integration flow for the mobile application and provides guidelines for handling Arabic localization for backend-driven content.

## 1. Paymob Payment Integration

### Overview
The mobile application initiates payments by calling the backend API to create a payment intent. The backend interacts with Paymob to generate a `payment_key` which is then returned to the mobile app. The mobile app uses this key with the native Paymob SDK to process the payment.

### Endpoint
**URL:** `POST /api/create-paymob-payment`
**Auth:** Bearer Token (Sanctum)

### Request Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `reservable_id` | Integer | Yes | ID of the item being booked (e.g., Hotel Room ID or Property ID) |
| `reservable_type` | String | Yes | Type of reservation: `"hotel_room"` or `"property"` |
| `check_in_date` | String | Yes | Format: `YYYY-MM-DD` |
| `check_out_date` | String | Yes | Format: `YYYY-MM-DD` |
| `amount` | Float | Yes | Total amount to be charged (e.g., `150.00`) |
| `first_name` | String | Yes | Customer's first name |
| `last_name` | String | Yes | Customer's last name |
| `email` | String | Yes | Customer's email address |
| `phone` | String | Yes | Customer's phone number |
| `number_of_guests` | Integer | Optional | Number of guests |
| `special_requests` | String | Optional | Any special requests |

#### Example Request Body
```json
{
  "amount": 1250.00,
  "email": "customer@example.com",
  "first_name": "Ahmed",
  "last_name": "Mohamed",
  "phone": "01012345678",
  "reservable_id": 105,
  "reservable_type": "hotel_room",
  "check_in_date": "2025-05-20",
  "check_out_date": "2025-05-25",
  "number_of_guests": 2
}
```

### Response Structure
The API returns a JSON object containing the `mobile_payment` object, which holds the critical `payment_key` required for the SDK.

#### Example Success Response
```json
{
  "error": false,
  "message": "Payment intent created successfully",
  "data": {
    "payment_intent": {
      "id": "123456",
      "amount": 1250.0,
      "currency": "EGP",
      "status": "pending"
    },
    "transaction_id": "RES_1715612345_105",
    "reservation_id": 889,
    "mobile_payment": {
      "order_id": "123456789",
      "payment_key": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...", 
      "integration_id": "12345",
      "amount_cents": 125000,
      "currency": "EGP",
      "callback_url": "https://dashboard.as-home.com/api/payments/paymob/callback",
      "return_url": "https://dashboard.as-home.com/payments/paymob/return"
    }
  }
}
```

### Mobile SDK Implementation Steps
1. **Call API**: Send the request to `/api/create-paymob-payment`.
2. **Extract Key**: Retrieve `data.mobile_payment.payment_key` from the response.
3. **Initialize SDK**: Use the `payment_key` to launch the Paymob payment sheet (Card or Wallet).
   - **Android/iOS**: Pass the key to the `Intent` or `ViewController` provided by the Paymob SDK.
4. **Handle Result**: 
   - Upon success, Paymob will automatically trigger the `callback_url` on the backend to confirm the reservation.
   - The mobile app can redirect the user to a success screen or poll the reservation status if needed.

---

## 2. Arabic Localization (AR APIs)

The backend provides bilingual content for dynamic data such as properties, projects, and room details. The mobile app is responsible for selecting the appropriate language field based on the user's device settings.

### Logic
- **Check Language**: Determine if the app's current locale is Arabic (`ar`).
- **Select Field**: 
  - If **Arabic**, use the `*_ar` field (e.g., `title_ar`).
  - If the `*_ar` field is `null` or empty, **fallback** to the English field (e.g., `title`).
  - If **English/Other**, use the standard English field.

### API Fields with Arabic Support
The following objects and fields are returned by API endpoints (e.g., Property Details, Search Results, Hotel Rooms) with dedicated Arabic values:

#### 1. Property / Listing Objects
| English Field | Arabic Field | Description |
|---------------|--------------|-------------|
| `title` | `title_ar` | The main headline of the property listing. |
| `description` | `description_ar` | The detailed full text description. |
| `area_description` | `area_description_ar` | Description of the neighborhood or area. |

#### 2. Projects
| English Field | Arabic Field | Description |
|---------------|--------------|-------------|
| `title` | `title_ar` | Project name. |
| `description` | `description_ar` | Project details. |
| `area_description` | `area_description_ar` | Project location details. |

#### 3. Hotel Rooms
| English Field | Arabic Field | Description |
|---------------|--------------|-------------|
| `description` | `description` | *Note: Currently, hotel rooms might share the description field or use a specific structure. Check specific response. Typically follows the same pattern if added.* |

### Example JSON Response (Property)
```json
{
  "id": 204,
  "price": 5000,
  "title": "Luxury Villa with Sea View",
  "title_ar": "فيلا فاخرة تطل على البحر",
  "description": "A beautiful 5-bedroom villa...",
  "description_ar": "فيلا جميلة مكونة من 5 غرف نوم...",
  "area_description": "Located in the heart of El Gouna...",
  "area_description_ar": "تقع في قلب الجونة...",
  "city": "Hurghada",
  "state": "Red Sea"
}
```

### Static vs. Dynamic Content
- **Dynamic (Backend)**: Use the `_ar` fields listed above for content created by users/admins.
- **Static (App UI)**: Use the mobile app's internal localization files (e.g., `assets/languages/ai-ar.json`) for labels like "Check-in", "Guests", "Pay Now", and city names (e.g., "Hurghada" -> "الغردقة").
