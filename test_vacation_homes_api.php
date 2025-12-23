<?php

/**
 * Test the actual API endpoint for vacation homes search
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\VacationApartment;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Testing Vacation Homes API Endpoint\n";
echo "========================================\n\n";

// Simulate the exact query from getPropertyList method
$offset = 0;
$limit = 10;

echo "1. Building Query (matching API logic):\n";
$propertyQuery = Property::whereIn('propery_type', [0, 1])
    ->where(function ($query) {
        return $query->where(['status' => 1, 'request_status' => 'approved']);
    })
    ->where('property_classification', 4);

echo "   Base query conditions:\n";
echo "   - propery_type IN [0, 1]\n";
echo "   - status = 1\n";
echo "   - request_status = 'approved'\n";
echo "   - property_classification = 4\n\n";

// Save base query for count
$baseQueryForCount = $propertyQuery->clone();
$totalProperties = $baseQueryForCount->count();
echo "2. Total Properties Count: {$totalProperties}\n\n";

// Apply ordering
$propertyQuery = $propertyQuery->clone()->orderBy('id', 'DESC');

// Get properties with apartments
$propertiesData = $propertyQuery->clone()
    ->with([
        'category:id,category,image,slug_id,parameter_types', 
        'vacationApartments' => function($query) {
            $query->where('status', 1);
        }, 
        'assignParameter.parameter'
    ])
    ->select('id', 'slug_id', 'propery_type', 'title_image', 'category_id', 'title', 'price', 'city', 'state', 'country', 'rentduration', 'added_by', 'is_premium', 'property_classification', 'rent_package', 'latitude', 'longitude', 'total_click')
    ->withCount('favourite')
    ->skip($offset)
    ->take($limit)
    ->get();

echo "3. Properties Fetched (after skip/take):\n";
echo "   Offset: {$offset}, Limit: {$limit}\n";
echo "   Properties returned: {$propertiesData->count()}\n\n";

// Expand vacation homes
echo "4. Expanding Vacation Homes:\n";
$expandedProperties = collect();
foreach ($propertiesData as $property) {
    echo "   Processing Property ID {$property->id} ({$property->title}):\n";
    
    // Use relationLoaded to check if relationship was eager loaded, otherwise use the accessor
    $vacationApartments = null;
    if ($property->relationLoaded('vacationApartments')) {
        $vacationApartments = $property->getRelation('vacationApartments');
        echo "      - Relationship loaded via eager loading, count: " . ($vacationApartments ? $vacationApartments->count() : 'NULL') . "\n";
    } else {
        // Fallback to accessor if relationship wasn't loaded
        $vacationApartments = $property->vacationApartments;
        echo "      - Using accessor, count: " . ($vacationApartments ? $vacationApartments->count() : 'NULL') . "\n";
    }
    
    // Also try direct query to see what's in DB
    $directCount = \App\Models\VacationApartment::where('property_id', $property->id)->count();
    $activeCount = \App\Models\VacationApartment::where('property_id', $property->id)->where('status', 1)->count();
    echo "      - Direct DB query: Total={$directCount}, Active={$activeCount}\n";
    
    // Check property classification - might be string or int
    $classification = $property->getRawOriginal('property_classification') ?? $property->property_classification;
    $isVacationHome = ($classification == 4 || $classification === 4 || (int)$classification === 4);
    
    echo "      - Property classification: " . ($property->property_classification ?? 'NULL') . " (raw: " . ($property->getRawOriginal('property_classification') ?? 'NULL') . ")\n";
    echo "      - Is vacation home check: " . ($isVacationHome ? 'YES' : 'NO') . "\n";
    echo "      - Apartments count check: " . ($vacationApartments && $vacationApartments->count() > 0 ? 'YES' : 'NO') . "\n";
    
    if ($isVacationHome && 
        $vacationApartments && 
        $vacationApartments->count() > 0) {
        
        $aptCount = $vacationApartments->count();
        echo "      - Has {$aptCount} active apartments\n";
        
        foreach ($vacationApartments as $apartment) {
            $apartmentProperty = clone $property;
            $apartmentProperty->apartment_id = $apartment->id;
            $apartmentProperty->parent_property_id = $property->id;
            $apartmentProperty->title = $property->title . ' - ' . $apartment->apartment_number;
            $apartmentProperty->is_apartment = true;
            $expandedProperties->push($apartmentProperty);
            echo "        → Created listing for apartment #{$apartment->apartment_number} (ID: {$apartment->id})\n";
        }
    } else {
        echo "      - No apartments or not vacation home, adding as single listing\n";
        $property->is_apartment = false;
        $expandedProperties->push($property);
    }
}

echo "\n5. Final Results:\n";
echo "   Total expanded listings: {$expandedProperties->count()}\n";
echo "   Listings:\n";
foreach ($expandedProperties as $listing) {
    if (isset($listing->is_apartment) && $listing->is_apartment) {
        echo "      - {$listing->title} (Apartment ID: {$listing->apartment_id})\n";
    } else {
        echo "      - {$listing->title} (Property ID: {$listing->id})\n";
    }
}

// Calculate total count adjustment
echo "\n6. Total Count Calculation:\n";
$vacationHomesQuery = $baseQueryForCount->clone()
    ->where('property_classification', 4)
    ->with([
        'vacationApartments' => function($query) {
            $query->where('status', 1);
        }
    ])
    ->get();

$additionalListings = 0;
foreach ($vacationHomesQuery as $property) {
    if ($property->vacationApartments && $property->vacationApartments->count() > 0) {
        $additionalListings += $property->vacationApartments->count() - 1;
    }
}
$adjustedTotal = $totalProperties + $additionalListings;

echo "   Base properties: {$totalProperties}\n";
echo "   Additional listings from apartments: {$additionalListings}\n";
echo "   Adjusted total: {$adjustedTotal}\n";

echo "\n========================================\n";
echo "API RESPONSE SIMULATION\n";
echo "========================================\n";
echo "{\n";
echo "  \"error\": false,\n";
echo "  \"total\": {$adjustedTotal},\n";
echo "  \"data\": [\n";
foreach ($expandedProperties->take(3) as $listing) {
    echo "    {\"id\": {$listing->id}, \"title\": \"{$listing->title}\", \"is_apartment\": " . (isset($listing->is_apartment) && $listing->is_apartment ? 'true' : 'false') . "},\n";
}
echo "    ... ({$expandedProperties->count()} total)\n";
echo "  ],\n";
echo "  \"message\": \"Data fetched Successfully\"\n";
echo "}\n";

echo "\n";
if ($expandedProperties->count() < 6) {
    echo "⚠️  WARNING: Only {$expandedProperties->count()} listings returned, expected 6!\n";
    echo "   This suggests a problem with the expansion logic or apartment loading.\n";
} else {
    echo "✅ All expected listings are being generated correctly.\n";
}

