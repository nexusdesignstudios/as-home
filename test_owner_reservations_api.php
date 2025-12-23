<?php

/**
 * Test the owner reservations API response for property 334
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\Reservation;
use App\Models\VacationApartment;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Testing Owner Reservations API Response\n";
echo "========================================\n\n";

$propertyId = 334;
$ownerId = 32; // Customer ID from reservations

// Simulate the API query
$query = Reservation::query();
$query->where('property_id', $propertyId);

$reservations = $query->with([
    'customer:id,name,email,mobile',
    'property:id,title,category_id,price,title_image,property_classification',
    'property.category:id,category,image'
])
    ->orderBy('created_at', 'desc')
    ->get();

echo "Found {$reservations->count()} reservations\n\n";

foreach ($reservations as $reservation) {
    echo "Reservation ID: {$reservation->id}\n";
    echo "  Property ID: {$reservation->property_id}\n";
    echo "  Reservable Type: {$reservation->reservable_type}\n";
    echo "  Status: {$reservation->status}\n";
    
    // Simulate the formatting
    $data = $reservation->toArray();
    $data['property_id'] = $reservation->property_id;
    $data['reservable_id'] = $reservation->reservable_id;
    
    // Parse apartment_id from special_requests
    if (empty($data['apartment_id']) && !empty($reservation->special_requests)) {
        $specialRequests = $reservation->special_requests;
        if (preg_match('/Apartment ID:\s*(\d+)/i', $specialRequests, $aptMatches)) {
            $data['apartment_id'] = (int)$aptMatches[1];
            echo "  ✅ Parsed Apartment ID from special_requests: {$data['apartment_id']}\n";
        }
        if (preg_match('/Quantity:\s*(\d+)/i', $specialRequests, $qtyMatches)) {
            $data['apartment_quantity'] = (int)$qtyMatches[1];
            echo "  ✅ Parsed Apartment Quantity from special_requests: {$data['apartment_quantity']}\n";
        }
    } else {
        echo "  Apartment ID: " . ($data['apartment_id'] ?? 'NULL') . "\n";
    }
    
    if (isset($reservation->property)) {
        $propertyClassification = $reservation->property->getRawOriginal('property_classification') ?? $reservation->property->property_classification;
        $data['property_info'] = [
            'id' => $reservation->property->id,
            'title' => $reservation->property->title,
            'property_classification' => $propertyClassification
        ];
        $data['property_classification'] = $propertyClassification;
        $data['property_details'] = [
            'property_classification' => $propertyClassification
        ];
        
        echo "  ✅ Property Classification: {$propertyClassification}\n";
        echo "  ✅ property_info.property_classification: {$data['property_info']['property_classification']}\n";
        echo "  ✅ property_classification (direct): {$data['property_classification']}\n";
    }
    
    // Check if it would pass frontend filter
    $isPropertyReservation = $data['reservable_type'] === "App\\Models\\Property";
    $matchesProperty = $data['property_id'] == $propertyId;
    $propertyClassification = 
        $data['property_classification'] ??
        $data['property_info']['property_classification'] ??
        $data['reservable']['property_classification'] ??
        $data['property_details']['property_classification'] ?? null;
    
    $isVacationHome = 
        $propertyClassification === 4 ||
        $propertyClassification === "vacation_homes" ||
        !empty($data['apartment_id']) ||
        (!empty($reservation->special_requests) && strpos($reservation->special_requests, "Apartment ID:") !== false);
    
    $wouldPassFilter = $isPropertyReservation && $matchesProperty && $isVacationHome;
    
    echo "  Frontend Filter Check:\n";
    echo "    isPropertyReservation: " . ($isPropertyReservation ? '✅' : '❌') . "\n";
    echo "    matchesProperty: " . ($matchesProperty ? '✅' : '❌') . "\n";
    echo "    isVacationHome: " . ($isVacationHome ? '✅' : '❌') . "\n";
    echo "    Would Pass Filter: " . ($wouldPassFilter ? '✅ YES' : '❌ NO') . "\n";
    echo "\n";
}

