<?php
require_once 'config.php';

// Get reservations for Green Hotel 2
$propertyId = 351;
$roomId = 755;

// Query reservations for this specific room
$query = "
    SELECT 
        r.*,
        p.name as property_name,
        room.room_number,
        room.id as room_id
    FROM reservations r
    JOIN properties p ON r.property_id = p.id
    JOIN hotel_rooms room ON r.reservable_id = room.id
    WHERE r.property_id = ? AND room.id = ?
    ORDER BY r.check_in_date ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $propertyId, $roomId);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
}

echo "=== GREEN HOTEL 2 ROOM 755 RESERVATIONS ===\n";
echo "Property ID: $propertyId, Room ID: $roomId\n";
echo "Total reservations found: " . count($reservations) . "\n\n";

foreach ($reservations as $reservation) {
    echo "Reservation #{$reservation['id']}:\n";
    echo "  Property: {$reservation['property_name']}\n";
    echo "  Room: {$reservation['room_number']} (ID: {$reservation['room_id']})\n";
    echo "  Guest: {$reservation['first_name']} {$reservation['last_name']}\n";
    echo "  Email: {$reservation['email']}\n";
    echo "  Phone: {$reservation['phone']}\n";
    echo "  Check-in: {$reservation['check_in_date']}\n";
    echo "  Check-out: {$reservation['check_out_date']}\n";
    echo "  Price: {$reservation['total_price']} {$reservation['currency']}\n";
    echo "  Payment Method: {$reservation['payment_method']}\n";
    echo "  Payment Gateway: {$reservation['payment_gateway']}\n";
    echo "  Transaction Method: {$reservation['transaction_method']}\n";
    echo "  Payment Status: {$reservation['payment_status']}\n";
    echo "  Status: {$reservation['status']}\n";
    echo "  Display Status: {$reservation['display_status']}\n";
    echo "  Is Flexible: " . (isFlexibleReservation($reservation) ? 'YES' : 'NO') . "\n";
    echo "  Should Block: " . (shouldBlockRoom($reservation) ? 'YES' : 'NO') . "\n";
    echo "  Created At: {$reservation['created_at']}\n";
    echo "  Updated At: {$reservation['updated_at']}\n";
    echo "\n";
}

function isFlexibleReservation($reservation) {
    $paymentMethod = $reservation['payment_method'] ?? 'cash';
    return !($paymentMethod === 'paymob' || $paymentMethod === 'online' || !empty($reservation['payment']));
}

function shouldBlockRoom($reservation) {
    $isFlexible = isFlexibleReservation($reservation);
    $actualStatus = strtolower($reservation['status'] ?? '');
    $displayStatus = strtolower($reservation['display_status'] ?? '');
    $statusToUse = $displayStatus ?: $actualStatus;
    
    if ($isFlexible) {
        // For flexible reservations, block unless cancelled or rejected
        return $statusToUse !== 'cancelled' && $statusToUse !== 'rejected';
    } else {
        // For non-flexible reservations, use standard blocking logic
        $blockingStatuses = ["confirmed", "approved", "pending", "active"];
        return in_array($statusToUse, $blockingStatuses);
    }
}

// Test specific dates
echo "=== DATE-BY-DATE BLOCKING TEST ===\n";
$testDates = ['2026-01-01', '2026-01-02', '2026-01-03', '2026-01-04'];

foreach ($testDates as $date) {
    echo "\nTesting date: $date\n";
    $blocked = false;
    $blockingReservations = [];
    
    foreach ($reservations as $reservation) {
        $checkIn = $reservation['check_in_date'];
        $checkOut = $reservation['check_out_date'];
        
        // Check if date is within reservation period (inclusive check-in, exclusive check-out)
        if ($date >= $checkIn && $date < $checkOut) {
            if (shouldBlockRoom($reservation)) {
                $blocked = true;
                $blockingReservations[] = $reservation['id'];
            }
        }
    }
    
    echo "  Room status: " . ($blocked ? "BLOCKED" : "AVAILABLE") . "\n";
    if ($blocked) {
        echo "  Blocking reservations: " . implode(', ', $blockingReservations) . "\n";
    }
}