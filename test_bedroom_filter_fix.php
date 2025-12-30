<?php

// Test script to verify the bedroom filter fix

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Testing Bedroom Filter Fix ===\n\n";

// Test the specific properties that were causing issues
$testProperties = [333, 334];
$bedroomsFilters = ['0', '1', '2']; // Test studio, 1 bedroom, and 2 bedroom filters

foreach ($bedroomsFilters as $bedroomsFilter) {
    echo "\n=== Testing Bedrooms Filter: '$bedroomsFilter' ===\n";
    
    foreach ($testProperties as $propertyId) {
        echo "\n--- Property ID: $propertyId ---\n";
        
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
        
        // Test the NEW logic
        $bedroomsValue = $bedroomsFilter;
        $bedroomsIntValue = (int) $bedroomsValue;
        $isStudio = ($bedroomsValue === '0');
        
        $shouldShow = false;
        $reason = "";
        
        // Simulate the new vacation homes logic
        if ($property->property_classification == 4) {
            // Check if property has assign_parameters bedroom data
            $hasAssignBedroom = DB::table('assign_parameters')
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
                ->exists();
            
            if ($hasAssignBedroom) {
                // Property has assign_parameters bedroom data - use that as primary
                if ($assignBedroomValue === 'studio' && $isStudio) {
                    $shouldShow = true;
                    $reason = "✅ Matches studio in assign_parameters";
                } elseif ($assignBedroomValue === $bedroomsValue) {
                    $shouldShow = true;
                    $reason = "✅ Matches bedroom value in assign_parameters";
                } elseif ($assignBedroomValue === 'studio' && !$isStudio) {
                    $shouldShow = false;
                    $reason = "❌ Has studio in assign_parameters but filtering for non-studio - should NOT show";
                } else {
                    $shouldShow = false;
                    $reason = "❌ Assign parameters bedroom value doesn't match filter";
                }
            } else {
                // No assign_parameters bedroom data - fallback to vacation_apartments
                foreach ($vacationApts as $apt) {
                    if ($apt->bedrooms == $bedroomsIntValue) {
                        if (!$isStudio && $apt->bedrooms == 0) {
                            continue; // Skip studio apartments for non-studio filters
                        }
                        $shouldShow = true;
                        $reason = "✅ Matches bedroom in vacation_apartments (no assign_parameters data)";
                        break;
                    }
                }
                if (!$shouldShow) {
                    $reason = "❌ No matching bedroom in vacation_apartments and no assign_parameters data";
                }
            }
        } else {
            // For non-vacation homes, use the original logic
            $reason = "(Regular property - using original logic)";
        }
        
        echo "NEW LOGIC: Should show for '$bedroomsValue' bedroom filter? " . ($shouldShow ? 'YES' : 'NO') . "\n";
        echo "Reason: $reason\n";
    }
}

echo "\n\n=== Summary ===\n";
echo "✅ Property 333 (studio in assign_parameters):\n";
echo "   - Should NOT appear for '1' bedroom filter\n";
echo "   - Should NOT appear for '2' bedroom filter\n";
echo "   - Should ONLY appear for '0' (studio) filter\n\n";

echo "✅ Property 334 (1 in assign_parameters):\n";
echo "   - Should appear for '1' bedroom filter\n";
echo "   - Should NOT appear for '0' (studio) filter\n";
echo "   - Should NOT appear for '2' bedroom filter\n";