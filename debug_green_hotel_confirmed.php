<?php

require_once 'vendor/autoload.php';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ashome_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== GREEN HOTEL 2 CONFIRMED FLEXIBLE RESERVATIONS DEBUG ===\n\n";

// Get Green Hotel 2 property ID
$propertyQuery = "SELECT id, title FROM properties WHERE title LIKE '%Green hotel 2 testing room only%'";
$propertyResult = $conn->query($propertyQuery);
$property = $propertyResult->fetch_assoc();
$propertyId = $property['id'];

echo "Property: " . $property['title'] . " (ID: " . $propertyId . ")\n\n";

// Get confirmed flexible reservations for Green Hotel 2
$query = "
    SELECT 
        r.id,
        r.property_id,
        r.reservable_id,
        r.reservable_type,
        r.check_in_date,
        r.check_out_date,
        r.status,
        r.payment_method,
        r.payment_status,
        r.created_at,
        rt.name as room_type_name,
        rt.id as room_type_id
    FROM reservations r
    LEFT JOIN room_types rt ON r.reservable_id = rt.id AND r.reservable_type = 'room_type'
    WHERE r.property_id = ? 
    AND r.status = 'confirmed'
    AND (r.payment_method = 'cash' OR r.payment_method IS NULL OR r.payment_method = '')
    ORDER BY r.check_in_date ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $propertyId);
$stmt->execute();
$result = $stmt->get_result();

echo "CONFIRMED FLEXIBLE RESERVATIONS:\n";
echo "Total found: " . $result->num_rows . "\n\n";

while ($reservation = $result->fetch_assoc()) {
    echo "Reservation #" . $reservation['id'] . ":\n";
    echo "  Check-in: " . $reservation['check_in_date'] . "\n";
    echo "  Check-out: " . $reservation['check_out_date'] . "\n";
    echo "  Status: " . $reservation['status'] . "\n";
    echo "  Payment method: " . ($reservation['payment_method'] ?: 'cash') . "\n";
    echo "  Payment status: " . ($reservation['payment_status'] ?: 'unpaid') . "\n";
    echo "  Room type: " . ($reservation['room_type_name'] ?: 'N/A') . " (ID: " . $reservation['room_type_id'] . ")\n";
    echo "  Reservable type: " . $reservation['reservable_type'] . "\n";
    echo "  Reservable ID: " . $reservation['reservable_id'] . "\n\n";
}

// Check what dates these reservations should block
echo "=== DATE RANGE ANALYSIS ===\n\n";

$stmt->execute();
$result2 = $stmt->get_result();

$allDates = [];
while ($reservation = $result2->fetch_assoc()) {
    $checkIn = new DateTime($reservation['check_in_date']);
    $checkOut = new DateTime($reservation['check_out_date']);
    
    echo "Reservation #" . $reservation['id'] . " blocks:\n";
    
    // Include check-in date, exclude check-out date
    $currentDate = clone $checkIn;
    while ($currentDate < $checkOut) {
        $dateStr = $currentDate->format('Y-m-d');
        echo "  - " . $dateStr . "\n";
        $allDates[$dateStr] = ($allDates[$dateStr] ?? 0) + 1;
        $currentDate->modify('+1 day');
    }
    echo "\n";
}

echo "=== DATE BLOCKING SUMMARY ===\n";
foreach ($allDates as $date => $count) {
    echo $date . ": " . $count . " reservation(s) blocking\n";
}

$conn->close();

?>