<?php

/**
 * Test the LIVE API response for Green Hotel 2 reservations
 * This will authenticate and show us the actual data the frontend is receiving
 */

$apiBaseUrl = "https://maroon-fox-767665.hostingersite.com/api";
$email = "admin@gmail.com";
$password = "admin123";

echo "========================================\n";
echo "Testing LIVE API for Green Hotel 2 with Auth\n";
echo "========================================\n\n";

// Step 1: Login to get authentication token
echo "Step 1: Authenticating...\n";
$loginUrl = "{$apiBaseUrl}/login";
$loginData = [
    'email' => $email,
    'password' => $password
];

$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login HTTP Status Code: {$loginHttpCode}\n";

if ($loginHttpCode !== 200) {
    echo "❌ Login failed\n";
    echo "Response: " . substr($loginResponse, 0, 300) . "...\n";
    exit;
}

$loginData = json_decode($loginResponse, true);
if (!isset($loginData['token'])) {
    echo "❌ No token received in login response\n";
    echo "Response: " . substr($loginResponse, 0, 300) . "...\n";
    exit;
}

$token = $loginData['token'];
echo "✅ Login successful! Token received.\n\n";

// Step 2: Get user profile to find owner ID
echo "Step 2: Getting user profile...\n";
$profileUrl = "{$apiBaseUrl}/user-profile";

$ch = curl_init($profileUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);

$profileResponse = curl_exec($ch);
$profileHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Profile HTTP Status Code: {$profileHttpCode}\n";

if ($profileHttpCode !== 200) {
    echo "❌ Failed to get user profile\n";
    echo "Response: " . substr($profileResponse, 0, 300) . "...\n";
    exit;
}

$profileData = json_decode($profileResponse, true);
$userId = $profileData['data']['id'] ?? null;

if (!$userId) {
    echo "❌ No user ID found in profile\n";
    echo "Response: " . substr($profileResponse, 0, 300) . "...\n";
    exit;
}

echo "✅ User profile retrieved. User ID: {$userId}\n\n";

// Step 3: Get owner reservations
echo "Step 3: Getting owner reservations...\n";
$reservationsUrl = "{$apiBaseUrl}/property-owner-reservations/{$userId}?per_page=100&page=1";

$ch = curl_init($reservationsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);

$reservationsResponse = curl_exec($ch);
$reservationsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Reservations HTTP Status Code: {$reservationsHttpCode}\n\n";

if ($reservationsHttpCode !== 200) {
    echo "❌ Failed to get reservations\n";
    echo "Response: " . substr($reservationsResponse, 0, 300) . "...\n";
    exit;
}

$reservationsData = json_decode($reservationsResponse, true);

if (!isset($reservationsData['data'])) {
    echo "❌ Invalid reservations response format\n";
    echo "Response: " . substr($reservationsResponse, 0, 300) . "...\n";
    exit;
}

$reservations = $reservationsData['data'];
echo "✅ Reservations retrieved successfully!\n";
echo "Total reservations: " . count($reservations) . "\n\n";

// Step 4: Find Green Hotel 2 reservations
echo "Step 4: Looking for Green Hotel 2 reservations...\n";

// Look for reservations with "Green hotel" in the property title
$greenHotelReservations = array_filter($reservations, function($reservation) {
    $title = strtolower($reservation['property']['title'] ?? '');
    return strpos($title, 'green hotel') !== false && strpos($title, 'testing room') !== false;
});

echo "Found " . count($greenHotelReservations) . " Green Hotel 2 reservations\n\n";

