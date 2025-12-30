<?php

// Test why the fallback logic isn't working for property 333
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

// Test property 333 specifically
echo "=== Testing Property 333 Logic ===\n";

// Check if property 333 has any assign_parameters for bedrooms
$assignParams = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where(function($q) {
        $q->where('assign_parameters.property_id', 333)
          ->orWhere(function($q2) {
              $q2->where('assign_parameters.modal_id', 333)
                 ->where(function($typeQuery) {
                     $typeQuery->where('assign_parameters.modal_type', 'like', '%Property%')
                               ->orWhere('assign_parameters.modal_type', 'like', '%property%');
                 });
          });
    })
    ->where('parameters.name', 'bedrooms')
    ->select('assign_parameters.value', 'assign_parameters.modal_type')
    ->first();

echo "Assign Parameters for property 333:\n";
echo "  Found: " . ($assignParams ? 'Yes' : 'No') . "\n";
if ($assignParams) {
    echo "  Value: {$assignParams->value}\n";
    echo "  Modal Type: {$assignParams->modal_type}\n";
}

// Check vacation_apartments for property 333
echo "\nVacation Apartments for property 333:\n";
$vacationApts = DB::table('vacation_apartments')
    ->where('property_id', 333)
    ->where('status', 1)
    ->select('bedrooms', 'apartment_number')
    ->get();

foreach ($vacationApts as $apt) {
    echo "  Apartment {$apt->apartment_number}: {$apt->bedrooms} bedrooms\n";
}

// Now test the current API logic step by step
echo "\n=== Current API Logic Analysis ===\n";

// Step 1: Check if assign_parameters exists (this determines if fallback triggers)
$assignParamsExists = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where(function($q) {
        $q->where('assign_parameters.property_id', 333)
          ->orWhere(function($q2) {
              $q2->where('assign_parameters.modal_id', 333)
                 ->where(function($typeQuery) {
                     $typeQuery->where('assign_parameters.modal_type', 'like', '%Property%')
                               ->orWhere('assign_parameters.modal_type', 'like', '%property%');
                 });
          });
    })
    ->where('parameters.name', 'bedrooms')
    ->exists();

echo "1. Assign parameters for bedrooms exists: " . ($assignParamsExists ? 'Yes' : 'No') . "\n";

if ($assignParamsExists) {
    echo "   → Would use assign_parameters value for filtering\n";
    echo "   → Fallback to vacation_apartments would NOT trigger\n";
    
    // Check what value it has
    $assignValue = DB::table('assign_parameters')
        ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
        ->where(function($q) {
            $q->where('assign_parameters.property_id', 333)
              ->orWhere(function($q2) {
                  $q2->where('assign_parameters.modal_id', 333)
                     ->where(function($typeQuery) {
                         $typeQuery->where('assign_parameters.modal_type', 'like', '%Property%')
                                   ->orWhere('assign_parameters.modal_type', 'like', '%property%');
                     });
              });
        })
        ->where('parameters.name', 'bedrooms')
        ->value('assign_parameters.value');
    
    echo "   → Value: {$assignValue}\n";
    echo "   → Is Studio: " . (strtolower(trim($assignValue)) === 'studio' ? 'Yes' : 'No') . "\n";
    
} else {
    echo "   → No assign_parameters data found\n";
    echo "   → Fallback to vacation_apartments SHOULD trigger\n";
    
    // Check if vacation_apartments would match
    $vacationMatch = DB::table('vacation_apartments')
        ->where('property_id', 333)
        ->where('status', 1)
        ->where('bedrooms', 1)
        ->exists();
    
    echo "   → Vacation apartments with 1 bedroom exists: " . ($vacationMatch ? 'Yes' : 'No') . "\n";
}

echo "\n=== The Real Issue ===\n";
if (!$assignParamsExists && $vacationApts->count() > 0) {
    echo "✓ Property 333 should be included in 1-bedroom filter via fallback logic\n";
    echo "✓ But API returns 0 results - there's a bug in the query logic\n";
} else if ($assignParamsExists) {
    echo "❌ Property 333 has assign_parameters, so fallback won't trigger\n";
    echo "❌ But if assign_parameters has 'studio', it gets excluded from 1-bedroom filter\n";
} else {
    echo "? Unclear what the issue is\n";
}