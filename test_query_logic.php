<?php

// Test the actual query logic step by step
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "=== Testing Vacation Homes Query Logic Step by Step ===\n";

// First, let's see what properties are vacation homes
$vacationHomes = DB::table('propertys')
    ->where('property_classification', 4)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->select('id', 'title', 'property_classification')
    ->get();

echo "Total vacation homes: " . count($vacationHomes) . "\n\n";

foreach ($vacationHomes as $property) {
    echo "Property ID: {$property->id} - {$property->title}\n";
    
    // Check assign_parameters bedroom
    $assignBedroom = DB::table('assign_parameters')
        ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
        ->where(function($q) use ($property) {
            $q->where('assign_parameters.property_id', $property->id)
              ->orWhere(function($q2) use ($property) {
                  $q2->where('assign_parameters.modal_id', $property->id)
                     ->where('assign_parameters.modal_type', 'like', '%Property%');
              });
        })
        ->where('parameters.name', 'bedrooms')
        ->select('assign_parameters.value')
        ->first();
    
    echo "  Assign Parameters Bedroom: " . ($assignBedroom ? $actualAssignParams->value : 'None') . "\n";
    
    // Check vacation apartments
    $vacationApts = DB::table('vacation_apartments')
        ->where('property_id', $property->id)
        ->where('status', 1)
        ->select('bedrooms')
        ->get();
    
    echo "  Vacation Apartments Bedrooms: " . implode(', ', array_map(function($apt) { return $apt->bedrooms; }, $vacationApts->toArray())) . "\n";
    
    // Test studio filter (bedrooms=0)
    echo "  Studio Filter (bedrooms=0): ";
    if ($assignBedroom && ($assignBedroom->value === 'studio' || $assignBedroom->value === 'Studio' || $assignBedroom->value === '0')) {
        echo "✅ MATCH (studio in assign_parameters)\n";
    } elseif (!$assignBedroom && $vacationApts->contains(function($apt) { return $apt->bedrooms == 0; })) {
        echo "✅ MATCH (studio in vacation_apartments)\n";
    } else {
        echo "❌ NO MATCH\n";
    }
    
    // Test 1 bedroom filter (bedrooms=1)
    echo "  1 Bedroom Filter (bedrooms=1): ";
    if ($assignBedroom && $assignBedroom->value === '1') {
        echo "✅ MATCH (1 in assign_parameters)\n";
    } elseif (!$assignBedroom && $vacationApts->contains(function($apt) { return $apt->bedrooms == 1; })) {
        echo "✅ MATCH (1 in vacation_apartments)\n";
    } else {
        echo "❌ NO MATCH\n";
    }
    
    echo "---\n";
}