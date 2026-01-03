<?php
// Show all reservations blocking Jan 13-14, 2026 in table format
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=================================================================\n";
echo "  RESERVATIONS BLOCKING JAN 13-14, 2026 - TABLE VIEW\n";
echo "=================================================================\n\n";

$checkInDate = '2026-01-13';
$checkOutDate = '2026-01-14';
$propertyId = 357; // Amazing 4 Star Hotel

echo "Period: $checkInDate to $checkOutDate\n";
echo "Property: Amazing 4 Star Hotel (ID: $propertyId)\n\n";

// =============================================================================
// 1. DIRECT ROOM RESERVATIONS
// =============================================================================

echo "┌─────────────┬──────────────┬──────────────┬──────────────┬──────────────┬──────────────┬──────────────┬──────────────┐\n";
echo "│    ID       │    STATUS    │ PAYMENT STAT │ PAYMENT METH │   CHECK-IN   │  CHECK-OUT   │   ROOM ID    │   BLOCKING   │\n";
echo "├─────────────┼──────────────┼──────────────┼──────────────┼──────────────┼──────────────┼──────────────┼──────────────┤\n";

$directReservations = \App\Models\Reservation::where('reservable_type', 'App\\Models\\HotelRoom')
    ->orWhere('reservable_type', 'hotel_room')
    ->get();

$hasBlockingDirect = false;
foreach ($directReservations as $res) {
    $resCheckIn = $res->check_in_date->format('Y-m-d');
    $resCheckOut = $res->check_out_date->format('Y-m-d');
    
    // Check if overlaps
    $overlaps = ($resCheckIn <= $checkOutDate && $resCheckOut >= $checkInDate);
    
    // Check if active status
    $isActive = in_array($res->status, ['confirmed', 'approved', 'pending']);
    
    if ($overlaps && $isActive) {
        $hasBlockingDirect = true;
        $status = str_pad(substr($res->status, 0, 12), 12);
        $paymentStatus = str_pad(substr($res->payment_status ?? 'N/A', 0, 12), 12);
        $paymentMethod = str_pad(substr($res->payment_method ?? 'N/A', 0, 12), 12);
        $checkIn = str_pad($resCheckIn, 12);
        $checkOut = str_pad($resCheckOut, 12);
        $roomId = str_pad($res->reservable_id, 12);
        $blocking = str_pad('YES ❌', 12);
        
        echo "│ " . str_pad($res->id, 11) . " │ $status │ $paymentStatus │ $paymentMethod │ $checkIn │ $checkOut │ $roomId │ $blocking │\n";
    }
}

if (!$hasBlockingDirect) {
    echo "│ " . str_pad('None', 11) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('NO', 12) . " │\n";
}

echo "└─────────────┴──────────────┴──────────────┴──────────────┴──────────────┴──────────────┴──────────────┴──────────────┘\n\n";

// =============================================================================
// 2. LEGACY RESERVATIONS (reservable_data)
// =============================================================================

echo "┌─────────────┬──────────────┬──────────────┬──────────────┬──────────────┬──────────────┬──────────────┬──────────────┐\n";
echo "│    ID       │    STATUS    │ PAYMENT STAT │ PAYMENT METH │   CHECK-IN   │  CHECK-OUT   │   ROOM(S)   │   BLOCKING   │\n";
echo "├─────────────┼──────────────┼──────────────┼──────────────┼──────────────┼──────────────┼──────────────┼──────────────┤\n";

$legacyReservations = \App\Models\Reservation::where(function($q) use ($propertyId) {
        $q->where('reservable_id', $propertyId)
          ->where('reservable_type', 'App\\Models\\Property');
    })
    ->orWhere(function($q) use ($propertyId) {
        $q->where('property_id', $propertyId)
          ->whereNull('reservable_type');
    })
    ->whereNotNull('reservable_data')
    ->get();

