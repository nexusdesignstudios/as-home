<?php

// Test the fixed logic
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "=== Testing Fixed Logic ===\n";

// Test property 333 with 1 bedroom filter
echo "Testing Property 333 with 1 bedroom filter:\n";

// Simulate the new logic
$isStudio = false;
$bedroomsIntValue = 1;
$bedroomsValue = '1';

// Check if property has matching vacation apartments
$hasMatchingVacationApts = DB::table('vacation_apartments')
    ->where('property_id', 333)
    ->where('status', 1)
    ->where('bedrooms', $bedroomsIntValue)
    ->where('bedrooms', '!=', 0)
    ->exists();

echo "  Has matching vacation apartments: " . ($hasMatchingVacationApts ? 'Yes' : 'No') . "\n";

// Check assign_parameters
$hasStudioInAssignParams = DB::table('assign_parameters')
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
    ->where(function($nameQuery) {
        $nameQuery->where('parameters.name', 'LIKE', '%bedroom%')
            ->orWhere('parameters.name', 'LIKE', '%bed%');
    })
    ->where(function($studioQuery) {
        $studioQuery->where('assign_parameters.value', '0')
            ->orWhereRaw('LOWER(TRIM(assign_parameters.value)) = ?', ['studio'])
            ->orWhere('assign_parameters.value', 'Studio')
            ->orWhere('assign_parameters.value', 'STUDIO');
    })
    ->exists();

echo "  Has studio in assign_parameters: " . ($hasStudioInAssignParams ? 'Yes' : 'No') . "\n";

// Apply the new logic
$shouldInclude = false;

if ($isStudio) {
    // For studio filter: include if assign_parameters has studio OR vacation_apartments has studio
    $shouldInclude = $hasStudioInAssignParams || $hasMatchingVacationApts;
} else {
    // For 1,2,3 bedroom filters: include if vacation_apartments match
    // AND (assign_parameters matches OR assign_parameters doesn't have studio)
    if ($hasMatchingVacationApts) {
        // Check if assign_parameters has matching value OR doesn't have studio
        $hasMatchingAssignParams = DB::table('assign_parameters')
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
            ->where(function($nameQuery) {
                $nameQuery->where('parameters.name', 'LIKE', '%bedroom%')
                    ->orWhere('parameters.name', 'LIKE', '%bed%');
            })
            ->where(function($valueQuery) use ($bedroomsValue) {
                $valueQuery->where('assign_parameters.value', $bedroomsValue)
                    ->orWhere('assign_parameters.value', '"' . $bedroomsValue . '"')
                    ->orWhereRaw('TRIM(assign_parameters.value) = ?', [$bedroomsValue]);
            })
            ->exists();
        
        if ($hasMatchingAssignParams) {
            $shouldInclude = true;
        } else {
            // If no matching assign_parameters, include if assign_parameters doesn't have studio
            $shouldInclude = !$hasStudioInAssignParams;
        }
    }
}

echo "  Should include in 1BR filter: " . ($shouldInclude ? 'Yes ✓' : 'No ✗') . "\n";

echo "\n=== Summary ===\n";
echo "Property 333:\n";
echo "- Assign parameters: studio\n";
echo "- Vacation apartments: 1BR, 2BR, 3BR\n";
echo "- New logic result: " . ($shouldInclude ? "INCLUDE in 1BR filter ✓" : "EXCLUDE from 1BR filter ✗") . "\n";
echo "- This should fix the user's issue!\n";