<?php
// Preview all reservations that block room 764
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ALL BLOCKING RESERVATIONS FOR ROOM 764 ===\n\n";

$roomId = 764;
$checkInDate = '2026-01-13';
$checkOutDate = '2026-01-14';

echo "Room ID: $roomId\n";
echo "Requested Dates: $checkInDate to $checkOutDate\n\n";

// =============================================================================
// 1. DIRECT ROOM RESERVATIONS (reservable_id = 764)
// =============================================================================

echo "1. DIRECT ROOM RESERVATIONS\n";
echo "==========================\n";

$directReservations = \App\Models\Reservation::where('reservable_id', $roomId)
    ->where(function($q) {
        $q->where('reservable_type', 'App\\Models\\HotelRoom')
          ->orWhere('reservable_type', 'hotel_room');
    })
    ->get();

echo "Total direct reservations: {$directReservations->count()}\n\n";

$blockingDirect = [];
foreach ($directReservations as $res) {
    $status = $res->status;
    $paymentStatus = $res->payment_status ?? 'N/A';
    $paymentMethod = $res->payment_method ?? 'N/A';
    $resCheckIn = $res->check_in_date->format('Y-m-d');
    $resCheckOut = $res->check_out_date->format('Y-m-d');
    
    // Check if active status
    $isActive = in_array($status, ['confirmed', 'approved', 'pending']);
    
    // Check if overlaps
    $overlaps = ($resCheckIn <= $checkOutDate && $resCheckOut >= $checkInDate);
    
    echo "Reservation ID: {$res->id}\n";
    echo "  Status: $status " . ($isActive ? "(ACTIVE)" : "(INACTIVE)") . "\n";
    echo "  Payment: $paymentStatus / $paymentMethod\n";
    echo "  Dates: $resCheckIn to $resCheckOut\n";
    echo "  Overlaps: " . ($overlaps ? "YES ❌" : "NO ✅") . "\n";
    
    if ($isActive && $overlaps) {
        $blockingDirect[] = $res;
        echo "  >>> BLOCKING RESERVATION <<<\n";
    }
    
    echo "  Created: " . $res->created_at->format('Y-m-d H:i:s') . "\n\n";
}

// =============================================================================
// 2. LEGACY RESERVATIONS (reservable_data contains room 764)
// =============================================================================

echo "2. LEGACY RESERVATIONS (reservable_data)\n";
echo "=====================================\n";

$legacyReservations = \App\Models\Reservation::where(function($q) use ($roomId) {
        // Property reservations with reservable_data
        $q->where('reservable_type', 'App\\Models\\Property')
          ->where('reservable_id', 357); // Amazing 4 Star Hotel
    })
    ->orWhere(function($q) {
        // Reservations with empty reservable_type but property_id
        $q->whereNull('reservable_type')
          ->where('property_id', 357);
    })
    ->whereNotNull('reservable_data')
    ->get();

echo "Total legacy reservations: {$legacyReservations->count()}\n\n";

$blockingLegacy = [];
foreach ($legacyReservations as $res) {
    $status = $res->status;
    $paymentStatus = $res->payment_status ?? 'N/A';
    $paymentMethod = $res->payment_method ?? 'N/A';
    $resCheckIn = $res->check_in_date->format('Y-m-d');
    $resCheckOut = $res->check_out_date->format('Y-m-d');
    
    // Parse reservable_data
    $reservableData = $res->reservable_data;
    if (is_string($reservableData)) {
        $reservableData = json_decode($reservableData, true);
    }
    
    $roomFound = false;
    if (is_array($reservableData)) {
        foreach ($reservableData as $item) {
            if (isset($item['id']) && $item['id'] == $roomId) {
                $roomFound = true;
                break;
            }
        }
    }
    
    if ($roomFound) {
        $isActive = in_array($status, ['confirmed', 'approved', 'pending']);
        $overlaps = ($resCheckIn <= $checkOutDate && $resCheckOut >= $checkInDate);
        
        echo "Legacy Reservation ID: {$res->id}\n";
        echo "  Status: $status " . ($isActive ? "(ACTIVE)" : "(INACTIVE)") . "\n";
        echo "  Payment: $paymentStatus / $paymentMethod\n";
        echo "  Dates: $resCheckIn to $resCheckOut\n";
        echo "  Overlaps: " . ($overlaps ? "YES ❌" : "NO ✅") . "\n";
        echo "  Reservable Data: " . json_encode($reservableData) . "\n";
        
        if ($isActive && $overlaps) {
            $blockingLegacy[] = $res;
            echo "  >>> BLOCKING RESERVATION <<<\n";
        }
        
        echo "  Created: " . $res->created_at->format('Y-m-d H:i:s') . "\n\n";
    }
}

// =============================================================================
// 3. PROPERTY-LEVEL RESERVATIONS
// =============================================================================

echo "3. PROPERTY-LEVEL RESERVATIONS\n";
echo "=============================\n";

$propertyReservations = \App\Models\Reservation::where('reservable_id', 357)
    ->where('reservable_type', 'App\\Models\\Property')
    ->get();

