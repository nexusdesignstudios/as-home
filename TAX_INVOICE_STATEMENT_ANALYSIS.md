# Tax Invoice Loading in Statement of Account - Analysis Report

## Overview
This document analyzes how tax invoices are loaded in the Statement of Account for hotel properties, specifically focusing on cash/flexible reservations and their calculations.

---

## 🔍 Current Implementation Analysis

### 1. Tax Invoice Loading Method: `getTaxInvoice()`

**Location:** `app/Http/Controllers/StatementOfAccountController.php` (Lines 1119-1334)

**Current Query Logic:**
```php
// Line 1203-1208
$query = Reservation::where('reservable_type', 'App\\Models\\HotelRoom')
    ->whereIn('reservable_id', $hotelRoomIds)
    ->whereIn('status', ['confirmed', 'approved'])
    ->whereIn('payment_status', ['paid', 'cash'])  // ✅ Includes both paid and cash
    ->with(['customer', 'reservable.roomType', 'reservable.property', 'payment:id,reservation_id,status']);
```

**Issues Identified:**

1. **❌ No Filtering for Cash/Flexible Reservations Only**
   - The method includes ALL reservations with `payment_status` = 'paid' OR 'cash'
   - This means it includes:
     - Online payments (Paymob) with `payment_status = 'paid'`
     - Cash/Manual payments with `payment_status = 'cash'`
   - **Tax invoices should ONLY include cash/flexible reservations**

2. **❌ Payment Method Detection Logic Missing**
   - The method determines payment method but doesn't filter by it
   - Line 1236-1238: Determines if online payment but doesn't exclude them

---

## 📊 Calculation Logic Analysis

### Current Calculation Flow (Lines 1263-1287):

```php
// Step 1: Get gross reservation amount (before taxes)
$revenueBeforeTax = (float)$reservation->total_price;

// Step 2: Calculate taxes from gross revenue
$serviceCharge = $revenueBeforeTax * ($serviceChargeRate / 100);
$salesTax = $revenueBeforeTax * ($salesTaxRate / 100);
$cityTax = $revenueBeforeTax * ($cityTaxRate / 100);
$totalTaxAmount = $serviceCharge + $salesTax + $cityTax;

// Step 3: Calculate net revenue (pure reservation amount without taxes)
$netRevenue = $revenueBeforeTax - $totalTaxAmount;

// Step 4: Calculate commission from net revenue
// AS Home: 15% of net revenue
// Hotel: 85% of net revenue
$asHomeCommission = $netRevenue * 0.15;  // 15% commission for AS Home
$hotelCommission = $netRevenue * 0.85;   // 85% for hotel
```

**✅ Calculation Logic is CORRECT:**
- Taxes are calculated from gross revenue (total_price)
- Net revenue = Gross revenue - Total taxes
- Commission split: 15% AS Home, 85% Hotel (from net revenue)

---

## 🔴 Problem: Missing Cash/Flexible Filter

### Comparison with Other Methods:

#### 1. `getOwnerStatement()` Method (Line 905-909)
```php
$query = Reservation::where('reservable_type', 'App\\Models\\HotelRoom')
    ->whereIn('reservable_id', $hotelRoomIds)
    ->whereIn('status', ['confirmed', 'approved'])
    ->whereIn('payment_status', ['paid', 'cash'])  // Includes both
    ->with([...]);
```

**Then filters by payment method (Lines 941-949):**
```php
$paymentMethod = $reservation->payment_method ?? 'cash';
$isOnlinePayment = ($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment);
$isFlexibleRate = !$isOnlinePayment; // Flexible = Manual/Cash, Non-Refundable = Online
```

#### 2. `MonthlyTaxInvoiceService` (Lines 856-869)
```php
$flexibleReservations = $reservations->filter(function ($reservation) {
    $paymentMethod = strtolower($reservation->payment_method ?? 'cash');
    // If payment_method is explicitly 'cash', treat as flexible
    if ($paymentMethod === 'cash') {
        return true;
    }
    // Otherwise, check if it's online payment
    $isOnlinePayment = (
        $paymentMethod === 'paymob' || 
        $paymentMethod === 'online' || 
        ($reservation->payment !== null && $paymentMethod !== 'cash')
    );
    return !$isOnlinePayment;
});
```

