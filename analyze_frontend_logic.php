<?php

/**
 * Debug script to check the exact reservation data structure
 * This will help us understand what's happening with the frontend display
 */

// Let's create a mock response similar to what the frontend receives
$mockReservations = [
    [
        'id' => 896,
        'property_id' => 351,
        'reservable_id' => 755,
        'reservable_type' => 'App\\Models\\HotelRoom',
        'check_in_date' => '2026-01-01',
        'check_out_date' => '2026-01-03',
        'status' => 'pending', // This might be the issue - should it be 'confirmed'?
        'payment_method' => 'cash',
        'payment_status' => 'unpaid',
        'payment' => null,
        'created_at' => '2026-01-01T01:55:00Z',
        'property' => [
            'title' => 'Green hotel 2 testing room only'
        ],
        'display_status' => 'confirmed' // This is what the frontend shows
    ],
    [
        'id' => 897,
        'property_id' => 351,
        'reservable_id' => 755,
        'reservable_type' => 'App\\Models\\HotelRoom',
        'check_in_date' => '2026-01-02',
        'check_out_date' => '2026-01-04',
        'status' => 'pending',
        'payment_method' => 'cash',
        'payment_status' => 'unpaid',
        'payment' => null,
        'created_at' => '2026-01-01T12:42:00Z',
        'property' => [
            'title' => 'Green hotel 2 testing room only'
        ],
        'display_status' => 'confirmed'
    ],
    [
        'id' => 898,
        'property_id' => 351,
        'reservable_id' => 755,
        'reservable_type' => 'App\\Models\\HotelRoom',
        'check_in_date' => '2026-01-02',
        'check_out_date' => '2026-01-04',
        'status' => 'pending',
        'payment_method' => 'cash',
        'payment_status' => 'unpaid',
        'payment' => null,
        'created_at' => '2026-01-01T14:47:00Z',
        'property' => [
            'title' => 'Green hotel 2 testing room only'
        ],
        'display_status' => 'confirmed'
    ]
];

echo "=== ANALYZING RESERVATION DATA STRUCTURE ===\n\n";

foreach ($mockReservations as $reservation) {
    echo "Reservation #{$reservation['id']}:\n";
    echo "  Status (actual): {$reservation['status']}\n";
    echo "  Display Status (frontend): {$reservation['display_status']}\n";
    echo "  Payment Method: {$reservation['payment_method']}\n";
    echo "  Payment Status: {$reservation['payment_status']}\n";
    echo "  Check-in: {$reservation['check_in_date']}\n";
    echo "  Check-out: {$reservation['check_out_date']}\n";
    echo "  Room ID: {$reservation['reservable_id']}\n\n";
    
    // Check if this should block the calendar according to the frontend logic
    $paymentMethod = $reservation['payment_method'] ?? 'cash';
    $isFlexible = !($paymentMethod === 'paymob' || $paymentMethod === 'online' || !empty($reservation['payment']));
    
    // Frontend uses display_status if available, otherwise status
    $reservationStatus = $reservation['display_status'] ?? $reservation['status'];
    
    echo "  Frontend Logic Analysis:\n";
    echo "    Is Flexible: " . ($isFlexible ? 'YES' : 'NO') . "\n";
    echo "    Status Used: {$reservationStatus}\n";
    
    // Check blocking logic from frontend
    $blockingStatuses = ["confirmed", "approved", "pending", "active"];
    $isBlockingStatus = ($isFlexible && $reservation['status'] !== 'cancelled' && $reservation['status'] !== 'rejected') || 
                       in_array($reservationStatus, $blockingStatuses) || 
                       !$reservation['status'];
    
    echo "    Should Block Calendar: " . ($isBlockingStatus ? 'YES' : 'NO') . "\n";
    
    if ($isBlockingStatus) {
        echo "    🔒 This reservation SHOULD block room {$reservation['reservable_id']}\n";
    } else {
        echo "    ✅ This reservation should NOT block room {$reservation['reservable_id']}\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

echo "=== KEY FINDINGS ===\n";
echo "1. All reservations have 'pending' as actual status but 'confirmed' as display_status\n";
echo "2. Frontend uses display_status ('confirmed') for display but actual status ('pending') for blocking logic\n";
echo "3. Since they're flexible (cash) and status is 'pending' (not cancelled/rejected), they SHOULD block\n";
echo "4. The issue might be that 'pending' status is not in the blockingStatuses array\n";
echo "5. OR the frontend is using actual status instead of display_status for blocking\n\n";

echo "=== SOLUTION ===\n";
echo "The blocking logic should use display_status when available, not the actual status.\n";
echo "Since display_status is 'confirmed', these reservations should definitely block.\n";
echo "The issue is in the calendar component's blocking logic.\n";