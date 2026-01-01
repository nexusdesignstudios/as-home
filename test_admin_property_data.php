<?php

// Test script to verify property data appears in admin dashboard for flexible reservations
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;

echo "=== Testing Admin Dashboard Property Data Loading ===\n\n";

// Test the problematic reservations 893, 894
$testIds = [893, 894, 895];

foreach ($testIds as $id) {
    echo "Reservation {$id}:\n";
    
    // Load reservation as the admin controller would (simulating 'all' type query)
    $reservation = Reservation::with([
        'customer:id,name,email,mobile',
        'reservable',
        'reservable.property:id,title,property_classification',
        'reservable.roomType:id,name',
        'payment:id,reservation_id,status'
    ])->find($id);
    
    if (!$reservation) {
        echo "  Not found!\n\n";
        continue;
    }
    
    echo "  Status: {$reservation->status}\n";
    echo "  Payment Method: " . ($reservation->payment_method ?? 'null') . "\n";
    echo "  Reservable Type: {$reservation->reservable_type}\n";
    
    // Simulate the admin controller logic to get property name and type
    $propertyName = 'N/A';
    $propertyType = 'N/A';
    
    if ($reservation->reservable_type === 'App\Models\Property') {
        $reservable = $reservation->reservable;
        if ($reservable) {
            $propertyName = $reservable->title ?? 'N/A';
            $propertyClassification = $reservable->getRawOriginal('property_classification');
            
            if ($propertyClassification == 4) {
                $propertyType = 'Vacation Home';
            } elseif ($propertyClassification == 5) {
                $propertyType = 'Hotel Property';
            } else {
                $propertyType = 'Property';
            }
        }
    } elseif ($reservation->reservable_type === 'App\Models\HotelRoom') {
        $reservable = $reservation->reservable;
        if ($reservable) {
            // Check if property relationship is loaded
            if ($reservable->relationLoaded('property') && $reservable->property) {
                $propertyName = $reservable->property->title ?? 'N/A';
            } else {
                $propertyName = 'Property NOT LOADED';
            }
            
            $propertyType = 'Hotel Room';
            
            // Add room type if available
            if ($reservable->relationLoaded('roomType') && $reservable->roomType) {
                $propertyName .= ' - ' . $reservable->roomType->name;
            }
            
            // Determine flexible vs non-refundable
            $paymentMethod = $reservation->payment_method ?? 'cash';
            $isOnlinePayment = ($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment);
            if (!$isOnlinePayment) {
                $propertyType .= ' (Flexible)';
            } else {
                $propertyType .= ' (Non-Refundable)';
            }
        }
    }
    
    echo "  Property Name: {$propertyName}\n";
    echo "  Property Type: {$propertyType}\n";
    
    // Check if relationships were actually loaded
    if ($reservation->reservable_type === 'App\Models\HotelRoom') {
        $reservable = $reservation->reservable;
        if ($reservable) {
            echo "  Property relationship loaded: " . ($reservable->relationLoaded('property') ? 'YES' : 'NO') . "\n";
            echo "  RoomType relationship loaded: " . ($reservable->relationLoaded('roomType') ? 'YES' : 'NO') . "\n";
            
            if ($reservable->relationLoaded('property') && $reservable->property) {
                echo "  Property ID: {$reservable->property->id}\n";
                echo "  Property Title: {$reservable->property->title}\n";
            }
        }
    }
    
    echo "\n";
}

echo "=== Summary ===\n";
echo "If property data shows 'N/A' or 'NOT LOADED', the fix needs adjustment.\n";
echo "If property data shows actual names, the fix is working correctly.\n\n";