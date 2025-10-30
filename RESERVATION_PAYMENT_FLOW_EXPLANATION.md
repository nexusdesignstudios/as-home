# Hotel Reservation Payment Flow Differentiation

## Overview

The system handles **two distinct types** of hotel reservations based on payment method and approval requirements:

1. **Online Payment Reservations** (Paymob/Payment Gateway)
2. **Manual Approval Reservations** (Requires admin approval or manual payment marking)

---

## Type 1: Online Payment Through Paymob/Payment Gateway

### Characteristics:
- **Payment Method**: `payment_method = 'paymob'` or via payment gateway
- **Initial Status**: `status = 'pending'`, `payment_status = 'unpaid'`
- **Automatic Confirmation**: Automatically confirmed upon successful payment
- **Payment Tracking**: Creates `PaymobPayment` record linked to reservation
- **Workflow**: Customer pays → Payment Gateway callback → Auto-confirm

### Flow Diagram:
```
Customer Books → Reservation Created (pending, unpaid)
    ↓
Payment Gateway Redirect
    ↓
Customer Completes Payment
    ↓
Payment Gateway Callback → PaymobController::callback()
    ↓
If Payment Succeeds:
    - PaymobPayment.status = 'succeed'
    - Reservation.status = 'confirmed'
    - Reservation.payment_status = 'paid'
    - ReservationService::handleReservationConfirmation()
    - Available dates updated
    - Confirmation emails sent
```

### Key Code Locations:

**1. Reservation Creation (ApiController.php)**
```php
$reservation = Reservation::create([
    'status' => 'pending',
    'payment_status' => 'unpaid',
    'payment_method' => 'paymob',
    'transaction_id' => $transactionId,
]);

$payment = PaymobPayment::create([
    'reservation_id' => $reservation->id,
    'status' => 'pending',
    'transaction_id' => $transactionId,
]);
```

**2. Payment Callback Handler (PaymobController.php)**
```php
if ($paymentStatus === 'succeed') {
    $reservationService->handleReservationConfirmation($reservation, 'paid');
    // Automatically:
    // - Sets status to 'confirmed'
    // - Sets payment_status to 'paid'
    // - Updates available dates
    // - Sends confirmation emails
}
```

**3. Confirmation Service (ReservationService.php)**
```php
public function handleReservationConfirmation($reservation, $paymentStatus = 'paid')
{
    $reservation->status = 'confirmed';
    $reservation->payment_status = $paymentStatus;
    $reservation->save();
    
    // Update available dates
    $this->updateAvailableDates(...);
    
    // Send emails
    $this->sendPaymentCompletionEmailToOwner($reservation);
    $this->sendReservationConfirmationEmail($reservation);
}
```

---

## Type 2: Manual Approval / Cash Payment Reservations

### Characteristics:
- **Payment Method**: `payment_method = 'cash'` or `null` (manual entry)
- **Initial Status**: `status = 'pending'`, `payment_status = 'unpaid'` or `null`
- **Requires Approval**: `requires_approval = true` (optional field)
- **Approval Status**: `approval_status = 'pending'`
- **Manual Processing**: Admin must approve and mark as paid manually
- **Workflow**: Customer requests → Admin reviews → Admin approves → Admin marks as paid → Confirmed

### Flow Diagram:
```
Customer Submits Request → Reservation Created
    - status = 'pending'
    - payment_status = 'unpaid' or 'pending'
    - requires_approval = true (optional)
    - approval_status = 'pending'
    ↓
Admin Reviews Reservation
    ↓
Admin Approves (Optional Step)
    - status = 'approved'
    - Send approval email (with payment link if needed)
    ↓
Admin Manually Marks as Paid
    - payment_status = 'paid'
    - status = 'confirmed'
    - ReservationService::handleReservationConfirmation()
    - Available dates updated
    - Confirmation emails sent
```

### Key Code Locations:

**1. Reservation Creation (ApiController.php - Payment Form)**
```php
$reservation = Reservation::create([
    'payment_method' => 'Card', // or 'cash'
    'payment_status' => 'pending',
    'status' => 'pending',
    'approval_status' => 'pending',
    'requires_approval' => true,
    'booking_type' => 'reservation_request',
]);
```

**2. Admin Approval (ReservationsAdminController.php)**
```php
// Step 1: Admin Approves (Optional)
if ($newStatus === 'approved') {
    $reservation->status = 'approved';
    if ($request->has('payment_status')) {
        $reservation->payment_status = $request->payment_status;
    }
    $reservation->save();
    
    // Send approval email (may include payment link)
    $reservationService->sendReservationApprovalEmail($reservation);
}

// Step 2: Admin Confirms (After Payment)
if ($oldStatus === 'pending' && $newStatus === 'confirmed') {
    $paymentStatus = $request->payment_status ?? 'paid';
    $reservationService->handleReservationConfirmation($reservation, $paymentStatus);
}
```

