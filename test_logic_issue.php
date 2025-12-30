<?php

// Test the exact logic issue - why fallback isn't working
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;

// Test property 333 specifically
echo "=== Testing Property 333 Logic ===\n";

// Check assign_parameters for property 333
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
echo "  Value: " . ($assignParams ? $assignParams->value : 'None') . "\n";
echo "  Modal Type: " . ($assignParams ? $assignParams->modal_type : 'None') . "\n";

// Check vacation_apartments for property 333
$vacationApts = DB::table('vacation_apartments')
    ->where('property_id', 333)
    ->where('status', 1)
    ->select('bedrooms', 'apartment_number')
    ->get();

echo "Vacation Apartments for property 333:\n";
foreach ($vacationApts as $apt) {
    echo "  Bedrooms: {$apt->bedrooms}, Apartment: {$apt->apartment_number}\n";
}

// Now test the current logic
echo "\n=== Current Logic Analysis ===\n";

if ($assignParams) {
    echo "✓ Has assign_parameters data\n";
    echo "  Value: {$assignParams->value}\n";
    
    $isStudio = strtolower(trim($assignParams->value)) === 'studio';
    echo "  Is Studio: " . ($isStudio ? 'Yes' : 'No') . "\n";
    
    if ($isStudio) {
        echo "  ❌ This property would be EXCLUDED from 1-bedroom filter because it's studio\n";
    } else {
        echo "  ✓ This property would be INCLUDED in 1-bedroom filter\n";
    }
} else {
    echo "✗ No assign_parameters data\n";
    echo "  Would check vacation_apartments as fallback\n";
}

echo "\n=== The Problem ===\n";
echo "Property 333 has assign_parameters with 'studio' value, so it gets excluded from 1-bedroom filter.\n";
echo "But the user wants it to show in 1-bedroom filter because it has 1-bedroom vacation apartments.\n";
echo "The current logic prioritizes assign_parameters over vacation_apartments, which is correct for filtering,\n";
echo "but it means studio properties with 1-bedroom apartments will never show in 1-bedroom filter.\n";

echo "\n=== The Solution ===\n";
echo "We need to change the logic to:\n";
echo "1. If property has assign_parameters with non-studio value, use that for filtering\n";
echo "2. If property has assign_parameters with studio value, exclude from non-studio filters\n";
echo "3. If property has no assign_parameters, use vacation_apartments for filtering\n";
echo "4. For studio filter (bedrooms=0), include properties with studio in assign_parameters OR studio vacation apartments\n";