// Check specifically for reservations 896, 897, 898
$targetIds = [896, 897, 898];
foreach ($targetIds as $id) {
    $foundReservation = null;
    foreach ($reservations as $reservation) {
        if ($reservation['id'] == $id) {
            $foundReservation = $reservation;
            break;
        }
    }
    
    echo "Reservation #{$id}:\n";
    if ($foundReservation) {
        echo "  ✅ FOUND in API response\n";
        $propertyTitle = isset($foundReservation['property']['title']) ? $foundReservation['property']['title'] : 'N/A';
        echo "  Property: {$propertyTitle}\n";
        echo "  Check-in: {$foundReservation['check_in_date']}\n";
        echo "  Check-out: {$foundReservation['check_out_date']}\n";
        echo "  Status: {$foundReservation['status']}\n";
        $paymentMethod = isset($foundReservation['payment_method']) ? $foundReservation['payment_method'] : 'null';
        $paymentStatus = isset($foundReservation['payment_status']) ? $foundReservation['payment_status'] : 'null';
        echo "  Payment method: {$paymentMethod}\n";
        echo "  Payment status: {$paymentStatus}\n";
        echo "  Reservable type: {$foundReservation['reservable_type']}\n";
        echo "  Reservable ID: {$foundReservation['reservable_id']}\n";
        echo "  Created at: {$foundReservation['created_at']}\n";
        
        // Check if flexible
        $paymentMethod = isset($foundReservation['payment_method']) ? $foundReservation['payment_method'] : 'cash';
        $hasPayment = isset($foundReservation['payment']) && !empty($foundReservation['payment']);
        $isFlexible = !($paymentMethod === 'paymob' || $paymentMethod === 'online' || $hasPayment);
        echo "  Is Flexible: " . ($isFlexible ? 'YES' : 'NO') . "\n";
        
        if ($isFlexible) {
            echo "  Will be mapped to: confirmed (display_status)\n";
        }
    } else {
        echo "  ❌ NOT FOUND in API response\n";
    }
    echo "\n";
}

// Show all Green Hotel 2 reservations
echo "=== ALL GREEN HOTEL 2 RESERVATIONS ===\n";
foreach ($greenHotelReservations as $reservation) {
    echo "Reservation #{$reservation['id']}:\n";
    $propertyTitle = isset($reservation['property']['title']) ? $reservation['property']['title'] : 'N/A';
    echo "  Property: {$propertyTitle}\n";
    echo "  Check-in: {$reservation['check_in_date']}\n";
    echo "  Check-out: {$reservation['check_out_date']}\n";
    echo "  Status: {$reservation['status']}\n";
    $paymentMethod = isset($reservation['payment_method']) ? $reservation['payment_method'] : 'null';
    echo "  Payment method: {$paymentMethod}\n";
    echo "  Reservable ID: {$reservation['reservable_id']}\n";
    
    // Check if flexible
    $paymentMethod = isset($reservation['payment_method']) ? $reservation['payment_method'] : 'cash';
    $hasPayment = isset($reservation['payment']) && !empty($reservation['payment']);
    $isFlexible = !($paymentMethod === 'paymob' || $paymentMethod === 'online' || $hasPayment);
    echo "  Is Flexible: " . ($isFlexible ? 'YES' : 'NO') . "\n";
    
    if ($isFlexible) {
        echo "  Will show as: confirmed (display_status)\n";
    }
    echo "\n";
}

// Check for January 1-4, 2026 reservations
echo "=== RESERVATIONS FOR JANUARY 1-4, 2026 ===\n";
$januaryReservations = array_filter($greenHotelReservations, function($reservation) {
    $checkIn = new DateTime($reservation['check_in_date']);
    $checkOut = new DateTime($reservation['check_out_date']);
    $jan1 = new DateTime('2026-01-01');
    $jan4 = new DateTime('2026-01-04');
    
    return ($checkIn <= $jan4 && $checkOut >= $jan1);
});

echo "Found " . count($januaryReservations) . " reservations for Jan 1-4, 2026:\n\n";

foreach ($januaryReservations as $reservation) {
    echo "Reservation #{$reservation['id']}:\n";
    echo "  Check-in: {$reservation['check_in_date']}\n";
    echo "  Check-out: {$reservation['check_out_date']}\n";
    echo "  Status: {$reservation['status']}\n";
    $paymentMethod = isset($reservation['payment_method']) ? $reservation['payment_method'] : 'null';
    echo "  Payment method: {$paymentMethod}\n";
    
    // Check if flexible
    $paymentMethod = isset($reservation['payment_method']) ? $reservation['payment_method'] : 'cash';
    $hasPayment = isset($reservation['payment']) && !empty($reservation['payment']);
    $isFlexible = !($paymentMethod === 'paymob' || $paymentMethod === 'online' || $hasPayment);
    echo "  Is Flexible: " . ($isFlexible ? 'YES' : 'NO') . "\n";
    
    if ($isFlexible) {
        echo "  Will show as: confirmed (display_status)\n";
    }
    echo "\n";
}

echo "========================================\n";
echo "Key Findings from LIVE API:\n";
echo "- This shows the actual data the frontend receives from production\n";
echo "- Check if reservations 896, 897, 898 exist in live data\n";
echo "- Verify their actual status vs what frontend displays\n";
echo "- This will help us understand the discrepancy\n";
echo "========================================\n";