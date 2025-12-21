# Frontend Update: Vacation Homes Multi-Unit Quantity Support

## 📋 Overview

This document outlines the required frontend updates to properly support **vacation homes with multiple identical units** (quantity > 1). The system needs to track and display unit-level availability instead of property-level availability.

---

## 🎯 Current Issue

### Problem
When a vacation home has **4 identical units** and a user makes **1 reservation**, the current system blocks **all future bookings** for those dates, even though **3 units are still available**.

### Example Scenario
- **Property**: "Beachfront Resort"
- **Apartment**: "A101" with `quantity: 4` (4 identical units)
- **User 1** books: 1 unit for Feb 1-5 ✅
- **User 2** tries to book: 1 unit for Feb 1-5 ❌ **BLOCKED** (should be allowed - 3 units available)

---

## ✅ What Needs to Change

### Backend Changes (Already Planned)
1. Update availability checking to count units per apartment
2. Track booked units vs available units
3. Allow multiple bookings when units are available

### Frontend Changes (Required)
1. Display unit availability (e.g., "3 of 4 units available")
2. Show quantity selector when booking
3. Handle availability responses correctly
4. Update booking flow to include `apartment_quantity`

---

## 🔌 API Changes

### 1. Availability Check Endpoint

**Current:**
```http
POST /api/check-availability
{
  "reservable_id": 1,
  "reservable_type": "property",
  "check_in_date": "2025-02-01",
  "check_out_date": "2025-02-05"
}
```

**Response:**
```json
{
  "error": false,
  "message": "Availability checked successfully",
  "data": {
    "is_available": true  // ❌ Only boolean, no unit count
  }
}
```

**Updated (Planned):**
```http
POST /api/check-availability
{
  "reservable_id": 1,
  "reservable_type": "property",
  "check_in_date": "2025-02-01",
  "check_out_date": "2025-02-05",
  "apartment_id": 5,  // ✅ NEW: Apartment ID
  "apartment_quantity": 1  // ✅ NEW: Requested quantity
}
```

**Response:**
```json
{
  "error": false,
  "message": "Availability checked successfully",
  "data": {
    "is_available": true,  // ✅ Overall availability
    "apartment_id": 5,
    "total_units": 4,  // ✅ Total units for this apartment
    "booked_units": 1,  // ✅ Units already booked
    "available_units": 3,  // ✅ Units still available
    "can_book_quantity": 3  // ✅ Maximum quantity user can book
  }
}
```

### 2. Create Reservation Endpoint

**Current:**
```http
POST /api/create-reservation
{
  "reservable_type": "property",
  "property_id": 1,
  "reservable_id": 1,
  "check_in_date": "2025-02-01",
  "check_out_date": "2025-02-05",
  "apartment_id": 5,  // ✅ Already supported
  "apartment_quantity": 1  // ✅ Already supported
}
```

**No changes needed** - Already accepts `apartment_quantity` ✅

### 3. Property Detail Endpoint

**Current Response:**
```json
{
  "id": 1,
  "title": "Beachfront Resort",
  "property_classification": 4,
  "vacationApartments": [
    {
      "id": 5,
      "apartment_number": "A101",
      "price_per_night": 150,
      "quantity": 4,  // ✅ Already included
      "max_guests": 4,
      "bedrooms": 2,
      "bathrooms": 1
    }
  ]
}
```

**No changes needed** - `quantity` field already included ✅

---

## 🎨 UI/UX Updates Required

### 1. Property Listing Page

#### Display Unit Availability

**Current:**
```
Beachfront Resort - A101
$150/night
2 Bedrooms, 1 Bathroom
Max 4 Guests
```

**Updated:**
```
Beachfront Resort - A101
$150/night
2 Bedrooms, 1 Bathroom
Max 4 Guests
✅ 3 of 4 units available  // ✅ NEW: Show availability
```

**Implementation:**
```jsx
// React Example
{apartment.quantity > 1 && (
  <div className="availability-badge">
    <span className="available-count">{availableUnits}</span>
    <span> of </span>
    <span className="total-units">{apartment.quantity}</span>
    <span> units available</span>
  </div>
)}
```

### 2. Property Detail Page

#### Show Unit Information

**Add to apartment card:**
```jsx
<div className="apartment-info">
  <h3>{apartment.apartment_number}</h3>
  <p>${apartment.price_per_night}/night</p>
  
  {/* NEW: Unit availability */}
  {apartment.quantity > 1 && (
    <div className="unit-availability">
      <strong>Units Available:</strong>
      <span className="available">{availableUnits}</span>
      <span> / </span>
      <span className="total">{apartment.quantity}</span>
    </div>
  )}
  
  <p>{apartment.bedrooms} Bedrooms, {apartment.bathrooms} Bathrooms</p>
  <p>Max {apartment.max_guests} Guests</p>
</div>
```

### 3. Booking Form

#### Add Quantity Selector

**Current:**
```jsx
<BookingForm>
  <DatePicker checkIn={checkIn} checkOut={checkOut} />
  <GuestSelector guests={guests} />
  <SubmitButton />
</BookingForm>
```

