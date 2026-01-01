<?php

/**
 * Test the LIVE API response for Green Hotel 2 reservations
 * This will show us the actual data the frontend is receiving
 */

// Live API URL from the frontend .env file
$apiBaseUrl = "https://maroon-fox-767665.hostingersite.com/api";

// We need to find the owner ID for Green Hotel 2 first
// Let's check the database for the property owner
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\User;

echo "========================================\n";
echo "Testing LIVE API for Green Hotel 2\n";
echo "========================================\n\n";

// Find Green Hotel 2 in local database to get owner ID
$property = Property::where('title', 'like', '%Green hotel 2 testing room only%')->first();

if (!$property) {
    echo "❌ Green Hotel 2 not found in local database\n";
    echo "Let's check if we can find it by searching for properties with similar names...\n\n";
    
    $similarProperties = Property::where('title', 'like', '%Green hotel%')
        ->orWhere('title', 'like', '%testing room%')
        ->get();
    
    if ($similarProperties->count() > 0) {
        echo "Found similar properties:\n";
        foreach ($similarProperties as $prop) {
            echo "- ID: {$prop->id}, Title: {$prop->title}, Owner: {$prop->user_id}\n";
        }
        echo "\n";
    }
    
    // Let's try to find the owner by searching for users
    $users = User::where('email', 'like', '%nexlancer%')->get();
    if ($users->count() > 0) {
        echo "Found users with nexlancer email:\n";
        foreach ($users as $user) {
            echo "- ID: {$user->id}, Email: {$user->email}, Name: {$user->name}\n";
        }
        echo "\n";
    }
    
    exit;
}

echo "Property found in local database:\n";
echo "- ID: {$property->id}\n";
echo "- Title: {$property->title}\n";
echo "- Owner ID: {$property->user_id}\n\n";

// Now let's try to call the live API
$ownerId = $property->user_id;
$apiUrl = "{$apiBaseUrl}/property-owner-reservations/{$property->user_id}?per_page=100&page=1";

echo "Calling LIVE API: {$apiUrl}\n\n";

// Make the API call
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: {$httpCode}\n\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    
    if (isset($data['data'])) {
        echo "✅ API Response received successfully!\n\n";
        
        $reservations = $data['data'];
        echo "Total reservations returned: " . count($reservations) . "\n\n";
        
        // Look for Green Hotel 2 reservations
        $greenHotelReservations = array_filter($reservations, function($reservation) use ($property) {
            return $reservation['property_id'] == $property->id;
        });
        
        echo "Reservations for Green Hotel 2 (Property ID: {$property->id}):\n";
        echo "Found " . count($greenHotelReservations) . " reservations\n\n";
        
        // Check specifically for reservations 896, 897, 898
        $targetIds = [896, 897, 898];
        foreach ($targetIds as $id) {
            $foundReservation = null;
            foreach ($greenHotelReservations as $reservation) {
                if ($reservation['id'] == $id) {
                    $foundReservation = $reservation;
                    break;
                }
            }
            
            echo "Reservation #{$id}:\n";
            if ($foundReservation) {
                echo "  ✅ FOUND in API response\n";
                echo "  Check-in: {$foundReservation['check_in_date']}\n";
                echo "  Check-out: {$foundReservation['check_out_date']}\n";
                echo "  Status: {$foundReservation['status']}\n";
                echo "  Payment method: " . ($foundReservation['payment_method'] ?? 'null') . "\n";
                echo "  Payment status: " . ($foundReservation['payment_status'] ?? 'null') . "\n";
                echo "  Reservable type: {$foundReservation['reservable_type']}\n";
                echo "  Reservable ID: {$foundReservation['reservable_id']}\n";
                echo "  Created at: {$foundReservation['created_at']}\n";
                
                // Check if flexible
                $paymentMethod = $foundReservation['payment_method'] ?? 'cash';
                $isFlexible = !($paymentMethod === 'paymob' || $paymentMethod === 'online' || !empty($foundReservation['payment']));
                echo "  Is Flexible: " . ($isFlexible ? 'YES' : 'NO') . "\n";
                
                if ($isFlexible) {
                    echo "  Will be mapped to: confirmed (display_status)\n";
                }
            } else {
                echo "  ❌ NOT FOUND in API response\n";
            }
            echo "\n";
        }
        
        // Show all Green Hotel 2 reservations for January 1-4, 2026
        echo "=== ALL GREEN HOTEL 2 RESERVATIONS ===\n";
        foreach ($greenHotelReservations as $reservation) {
            echo "Reservation #{$reservation['id']}:\n";
            echo "  Check-in: {$reservation['check_in_date']}\n";
            echo "  Check-out: {$reservation['check_out_date']}\n";
            echo "  Status: {$reservation['status']}\n";
            echo "  Payment method: " . ($reservation['payment_method'] ?? 'null') . "\n";
            echo "  Reservable ID: {$reservation['reservable_id']}\n";
            
            // Check if flexible
            $paymentMethod = $reservation['payment_method'] ?? 'cash';
            $isFlexible = !($paymentMethod === 'paymob' || $paymentMethod === 'online' || !empty($reservation['payment']));
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
            echo "  Payment method: " . ($reservation['payment_method'] ?? 'null') . "\n";
            
            // Check if flexible
            $paymentMethod = $reservation['payment_method'] ?? 'cash';
            $isFlexible = !($paymentMethod === 'paymob' || $paymentMethod === 'online' || !empty($reservation['payment']));
            echo "  Is Flexible: " . ($isFlexible ? 'YES' : 'NO') . "\n";
            
            if ($isFlexible) {
                echo "  Will show as: confirmed (display_status)\n";
            }
            echo "\n";
        }
        
    } else {
        echo "❌ Invalid API response format\n";
        echo "Response: " . substr($response, 0, 500) . "...\n";
    }
} else {
    echo "❌ API call failed\n";
    echo "Response: " . substr($response, 0, 500) . "...\n";
}

echo "========================================\n";
echo "Key Findings from LIVE API:\n";
echo "- This shows the actual data the frontend receives\n";
echo "- Check if reservations 896, 897, 898 exist in live data\n";
echo "- Verify their actual status vs what frontend displays\n";
echo "========================================\n";