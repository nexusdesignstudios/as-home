# Reservation Email Types - Vacation Homes vs Flexible Hotel Bookings

This document explains the different email types for vacation home reservations and flexible hotel bookings, including when each email is sent and their templates.

---

## 📧 Email Types Overview

### 1. Vacation Home Reservation Emails
- **Property Classification**: `4` (Vacation Home)
- **Reservable Type**: `App\Models\Property`
- **Two scenarios**: Approval and Decline

### 2. Flexible Hotel Booking Emails
- **Property Classification**: `5` (Hotel Booking)
- **Reservable Type**: `App\Models\Property` or `App\Models\HotelRoom`
- **Payment Method**: `cash` or `flexible` (manual/offline payment)
- **Instant Booking**: `false` (requires approval)

---

## 🏠 Vacation Home Reservation Emails

### Email 1: Vacation Home Reservation Approval

**Template Type**: `reservation_approval`  
**Template Setting**: `reservation_approval_mail_template`  
**Email Title**: "Reservation Approval"

**When Sent:**
- Property classification = `4` (Vacation Home)
- Reservation status changes to `approved`
- Admin/owner approves the reservation request

**Trigger Location:**
- `ReservationController::updateStatus()` - when status = 'approved'
- `ReservationsAdminController::updateStatus()` - when status = 'approved'
- `ReservationService::sendReservationApprovalEmail()`

**Current Email Template:**
```
Dear {user_name},

Your reservation has been approved!

Reservation Details:
- Reservation ID: {reservation_id}
- Property: {property_name}
- Check-in Date: {check_in_date}
- Check-out Date: {check_out_date}
- Number of Guests: {number_of_guests}
- Total Price: {currency_symbol}{total_price}
- Payment Status: {payment_status}
- Special Requests: {special_requests}

Thank you for choosing our service!

Best regards,
The Team
```

**Available Variables:**
- `{app_name}`
- `{user_name}`
- `{reservation_id}`
- `{property_name}`
- `{check_in_date}`
- `{check_out_date}`
- `{number_of_guests}`
- `{total_price}`
- `{currency_symbol}`
- `{payment_status}`
- `{transaction_id}`
- `{special_requests}`

---

### Email 2: Vacation Home Reservation Decline/Rejection

**Template Type**: `reservation_rejection` or `reservation_decline`  
**Template Setting**: `reservation_rejection_mail_template` or `reservation_decline_mail_template`  
**Email Title**: "Reservation Rejection" or "Your Booking Request Has Been Declined"

**When Sent:**
- Property classification = `4` (Vacation Home)
- Reservation status changes to `rejected`
- Admin/owner declines the reservation request

**Trigger Location:**
- `ReservationController::updateStatus()` - when status = 'rejected'
- `ReservationController::sendReservationCancellationEmail()` - when type = 'decline'

**Current Email Template:**
```
Dear {customer_name},

We regret to inform you that your reservation request has been declined.

Reservation Details:
- Reservation ID: {reservation_id}
- Property: {property_name}
- Check-in Date: {check_in_date}
- Check-out Date: {check_out_date}
- Number of Guests: {number_of_guests}
- Total Amount: {currency_symbol}{total_price}

Reason for Rejection:
{rejection_reason}

We understand this may be disappointing, and we apologize for any inconvenience this may cause. Our team has carefully reviewed your reservation request and unfortunately, we are unable to accommodate it at this time.

If you have any questions or would like to discuss alternative options, please do not hesitate to contact our customer support team.

We value your interest in our properties and hope to have the opportunity to serve you in the future.

Best regards,
The {app_name} Team
```

**Available Variables:**
- `{app_name}`
- `{customer_name}`
- `{reservation_id}`
- `{property_name}`
- `{check_in_date}`
- `{check_out_date}`
- `{number_of_guests}`
- `{total_price}`
- `{currency_symbol}`
- `{rejection_reason}`

---

## 🏨 Flexible Hotel Booking Emails

### Email: Flexible Hotel Booking Confirmation

**Template Type**: `flexible_hotel_booking_approval`  
**Template Setting**: `flexible_hotel_booking_approval_mail_template`  
**Email Title**: "Flexible Hotel Booking Confirmation"

**When Sent:**
- Property classification = `5` (Hotel Booking)
- Payment method = `cash` or `flexible` (manual/offline payment)
- `instant_booking` = `false` (requires approval)
- Reservation is created (immediately upon booking, not after approval)