$hasBlockingLegacy = false;
foreach ($legacyReservations as $res) {
    $resCheckIn = $res->check_in_date->format('Y-m-d');
    $resCheckOut = $res->check_out_date->format('Y-m-d');
    
    // Check if overlaps
    $overlaps = ($resCheckIn <= $checkOutDate && $resCheckOut >= $checkInDate);
    
    // Check if active status
    $isActive = in_array($res->status, ['confirmed', 'approved', 'pending']);
    
    // Parse reservable_data
    $reservableData = $res->reservable_data;
    if (is_string($reservableData)) {
        $reservableData = json_decode($reservableData, true);
    }
    
    $roomIds = [];
    if (is_array($reservableData)) {
        foreach ($reservableData as $item) {
            if (isset($item['id'])) {
                $roomIds[] = $item['id'];
            }
        }
    }
    
    if ($overlaps && $isActive && !empty($roomIds)) {
        $hasBlockingLegacy = true;
        $status = str_pad(substr($res->status, 0, 12), 12);
        $paymentStatus = str_pad(substr($res->payment_status ?? 'N/A', 0, 12), 12);
        $paymentMethod = str_pad(substr($res->payment_method ?? 'N/A', 0, 12), 12);
        $checkIn = str_pad($resCheckIn, 12);
        $checkOut = str_pad($resCheckOut, 12);
        $roomsStr = implode(',', $roomIds);
        $rooms = str_pad(substr($roomsStr, 0, 12), 12);
        $blocking = str_pad('YES ❌', 12);
        
        echo "│ " . str_pad($res->id, 11) . " │ $status │ $paymentStatus │ $paymentMethod │ $checkIn │ $checkOut │ $rooms │ $blocking │\n";
    }
}

if (!$hasBlockingLegacy) {
    echo "│ " . str_pad('None', 11) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('NO', 12) . " │\n";
}

echo "└─────────────┴──────────────┴──────────────┴──────────────┴──────────────┴──────────────┴──────────────┴──────────────┘\n\n";

// =============================================================================
// 3. PROPERTY-LEVEL RESERVATIONS
// =============================================================================

echo "┌─────────────┬──────────────┬──────────────┬──────────────┬──────────────┬──────────────┬──────────────┬──────────────┐\n";
echo "│    ID       │    STATUS    │ PAYMENT STAT │ PAYMENT METH │   CHECK-IN   │  CHECK-OUT   │  PROPERTY    │   BLOCKING   │\n";
echo "├─────────────┼──────────────┼──────────────┼──────────────┼──────────────┼──────────────┼──────────────┼──────────────┤\n";

$propertyReservations = \App\Models\Reservation::where('reservable_id', $propertyId)
    ->where('reservable_type', 'App\\Models\\Property')
    ->get();

$hasBlockingProperty = false;
foreach ($propertyReservations as $res) {
    $resCheckIn = $res->check_in_date->format('Y-m-d');
    $resCheckOut = $res->check_out_date->format('Y-m-d');
    
    // Check if overlaps
    $overlaps = ($resCheckIn <= $checkOutDate && $resCheckOut >= $checkInDate);
    
    // Check if active status
    $isActive = in_array($res->status, ['confirmed', 'approved', 'pending']);
    
    if ($overlaps && $isActive) {
        $hasBlockingProperty = true;
        $status = str_pad(substr($res->status, 0, 12), 12);
        $paymentStatus = str_pad(substr($res->payment_status ?? 'N/A', 0, 12), 12);
        $paymentMethod = str_pad(substr($res->payment_method ?? 'N/A', 0, 12), 12);
        $checkIn = str_pad($resCheckIn, 12);
        $checkOut = str_pad($resCheckOut, 12);
        $prop = str_pad($propertyId, 12);
        $blocking = str_pad('YES ❌', 12);
        
        echo "│ " . str_pad($res->id, 11) . " │ $status │ $paymentStatus │ $paymentMethod │ $checkIn │ $checkOut │ $prop │ $blocking │\n";
    }
}

if (!$hasBlockingProperty) {
    echo "│ " . str_pad('None', 11) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('N/A', 12) . " │ " . str_pad('NO', 12) . " │\n";
}

echo "└─────────────┴──────────────┴──────────────┴──────────────┴──────────────┴──────────────┴──────────────┴──────────────┘\n\n";

// =============================================================================
// 4. SUMMARY
// =============================================================================