**Updated:**
```jsx
<BookingForm>
  <DatePicker checkIn={checkIn} checkOut={checkOut} />
  <GuestSelector guests={guests} />
  
  {/* NEW: Quantity selector for multi-unit apartments */}
  {selectedApartment && selectedApartment.quantity > 1 && (
    <QuantitySelector
      min={1}
      max={availableUnits}  // ✅ Use available units from API
      value={apartmentQuantity}
      onChange={setApartmentQuantity}
      label="Number of Units"
    />
  )}
  
  <SubmitButton />
</BookingForm>
```

**Quantity Selector Component:**
```jsx
const QuantitySelector = ({ min, max, value, onChange, label }) => {
  return (
    <div className="quantity-selector">
      <label>{label}</label>
      <div className="quantity-controls">
        <button 
          onClick={() => onChange(Math.max(min, value - 1))}
          disabled={value <= min}
        >
          -
        </button>
        <input 
          type="number" 
          value={value} 
          onChange={(e) => {
            const newValue = parseInt(e.target.value) || min;
            onChange(Math.max(min, Math.min(max, newValue)));
          }}
          min={min}
          max={max}
        />
        <button 
          onClick={() => onChange(Math.min(max, value + 1))}
          disabled={value >= max}
        >
          +
        </button>
      </div>
      {max < selectedApartment.quantity && (
        <p className="availability-warning">
          Only {max} units available for selected dates
        </p>
      )}
    </div>
  );
};
```

### 4. Availability Check

#### Update Availability Checking Logic

**Current:**
```javascript
// Check availability
const checkAvailability = async () => {
  const response = await api.post('/api/check-availability', {
    reservable_id: propertyId,
    reservable_type: 'property',
    check_in_date: checkIn,
    check_out_date: checkOut
  });
  
  if (response.data.data.is_available) {
    // Allow booking
  } else {
    // Show error: "Dates not available"
  }
};
```

**Updated:**
```javascript
// Check availability with apartment info
const checkAvailability = async () => {
  const response = await api.post('/api/check-availability', {
    reservable_id: propertyId,
    reservable_type: 'property',
    check_in_date: checkIn,
    check_out_date: checkOut,
    apartment_id: selectedApartment?.id,  // ✅ NEW
    apartment_quantity: apartmentQuantity || 1  // ✅ NEW
  });
  
  const { is_available, available_units, total_units, booked_units } = response.data.data;
  
  if (is_available) {
    // Update UI with unit availability
    setAvailableUnits(available_units);
    setTotalUnits(total_units);
    setBookedUnits(booked_units);
    
    // Limit quantity selector to available units
    if (apartmentQuantity > available_units) {
      setApartmentQuantity(available_units);
    }
  } else {
    // Show error with details
    if (available_units === 0) {
      showError('All units are booked for selected dates');
    } else {
      showError(`Only ${available_units} units available for selected dates`);
    }
  }
};
```

### 5. Booking Confirmation

#### Show Unit Information

**Updated confirmation message:**
```jsx
<BookingConfirmation>
  <h2>Booking Confirmed!</h2>
  <p>Reservation ID: {reservationId}</p>
  <p>Property: {propertyName}</p>
  <p>Apartment: {apartmentNumber}</p>
  
  {/* NEW: Show quantity if > 1 */}
  {apartmentQuantity > 1 && (
    <p>Units Booked: {apartmentQuantity}</p>
  )}
  
  <p>Check-in: {checkIn}</p>
  <p>Check-out: {checkOut}</p>
  <p>Total: ${totalPrice}</p>
</BookingConfirmation>
```

---

## 🔄 Updated Booking Flow

### Step-by-Step Process

1. **User selects property** → Shows apartments with quantity info
2. **User selects apartment** → If `quantity > 1`, show availability
3. **User selects dates** → Call availability API with `apartment_id`
4. **API returns availability** → Show available units count
5. **User selects quantity** → Limit to available units
6. **User confirms booking** → Send `apartment_quantity` in request
7. **Booking confirmed** → Show quantity in confirmation

### Flow Diagram

```
User selects apartment (quantity: 4)
    ↓
User selects dates (Feb 1-5)
    ↓
Call /api/check-availability with apartment_id
    ↓
API returns: { available_units: 3, total_units: 4 }
    ↓
Show quantity selector (max: 3)
    ↓
User selects quantity (e.g., 2 units)
    ↓
Call /api/create-reservation with apartment_quantity: 2
    ↓
Booking confirmed for 2 units
```

---

## 📱 Mobile Considerations

### Responsive Design

1. **Quantity Selector**: Use touch-friendly controls
2. **Availability Badge**: Ensure readable on small screens
3. **Error Messages**: Clear and concise

### Example Mobile Layout

```jsx
<div className="mobile-booking-form">
  <DatePicker />
  <GuestSelector />
  
  {/* Stack quantity selector vertically on mobile */}
  <div className="quantity-selector-mobile">
    <label>How many units?</label>
    <div className="quantity-buttons">
      {[1, 2, 3].map(qty => (
        <button 
          key={qty}
          className={apartmentQuantity === qty ? 'active' : ''}
          onClick={() => setApartmentQuantity(qty)}
          disabled={qty > availableUnits}
        >
          {qty}
        </button>
      ))}
    </div>
    <p className="availability-text">
      {availableUnits} of {totalUnits} available
    </p>
  </div>
</div>
```

