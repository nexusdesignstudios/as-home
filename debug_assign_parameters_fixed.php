<?php

// Check what assign_parameters bedroom data exists
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "=== Checking Assign Parameters Bedroom Data ===\n";

$assignBedrooms = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where('parameters.name', 'bedrooms')
    ->whereNotNull('assign_parameters.value')
    ->where('assign_parameters.value', '!=', '')
    ->where('assign_parameters.value', '!=', 'null')
    ->select('assign_parameters.*', 'parameters.name as param_name')
    ->get();

echo "Total assign_parameters bedroom records: " . count($assignBedrooms) . "\n\n";

foreach ($assignBedrooms as $record) {
    echo "Property ID: {$record->property_id}\n";
    echo "Modal ID: {$record->modal_id}\n";
    echo "Modal Type: {$record->modal_type}\n";
    echo "Parameter Value: {$record->value}\n";
    echo "---\n";
}

// Also check what properties have vacation apartments
echo "\n=== Checking Vacation Apartments ===\n";
$vacationApts = DB::table('vacation_apartments')
    ->where('status', 1)
    ->select('property_id', 'bedrooms', DB::raw('COUNT(*) as count'))
    ->groupBy('property_id', 'bedrooms')
    ->get();

foreach ($vacationApts as $apt) {
    echo "Property ID: {$apt->property_id} - {$apt->bedrooms} bedrooms (count: {$apt->count})\n";
}

// Now let's test the actual logic step by step
echo "\n=== Testing ApiController Logic Step by Step ===\n";

// Test property 333 (Test 01-20-12-2025)
$propertyId = 333;

// Check assign_parameters
$assignBedroom = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where(function($q) use ($propertyId) {
        $q->where('assign_parameters.property_id', $propertyId)
          ->orWhere(function($q2) use ($propertyId) {
              $q2->where('assign_parameters.modal_id', $propertyId)
                 ->where('assign_parameters.modal_type', 'like', '%Property%');
          });
    })
    ->where('parameters.name', 'bedrooms')
    ->whereNotNull('assign_parameters.value')
    ->where('assign_parameters.value', '!=', '')
    ->where('assign_parameters.value', '!=', 'null')
    ->select('assign_parameters.value')
    ->first();

echo "Property 333 assign_parameters bedroom: " . ($assignBedroom ? $assignBedroom->value : 'None') . "\n";

// Check vacation apartments
$vacationApts = DB::table('vacation_apartments')
    ->where('property_id', $propertyId)
    ->where('status', 1)
    ->select('bedrooms')
    ->get();

echo "Property 333 vacation_apartments bedrooms: " . implode(', ', array_unique(array_map(function($apt) { return $apt->bedrooms; }, $vacationApts->toArray()))) . "\n";

// Test the logic
if ($assignBedroom) {
    echo "✅ Would use assign_parameters value: {$assignBedroom->value}\n";
} else {
    echo "❌ No assign_parameters, would check vacation_apartments\n";
    foreach ($vacationApts as $apt) {
        echo "   - Vacation apartment with {$apt->bedrooms} bedrooms\n";
    }
}