echo "┌─────────────────────────────────────────────────────────────────────────────┐\n";
echo "│                              SUMMARY TABLE                                    │\n";
echo "├─────────────────────────────────────────────────────────────────────────────┤\n";
echo "│ RESERVATION TYPE        │ COUNT │ BLOCKING PERIOD (13-14 JAN 2026)        │\n";
echo "├─────────────────────────────────────────────────────────────────────────────┤\n";

$directCount = \App\Models\Reservation::where(function($q) {
        $q->where('reservable_type', 'App\\Models\\HotelRoom')
          ->orWhere('reservable_type', 'hotel_room');
    })
    ->whereIn('status', ['confirmed', 'approved', 'pending'])
    ->where(function ($query) use ($checkInDate, $checkOutDate) {
        $query->where(function ($q) use ($checkInDate, $checkOutDate) {
            $q->where('check_in_date', '>=', $checkInDate)
              ->where('check_in_date', '<', $checkOutDate);
        })->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
            $q->where('check_out_date', '>', $checkInDate)
              ->where('check_out_date', '<=', $checkOutDate);
        })->orWhere(function ($q) use ($checkInDate, $checkOutDate) {
            $q->where('check_in_date', '<=', $checkInDate)
              ->where('check_out_date', '>=', $checkOutDate);
        });
    })
    ->count();

$legacyCount = 0;
foreach ($legacyReservations as $res) {
    $resCheckIn = $res->check_in_date->format('Y-m-d');
    $resCheckOut = $res->check_out_date->format('Y-m-d');
    $overlaps = ($resCheckIn <= $checkOutDate && $resCheckOut >= $checkInDate);
    $isActive = in_array($res->status, ['confirmed', 'approved', 'pending']);
    
    if ($overlaps && $isActive) {
        $legacyCount++;
    }
}

$propertyCount = 0;
foreach ($propertyReservations as $res) {
    $resCheckIn = $res->check_in_date->format('Y-m-d');
    $resCheckOut = $res->check_out_date->format('Y-m-d');
    $overlaps = ($resCheckIn <= $checkOutDate && $resCheckOut >= $checkInDate);
    $isActive = in_array($res->status, ['confirmed', 'approved', 'pending']);
    
    if ($overlaps && $isActive) {
        $propertyCount++;
    }
}

$totalBlocking = $directCount + $legacyCount + $propertyCount;

echo "│ Direct Room Reservations   │ " . str_pad($directCount, 6) . " │ " . str_pad($directCount > 0 ? "YES - BLOCKING" : "NO - NOT BLOCKING", 37) . " │\n";
echo "│ Legacy Reservations       │ " . str_pad($legacyCount, 6) . " │ " . str_pad($legacyCount > 0 ? "YES - BLOCKING" : "NO - NOT BLOCKING", 37) . " │\n";
echo "│ Property Reservations     │ " . str_pad($propertyCount, 6) . " │ " . str_pad($propertyCount > 0 ? "YES - BLOCKING" : "NO - NOT BLOCKING", 37) . " │\n";
echo "├─────────────────────────────────────────────────────────────────────────────┤\n";
echo "│ TOTAL BLOCKING            │ " . str_pad($totalBlocking, 6) . " │ " . str_pad($totalBlocking > 0 ? "PERIOD IS BLOCKED" : "PERIOD IS AVAILABLE", 37) . " │\n";
echo "└─────────────────────────────────────────────────────────────────────────────┘\n\n";

// =============================================================================
// 5. CONCLUSION
// =============================================================================

if ($totalBlocking > 0) {
    echo "🔴 CONCLUSION: The period Jan 13-14, 2026 is BLOCKED by reservations\n";
    echo "   Backend should reject booking attempts\n";
    echo "   Frontend should show dates as unavailable\n";
} else {
    echo "🟢 CONCLUSION: The period Jan 13-14, 2026 has NO blocking reservations\n";
    echo "   Backend should allow booking (subject to available_dates)\n";
    echo "   Frontend should show dates as available (if in available_dates range)\n";
}

echo "\nNOTE: Room 764 is unavailable due to available_dates configuration, not reservations\n";
echo "Available dates for room 764: 2025-12-24 to 2025-12-29, 2025-12-31, 2026-01-03 to 2026-01-10\n";
echo "Requested dates (Jan 13-14, 2026) are outside these ranges\n";

echo "\n=================================================================\n";