---

## 🧪 Testing Scenarios

### Test Case 1: Single Unit Available
- **Setup**: 4 units, 3 booked
- **Action**: User tries to book 1 unit
- **Expected**: ✅ Booking allowed, quantity selector shows max: 1

### Test Case 2: Multiple Units Available
- **Setup**: 4 units, 1 booked
- **Action**: User tries to book 2 units
- **Expected**: ✅ Booking allowed, quantity selector shows max: 3

### Test Case 3: All Units Booked
- **Setup**: 4 units, 4 booked
- **Action**: User tries to book any quantity
- **Expected**: ❌ Error: "All units are booked for selected dates"

### Test Case 4: Partial Availability
- **Setup**: 4 units, 2 booked
- **Action**: User tries to book 3 units
- **Expected**: ❌ Error: "Only 2 units available", quantity selector limited to 2

### Test Case 5: Quantity Change After Date Selection
- **Setup**: User selects dates with 3 units available
- **Action**: User changes dates (now only 1 unit available)
- **Expected**: Quantity selector updates to max: 1, current selection adjusted if needed

---

## 🚨 Error Handling

### Error Messages

```javascript
const getAvailabilityError = (availableUnits, totalUnits) => {
  if (availableUnits === 0) {
    return 'All units are booked for the selected dates. Please choose different dates.';
  }
  
  if (availableUnits < totalUnits) {
    return `Only ${availableUnits} of ${totalUnits} units are available for the selected dates.`;
  }
  
  return 'Selected dates are not available.';
};
```

### User Feedback

```jsx
{!isAvailable && (
  <div className="availability-error">
    <Icon name="warning" />
    <p>{getAvailabilityError(availableUnits, totalUnits)}</p>
    <button onClick={showAlternativeDates}>
      View Alternative Dates
    </button>
  </div>
)}
```

---

## 📊 Data Structure Reference

### Apartment Object
```typescript
interface VacationApartment {
  id: number;
  apartment_number: string;
  price_per_night: number;
  discount_percentage: number;
  quantity: number;  // ✅ Total identical units
  max_guests: number;
  bedrooms: number;
  bathrooms: number;
  status: boolean;
  availability_type: 1 | 2;  // 1: Available Days, 2: Busy Days
  available_dates: DateRange[];
}
```

### Availability Response
```typescript
interface AvailabilityResponse {
  is_available: boolean;
  apartment_id: number;
  total_units: number;  // ✅ Total units for apartment
  booked_units: number;  // ✅ Units already booked
  available_units: number;  // ✅ Units still available
  can_book_quantity: number;  // ✅ Max quantity user can book
}
```

### Reservation Request
```typescript
interface CreateReservationRequest {
  reservable_type: 'property' | 'hotel_room';
  property_id: number;
  reservable_id: number;
  check_in_date: string;
  check_out_date: string;
  number_of_guests: number;
  apartment_id?: number;  // ✅ For vacation homes
  apartment_quantity?: number;  // ✅ Number of units to book
  special_requests?: string;
}
```

---

## ✅ Checklist for Frontend Team

### Phase 1: API Integration
- [ ] Update availability check API call to include `apartment_id` and `apartment_quantity`
- [ ] Handle new availability response structure
- [ ] Update reservation creation to include `apartment_quantity`
- [ ] Test API responses with quantity data

### Phase 2: UI Components
- [ ] Create `QuantitySelector` component
- [ ] Add availability badge to apartment cards
- [ ] Update booking form to show quantity selector
- [ ] Add unit availability display to property detail page

### Phase 3: Logic Updates
- [ ] Update availability checking logic
- [ ] Implement quantity validation (max = available_units)
- [ ] Handle quantity changes when dates change
- [ ] Update booking confirmation to show quantity

### Phase 4: Error Handling
- [ ] Add error messages for unit availability
- [ ] Handle edge cases (all units booked, partial availability)
- [ ] Show helpful messages to users

### Phase 5: Testing
- [ ] Test with single unit apartments (quantity = 1)
- [ ] Test with multi-unit apartments (quantity > 1)
- [ ] Test booking flow with different quantities
- [ ] Test error scenarios
- [ ] Test mobile responsiveness

---

## 🔗 Related Documents

- `VACATION_HOMES_MULTI_UNIT_RESERVATIONS.md` - Backend implementation details
- `VACATION_HOMES_QUANTITY_AVAILABILITY.md` - Availability logic explanation

---

## 📞 Support

If you have questions or need clarification:
1. Review the backend documentation
2. Check API response examples
3. Contact backend team for API changes timeline

---

## 🎯 Priority

**High Priority** - This affects booking functionality for vacation homes with multiple units. Users should be able to book available units even when some units are already booked.

---

**Last Updated:** [Current Date]
**Status:** ⚠️ Awaiting Backend API Updates
**Frontend Ready:** ✅ Can start UI development, API integration pending

