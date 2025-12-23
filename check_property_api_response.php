<?php

/**
 * Check what the API returns for property ID 334
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;

echo "========================================\n";
echo "Checking API Response for Property 334\n";
echo "========================================\n\n";

$property = Property::with('vacationApartments')
    ->where('id', 334)
    ->first();

if (!$property) {
    echo "❌ Property not found\n";
    exit;
}

echo "Property ID: {$property->id}\n";
echo "Title: {$property->title}\n";
echo "Classification: {$property->property_classification}\n";
echo "\n";

// Check what the API would return
$propertyData = [
    'id' => $property->id,
    'title' => $property->title,
    'property_classification' => $property->property_classification,
    'available_dates' => $property->available_dates,
    'vacation_apartments' => []
];

if ($property->relationLoaded('vacationApartments')) {
    foreach ($property->vacationApartments as $apt) {
        $propertyData['vacation_apartments'][] = [
            'id' => $apt->id,
            'apartment_number' => $apt->apartment_number,
            'status' => $apt->status,
            'quantity' => $apt->quantity,
            'available_dates' => $apt->available_dates,
            'availability_type' => $apt->availability_type
        ];
    }
} else {
    $apartments = $property->vacationApartments;
    foreach ($apartments as $apt) {
        $propertyData['vacation_apartments'][] = [
            'id' => $apt->id,
            'apartment_number' => $apt->apartment_number,
            'status' => $apt->status,
            'quantity' => $apt->quantity,
            'available_dates' => $apt->available_dates,
            'availability_type' => $apt->availability_type
        ];
    }
}

echo "Property available_dates: ";
if (empty($propertyData['available_dates'])) {
    echo "❌ EMPTY\n";
} else {
    echo "✅ Found\n";
    print_r($propertyData['available_dates']);
}
echo "\n";

echo "Vacation Apartments: " . count($propertyData['vacation_apartments']) . "\n";
foreach ($propertyData['vacation_apartments'] as $apt) {
    echo "\n  Apartment ID: {$apt['id']}\n";
    echo "  Number: {$apt['apartment_number']}\n";
    echo "  Status: " . ($apt['status'] ? 'Active' : 'Inactive') . "\n";
    echo "  Quantity: {$apt['quantity']}\n";
    echo "  Available Dates: ";
    if (empty($apt['available_dates'])) {
        echo "❌ EMPTY\n";
    } else {
        echo "✅ Found (" . count($apt['available_dates']) . " ranges)\n";
        foreach ($apt['available_dates'] as $range) {
            echo "    - {$range['from']} to {$range['to']} (type: {$range['type']})\n";
        }
    }
}

echo "\n========================================\n";
echo "ISSUE ANALYSIS\n";
echo "========================================\n";
echo "The frontend component uses:\n";
echo "  selectedApartment?.available_dates || getPropData.available_dates\n";
echo "\n";
echo "Current state:\n";
echo "  - getPropData.available_dates: " . (empty($propertyData['available_dates']) ? "❌ EMPTY" : "✅ Has data") . "\n";
echo "  - selectedApartment: Not selected initially\n";
echo "\n";
echo "SOLUTION:\n";
echo "The frontend should:\n";
echo "1. Use the first apartment's available_dates if no apartment is selected yet, OR\n";
echo "2. Set available_dates at the property level, OR\n";
echo "3. Auto-select the first apartment when component loads\n";

