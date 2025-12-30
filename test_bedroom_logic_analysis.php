<?php

// Test script to understand the bedroom filtering requirement

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Understanding Bedroom Filter Requirements ===\n\n";

// Based on the user's concern, they want:
// - When filtering for "1 bedroom", show only properties that are primarily 1 bedroom
// - When filtering for "studio", show only properties that are primarily studio
// - Properties with "studio" in assign_parameters but 1 bedroom vacation apartments should NOT appear when filtering for "1 bedroom"

echo "Current issue: Property 333 has 'studio' in assign_parameters but 1-bedroom vacation apartments.\n";
echo "When user filters for '1 bedroom', Property 333 appears because it has 1-bedroom vacation apartments.\n";
echo "But user wants 'studio' properties to NOT appear when filtering for '1 bedroom'.\n\n";

// Let's check what the "correct" behavior should be
echo "=== Proposed Solution ===\n";
echo "For vacation homes (property_classification = 4), the bedroom filter should:\n";
echo "1. If property has assign_parameters bedroom value, use that as primary\n";
echo "2. If property has vacation_apartments but NO assign_parameters bedroom value, use vacation_apartments\n";
echo "3. If property has 'studio' in assign_parameters, it should ONLY appear for studio filter, not for 1,2,3 bedroom filters\n\n";

// Test this logic
echo "=== Testing New Logic ===\n";

$testProperties = [333, 334];
$bedroomsFilter = "1"; // Testing 1 bedroom filter

echo "Testing bedrooms filter: '$bedroomsFilter'\n\n";

foreach ($testProperties as $propertyId) {
    echo "--- Property ID: $propertyId ---\n";
    
    // Get property info
    $property = DB::table('propertys')
        ->where('id', $propertyId)
        ->select('id', 'title', 'property_classification')
        ->first();
    
    echo "Title: {$property->title}\n";
    
    // Check assign_parameters bedroom value
    $assignBedroom = DB::table('assign_parameters')
        ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
        ->where(function($q) use ($propertyId) {
            $q->where('assign_parameters.property_id', $propertyId)
              ->orWhere(function($q2) use ($propertyId) {
                  $q2->where('assign_parameters.modal_id', $propertyId)
                     ->where('assign_parameters.modal_type', 'like', '%Property%');
              });
        })
        ->where(function($q) {
            $q->where('parameters.name', 'LIKE', '%bedroom%')
              ->orWhere('parameters.name', 'LIKE', '%bed%');
        })
        ->select('assign_parameters.value')
        ->first();
    
    $assignBedroomValue = $assignBedroom ? strtolower(trim($assignBedroom->value)) : null;
    echo "Assign Parameters Bedroom: " . ($assignBedroomValue ?: 'none') . "\n";
    
    // Check vacation_apartments
    $vacationApts = DB::table('vacation_apartments')
        ->where('property_id', $propertyId)
        ->where('status', 1)
        ->select('bedrooms')
        ->get();
    
    echo "Vacation Apartments Bedrooms: " . $vacationApts->pluck('bedrooms')->implode(', ') . "\n";
    
    // Apply new logic
    $shouldShow = false;
    $reason = "";
    
    if ($assignBedroomValue) {
        // If property has assign_parameters bedroom value, use that as primary
        if ($assignBedroomValue === 'studio' && $bedroomsFilter === '0') {
            $shouldShow = true;
            $reason = "Matches studio in assign_parameters";
        } elseif ($assignBedroomValue === $bedroomsFilter) {
            $shouldShow = true;
            $reason = "Matches bedroom value in assign_parameters";
        } elseif ($assignBedroomValue === 'studio' && $bedroomsFilter !== '0') {
            $shouldShow = false;
            $reason = "Has studio in assign_parameters but filtering for non-studio - should NOT show";
        } else {
            $shouldShow = false;
            $reason = "Assign parameters bedroom value doesn't match filter";
        }
    } else {
        // If no assign_parameters bedroom value, check vacation_apartments
        foreach ($vacationApts as $apt) {
            if ($apt->bedrooms == $bedroomsFilter) {
                $shouldShow = true;
                $reason = "Matches bedroom in vacation_apartments (no assign_parameters data)";
                break;
            }
        }
        if (!$shouldShow) {
            $reason = "No matching bedroom in vacation_apartments and no assign_parameters data";
        }
    }
    
    echo "NEW LOGIC: Should show for '$bedroomsFilter' bedroom filter? " . ($shouldShow ? 'YES' : 'NO') . "\n";
    echo "Reason: $reason\n\n";
}