**Trigger Locations:**
1. `ReservationController::createReservation()` - when creating property or hotel room reservation
2. `ReservationController::checkout()` - when payment method is cash/flexible and instant_booking = false
3. `ApiController::submitPaymentForm()` - when payment form is submitted
4. `ReservationService::sendFlexibleHotelBookingApprovalEmail()`

**Current Email Template:**
```
Dear {customer_name},



We are delighted to confirm your booking at {property_name}! 🎉



Below are the full details of your reservation:



Guest Details

• Name: {customer_name}

• Email: {guest_email}

• Phone: {guest_phone}



Property Details

• Property: {property_name}

• Room Type: {room_type}

• Address: {property_address}



Booking Details

• Check-in Date: {check_in_date}

• Check-out Date: {check_out_date}

• Number of Guests: {number_of_guests}

• Total Amount: {currency_symbol}{total_amount}



💳 Payment Information

For flexible bookings, payment can be made on the day of check-in at the hotel or prior to arrival to secure your reservation.



Your reservation has been successfully confirmed. We look forward to welcoming you soon and ensuring you have a comfortable and enjoyable stay.



If you have any questions or need to make changes to your booking, please don't hesitate to contact us at support@as-home-group.com or through your As-home account dashboard.



Warm regards,

As-home Asset Management Team

🌐 www.as-home-group.com
```

**Available Variables:**
- `{app_name}`
- `{customer_name}`
- `{user_name}` (alias for customer_name)
- `{guest_email}`
- `{guest_phone}`
- `{property_name}`
- `{hotel_name}` (alias for property_name)
- `{reservation_id}`
- `{room_type}`
- `{room_number}`
- `{property_address}`
- `{hotel_address}` (alias for property_address)
- `{check_in_date}`
- `{check_out_date}`
- `{number_of_guests}`
- `{total_price}`
- `{total_amount}` (alias for total_price)
- `{currency_symbol}`
- `{payment_status}`
- `{special_requests}`

---

## 🔍 Key Differences

### Vacation Home vs Flexible Hotel Booking

| Aspect | Vacation Home | Flexible Hotel Booking |
|--------|---------------|------------------------|
| **Property Classification** | `4` | `5` |
| **Email Sent On** | After approval/rejection by owner | Immediately upon booking creation |
| **Requires Approval** | Yes (status changes to approved/rejected) | Yes (but email sent immediately, not after approval) |
| **Payment Method** | Any | Cash/Flexible only |
| **Email Purpose** | Notify customer of approval/rejection | Confirm booking and provide details |
| **Template Focus** | Approval/Rejection status | Booking confirmation with full details |

### Vacation Home Approval vs Decline

| Aspect | Approval Email | Decline Email |
|--------|----------------|---------------|
| **Status** | `approved` | `rejected` |
| **Template** | `reservation_approval_mail_template` | `reservation_rejection_mail_template` or `reservation_decline_mail_template` |
| **Tone** | Positive, welcoming | Apologetic, professional |
| **Includes** | Reservation details, payment status | Reservation details, rejection reason |

---

## 📝 Code References

### Vacation Home Approval
- **Service**: `ReservationService::sendReservationApprovalEmail()`
- **Controller**: `ReservationController::updateStatus()` (line 726)
- **Admin Controller**: `ReservationsAdminController::updateStatus()` (line 464)

### Vacation Home Decline
- **Controller**: `ReservationController::sendReservationCancellationEmail()` (line 1418)
- **Trigger**: `ReservationController::updateStatus()` (line 743) when status = 'rejected'

### Flexible Hotel Booking
- **Service**: `ReservationService::sendFlexibleHotelBookingApprovalEmail()`
- **Controller**: `ReservationController::createReservation()` (line 440, 515)
- **Controller**: `ReservationController::checkout()` (line 1143)
- **API Controller**: `ApiController::submitPaymentForm()` (line 9839)

---

## ⚠️ Important Notes

1. **Flexible Hotel Booking emails are sent immediately** when the reservation is created, not after approval. This is different from vacation home reservations.

2. **Vacation home reservations** require explicit approval/rejection by the owner/admin, and emails are sent when the status changes.

3. **Property classification** (`4` for vacation homes, `5` for hotels) is the key differentiator in the system.

4. **Payment method** matters for hotel bookings - only `cash` or `flexible` payments trigger the flexible hotel booking email.

5. **Instant booking** setting affects hotel bookings - if `instant_booking = false`, the flexible booking email is sent.