echo "Total property reservations: {$propertyReservations->count()}\n\n";

$blockingProperty = [];
foreach ($propertyReservations as $res) {
    $status = $res->status;
    $paymentStatus = $res->payment_status ?? 'N/A';
    $paymentMethod = $res->payment_method ?? 'N/A';
    $resCheckIn = $res->check_in_date->format('Y-m-d');
    $resCheckOut = $res->check_out_date->format('Y-m-d');
    
    $isActive = in_array($status, ['confirmed', 'approved', 'pending']);
    $overlaps = ($resCheckIn <= $checkOutDate && $resCheckOut >= $checkInDate);
    
    echo "Property Reservation ID: {$res->id}\n";
    echo "  Status: $status " . ($isActive ? "(ACTIVE)" : "(INACTIVE)") . "\n";
    echo "  Payment: $paymentStatus / $paymentMethod\n";
    echo "  Dates: $resCheckIn to $resCheckOut\n";
    echo "  Overlaps: " . ($overlaps ? "YES ❌" : "NO ✅") . "\n";
    
    if ($isActive && $overlaps) {
        $blockingProperty[] = $res;
        echo "  >>> BLOCKING RESERVATION <<<\n";
    }
    
    echo "  Created: " . $res->created_at->format('Y-m-d H:i:s') . "\n\n";
}

// =============================================================================
// 4. SUMMARY OF ALL BLOCKING RESERVATIONS
// =============================================================================

echo "\n4. SUMMARY OF BLOCKING RESERVATIONS\n";
echo "=================================\n";

$totalBlocking = count($blockingDirect) + count($blockingLegacy) + count($blockingProperty);

echo "Total blocking reservations: $totalBlocking\n\n";

if ($totalBlocking > 0) {
    echo "BLOCKING RESERVATIONS BREAKDOWN:\n";
    echo "-------------------------------\n";
    
    if (count($blockingDirect) > 0) {
        echo "Direct Room Reservations: " . count($blockingDirect) . "\n";
        foreach ($blockingDirect as $res) {
            echo "  - ID {$res->id}: {$res->status} ({$res->check_in_date->format('Y-m-d')} to {$res->check_out_date->format('Y-m-d')})\n";
        }
        echo "\n";
    }
    
    if (count($blockingLegacy) > 0) {
        echo "Legacy Reservations: " . count($blockingLegacy) . "\n";
        foreach ($blockingLegacy as $res) {
            echo "  - ID {$res->id}: {$res->status} ({$res->check_in_date->format('Y-m-d')} to {$res->check_out_date->format('Y-m-d')})\n";
        }
        echo "\n";
    }
    
    if (count($blockingProperty) > 0) {
        echo "Property Reservations: " . count($blockingProperty) . "\n";
        foreach ($blockingProperty as $res) {
            echo "  - ID {$res->id}: {$res->status} ({$res->check_in_date->format('Y-m-d')} to {$res->check_out_date->format('Y-m-d')})\n";
        }
        echo "\n";
    }
    
    echo "CONCLUSION: Room $roomId is BLOCKED for $checkInDate to $checkOutDate\n";
    echo "         Backend should reject booking attempts\n";
    echo "         Frontend should show room as unavailable\n";
} else {
    echo "NO BLOCKING RESERVATIONS FOUND\n";
    echo "Room $roomId should be AVAILABLE for $checkInDate to $checkOutDate\n";
    echo "If backend rejects, check available_dates configuration\n";
}

// =============================================================================
// 5. CHECK AVAILABLE_DATES CONFIGURATION
// =============================================================================

echo "\n5. ROOM AVAILABLE_DATES CONFIGURATION\n";
echo "===================================\n";

$room = \App\Models\HotelRoom::find($roomId);
if ($room) {
    echo "Room ID: {$room->id}\n";
    echo "Room Status: " . ($room->status ? 'Active' : 'Inactive') . "\n";
    
    if ($room->available_dates && count($room->available_dates) > 0) {
        echo "Available Dates:\n";
        foreach ($room->available_dates as $range) {
            $from = $range['from'];
            $to = $range['to'];
            $price = $range['price'] ?? 'N/A';
            
            // Check if requested dates fall in this range
            $inRange = ($checkInDate >= $from && $checkOutDate <= $to);
            echo "  $from to $to (Price: $price) " . ($inRange ? "✅ REQUESTED DATES IN RANGE" : "❌ Requested dates NOT in range") . "\n";
        }
        
        // Check if any range includes the requested dates
        $dateInAvailableRange = false;
        foreach ($room->available_dates as $range) {
            if ($checkInDate >= $range['from'] && $checkOutDate <= $range['to']) {
                $dateInAvailableRange = true;
                break;
            }
        }
        
        if (!$dateInAvailableRange) {
            echo "\n❌ REQUESTED DATES NOT IN ANY AVAILABLE RANGE\n";
            echo "   This is why the room shows as unavailable\n";
        }
    } else {
        echo "Available Dates: (empty - room available by default)\n";
    }
}

echo "\nPreview completed.\n";