**✅ This service correctly filters for cash/flexible only**

---

## 🛠️ Required Fix

### Issue:
The `getTaxInvoice()` method in `StatementOfAccountController` does NOT filter out online payments. It includes all reservations with `payment_status = 'paid'` or `'cash'`, which means it includes:
- ✅ Cash/Manual payments (should be included)
- ❌ Online/Paymob payments (should be EXCLUDED)

### Solution:
Add filtering logic similar to `MonthlyTaxInvoiceService` to exclude online payments:

```php
// After getting reservations (around line 1218)
$reservations = $query->orderBy('check_in_date', 'ASC')->get();

// Filter for cash/flexible reservations only (ADD THIS)
$flexibleReservations = $reservations->filter(function ($reservation) {
    $paymentMethod = strtolower($reservation->payment_method ?? 'cash');
    
    // If payment_method is explicitly 'cash', treat as flexible
    if ($paymentMethod === 'cash') {
        return true;
    }
    
    // Otherwise, check if it's online payment
    $isOnlinePayment = (
        $paymentMethod === 'paymob' || 
        $paymentMethod === 'online' || 
        ($reservation->payment !== null && $paymentMethod !== 'cash')
    );
    
    // Only include cash/offline payments (exclude online/paymob)
    return !$isOnlinePayment;
});

// Use filtered reservations
$reservations = $flexibleReservations;
```

---

## 📋 Calculation Verification

### Test Case Example:

**Reservation Details:**
- `total_price` = 1000 EGP
- `payment_status` = 'cash'
- `payment_method` = 'cash'
- Tax rates: Service Charge 10%, Sales Tax 14%, City Tax 5%

**Expected Calculations:**

1. **Revenue Before Tax:** 1000.00 EGP

2. **Taxes (from gross revenue):**
   - Service Charge: 1000 × 10% = 100.00 EGP
   - Sales Tax: 1000 × 14% = 140.00 EGP
   - City Tax: 1000 × 5% = 50.00 EGP
   - **Total Taxes:** 290.00 EGP

3. **Net Revenue (after taxes):**
   - Net Revenue = 1000 - 290 = **710.00 EGP**

4. **Commission Split (from net revenue):**
   - AS Home Commission: 710 × 15% = **106.50 EGP**
   - Hotel Commission: 710 × 85% = **603.50 EGP**

**✅ Verification:** 106.50 + 603.50 = 710.00 ✓ (Correct)

---

## 🔍 Additional Issues Found

### 1. Inconsistent Payment Status Handling

**In `getRevenueCollectorData()` (Line 98):**
```php
->where('payment_status', 'paid')  // ❌ Only 'paid', missing 'cash'
```

**Should be:**
```php
->whereIn('payment_status', ['paid', 'cash'])  // ✅ Include both
```

### 2. Payment Method Detection Logic

The logic for determining flexible vs non-refundable is consistent across methods:
- **Cash/Manual payment** = Flexible (editable credit)
- **Online/Paymob payment** = Non-Refundable (fixed credit)

---

## ✅ Summary of Findings

### What's Working:
1. ✅ Calculation logic is correct (taxes from gross, commission from net)
2. ✅ Tax rates are properly retrieved from system settings
3. ✅ Property-level and reservation-level credit edits are supported
4. ✅ Payment method detection logic is consistent

### What Needs Fixing:
1. ❌ **`getTaxInvoice()` method includes online payments** - Should filter for cash/flexible only
2. ❌ **`getRevenueCollectorData()` only includes 'paid' status** - Should include 'cash' as well

---

## 🎯 Recommended Actions

1. **Fix `getTaxInvoice()` method:**
   - Add filtering to exclude online payments
   - Only include cash/flexible reservations

2. **Fix `getRevenueCollectorData()` method:**
   - Change `where('payment_status', 'paid')` to `whereIn('payment_status', ['paid', 'cash'])`

3. **Add logging:**
   - Log when reservations are filtered out
   - Log calculation details for debugging

4. **Add unit tests:**
   - Test calculation accuracy
   - Test filtering logic
   - Test edge cases (null payment_method, etc.)

---

## 📝 Code Changes Required

See the fix implementation in the next section.

