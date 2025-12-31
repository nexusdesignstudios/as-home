<?php

// Analysis of the specific flexible reservations mentioned
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\Property;
use App\Models\HotelRoom;

echo "=== Analysis of Specific Flexible Reservations ===\n\n";

// Search for reservations with the details provided
$searchCriteria = [
    'customer_name' => 'ibrahim ahmed',
    'customer_phone' => '201061874267',
    'customer_email' => 'nexlancer.eg@gmail.com',
    'property_title' => 'Green hotel 2 testing room only'
];

echo "Searching for reservations matching:\n";
echo "Customer: {$searchCriteria['customer_name']}\n";
echo "Phone: {$searchCriteria['customer_phone']}\n";
echo "Email: {$searchCriteria['customer_email']}\n";
echo "Property: {$searchCriteria['property_title']}\n\n";

// Find reservations by customer details
$reservations = Reservation::with(['customer', 'property', 'reservable'])
    ->whereHas('customer', function($query) use ($searchCriteria) {
        $query->where('name', 'like', '%ibrahim ahmed%')
              ->orWhere('mobile', 'like', '%201061874267%')
              ->orWhere('email', 'like', '%nexlancer.eg@gmail.com%');
    })
    ->orWhereHas('property', function($query) {
        $query->where('title', 'like', '%Green hotel 2 testing room only%');
    })
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($reservations->isEmpty()) {
    echo "❌ No reservations found matching the criteria\n";
    
    // Let's search more broadly
    echo "\nSearching for recent reservations with similar patterns...\n";
    $recentReservations = Reservation::with(['customer', 'property', 'reservable'])
        ->where('total_price', '1000')
        ->where('check_in_date', 'like', '2025-12-25%')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
        
    if ($recentReservations->isNotEmpty()) {
        echo "Found {$recentReservations->count()} reservations with EGP 1000 and Dec 25, 2025:\n";
        foreach ($recentReservations as $res) {
            echo "ID: {$res->id}, Customer: " . ($res->customer->name ?? 'N/A') . ", Property: " . ($res->property->title ?? 'N/A') . "\n";
        }
    }
} else {
    echo "✅ Found {$reservations->count()} matching reservations:\n\n";
    
    foreach ($reservations as $reservation) {
        echo "Reservation ID: {$reservation->id}\n";
        echo "Customer: " . ($reservation->customer->name ?? 'N/A') . "\n";
        echo "Phone: " . ($reservation->customer->mobile ?? 'N/A') . "\n";
        echo "Email: " . ($reservation->customer->email ?? 'N/A') . "\n";
        echo "Property: " . ($reservation->property->title ?? 'N/A') . "\n";
        echo "Check-in: {$reservation->check_in_date}\n";
        echo "Check-out: {$reservation->check_out_date}\n";
        echo "Total Price: EGP {$reservation->total_price}\n";
        echo "Status: {$reservation->status}\n";
        echo "Payment Method: {$reservation->payment_method}\n";
        echo "Payment Status: {$reservation->payment_status}\n";
        echo "Created: {$reservation->created_at}\n";
        
        // Check if it should be flexible
        $shouldBeFlexible = false;
        if ($reservation->property && $reservation->property->refund_policy === 'flexible') {
            $shouldBeFlexible = true;
        }
        
        echo "Property Refund Policy: " . ($reservation->property->refund_policy ?? 'NULL') . "\n";
        echo "Should be flexible: " . ($shouldBeFlexible ? 'YES' : 'NO') . "\n";
        echo "Currently flexible: " . ($reservation->status === 'confirmed' && $reservation->payment_method === 'cash' ? 'YES' : 'NO') . "\n";
        
        if ($reservation->reservable_type === 'App\Models\HotelRoom' && $reservation->reservable) {
            echo "Room Number: {$reservation->reservable->room_number}\n";
            echo "Room Refund Policy: " . ($reservation->reservable->refund_policy ?? 'NULL') . "\n";
        }
        
        echo "---\n";
    }
}

echo "\n=== Property Analysis ===\n";
$greenHotelProperty = Property::where('title', 'like', '%Green hotel 2 testing room only%')->first();

if ($greenHotelProperty) {
    echo "Green Hotel Property Details:\n";
    echo "ID: {$greenHotelProperty->id}\n";
    echo "Title: {$greenHotelProperty->title}\n";
    echo "Classification: {$greenHotelProperty->property_classification}\n";
    echo "Refund Policy: " . ($greenHotelProperty->refund_policy ?? 'NULL') . "\n";
    
    echo "\nHotel Rooms for this property:\n";
    $rooms = HotelRoom::where('property_id', $greenHotelProperty->id)->get();
    foreach ($rooms as $room) {
        echo "Room ID: {$room->id}, Number: {$room->room_number}, Refund Policy: " . ($room->refund_policy ?? 'NULL') . "\n";
    }
} else {
    echo "❌ Green hotel property not found\n";
}

echo "\n=== Issue Analysis ===\n";
if ($greenHotelProperty && $greenHotelProperty->refund_policy !== 'flexible') {
    echo "⚠️  The Green hotel property does NOT have a flexible refund policy!\n";
    echo "Current policy: " . ($greenHotelProperty->refund_policy ?? 'NULL') . "\n";
    echo "This explains why these reservations are not behaving as flexible reservations.\n";
    echo "\nTo make them flexible, you need to:\n";
    echo "1. Update the property's refund_policy to 'flexible'\n";
    echo "2. Or update individual room refund policies to 'flexible'\n";
}