---

## Key Differentiators in the Database

### Reservation Model Fields:

| Field | Online Payment | Manual Approval |
|-------|---------------|----------------|
| `payment_method` | `'paymob'` | `'cash'` or `null` |
| `payment_status` | Starts as `'unpaid'` → Auto `'paid'` | Starts as `'unpaid'` → Manual `'paid'` |
| `status` | `'pending'` → Auto `'confirmed'` | `'pending'` → `'approved'` → `'confirmed'` |
| `requires_approval` | `false` or `null` | `true` |
| `approval_status` | Usually `null` | `'pending'` → `'approved'` |
| `transaction_id` | Generated by Paymob | Manual entry or `'PF-{id}'` |
| `PaymobPayment` record | ✅ Created | ❌ Not created |

---

## How the System Differentiates

### 1. **Payment Method Check**
```php
// Online payment has payment_method = 'paymob'
if ($reservation->payment_method === 'paymob') {
    // Expect PaymobPayment record
    // Payment handled via callback
}

// Manual approval has payment_method = 'cash' or null
if ($reservation->payment_method === 'cash' || !$reservation->payment_method) {
    // Requires manual approval/confirmation
}
```

### 2. **PaymobPayment Relationship**
```php
// Online payments have a linked PaymobPayment
$reservation->payment; // Returns PaymobPayment or null

if ($reservation->payment) {
    // This is an online payment reservation
    // Payment status tracked via PaymobPayment.status
}

if (!$reservation->payment) {
    // This is a manual approval reservation
    // Payment status set manually by admin
}
```

### 3. **Status Workflow Difference**

**Online Payment:**
- `pending` → (auto) → `confirmed` (when payment succeeds)

**Manual Approval:**
- `pending` → (optional) → `approved` → (manual) → `confirmed`

### 4. **Payment Status Tracking**

**Online Payment:**
```php
// Tracked via PaymobPayment callback
PaymobPayment::where('reservation_id', $id)
    ->where('status', 'succeed')
    ->exists();

// Reservation payment_status updated automatically
```

**Manual Approval:**
```php
// Set directly by admin
$reservation->payment_status = 'paid'; // or 'cash'
$reservation->status = 'confirmed';
$reservation->save();
```

---

## Admin Panel Handling

### In ReservationsAdminController:

**For Online Payments:**
- Payment status updated automatically via callback
- Admin can see payment details via `$reservation->payment`
- Admin only needs to handle edge cases (refunds, cancellations)

**For Manual Approvals:**
- Admin sees `status = 'pending'` or `'approved'`
- Admin can:
  1. Approve reservation → `status = 'approved'`
  2. Mark as paid → `payment_status = 'paid'`
  3. Confirm → `status = 'confirmed'` (triggers full confirmation flow)

---

## Statement of Account Differentiation

In the **Statement of Account** feature, both types are included:

**Method 1: getOwnerStatement (Line 909)**
```php
// Gets all confirmed/approved reservations with payment_status = 'paid' or 'cash'
$reservations = Reservation::whereIn('status', ['confirmed', 'approved'])
    ->whereIn('payment_status', ['paid', 'cash'])
    ->get();
```

**Method 2: getRevenueCollectorData (Line 98)**
```php
// Only gets 'paid' status (may need update to include 'cash')
$reservations = Reservation::where('status', 'confirmed')
    ->where('payment_status', 'paid')
    ->get();
```

**Note**: The `getOwnerStatement` method correctly includes both `'paid'` and `'cash'` payment statuses, while `getRevenueCollectorData` only includes `'paid'`. Both payment types are treated equally for revenue calculation in the owner statement view.

---

## Summary Table

| Aspect | Online Payment | Manual Approval |
|--------|---------------|----------------|
| **Payment Method** | Paymob/Gateway | Cash/Manual |
| **Payment Record** | `PaymobPayment` created | No `PaymobPayment` |
| **Confirmation** | Automatic (on payment success) | Manual (admin action) |
| **Workflow** | Single step (payment = confirmation) | Multi-step (approve → mark paid → confirm) |
| **Payment Status** | Auto-updated by callback | Manually set by admin |
| **Emails** | Auto-sent on confirmation | Sent on approval and confirmation |
| **Transaction ID** | Paymob transaction ID | Manual entry or form submission ID |

---

## Best Practices

1. **Always check `payment_method`** before processing reservations
2. **Check for `PaymobPayment` relationship** to determine payment type
3. **Use `requires_approval` flag** to identify reservations needing manual review
4. **Never auto-confirm manual approval reservations** without admin action
5. **Ensure `payment_status` is set correctly** for Statement of Account calculations

