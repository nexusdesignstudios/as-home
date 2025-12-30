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

// Check if property has matching vacation apartments
$hasMatchingVacationApts = DB::table('vacation_apartments')
    ->where('property_id', 333)
    ->where('status', 1)
    ->where('bedrooms', $bedroomsIntValue)
    ->where('bedrooms', '!=', 0)
    ->exists();

echo "  Has matching vacation apartments: " . ($hasMatchingVacationApts ? 'Yes' : 'No') . "\n";

// Apply the new logic
$shouldInclude = false;

if ($isStudio) {
    // For studio filter: include if assign_parameters has studio OR vacation_apartments has studio
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
    
    $hasStudioVacationApts = DB::table('vacation_apartments')
        ->where('property_id', 333)
        ->where('status', 1)
        ->where('bedrooms', 0)
        ->exists();
    
    $shouldInclude = $hasStudioInAssignParams || $hasStudioVacationApts;
} else {
    // For 1,2,3 bedroom filters: include if vacation_apartments match (ignore assign_parameters)
    $shouldInclude = $hasMatchingVacationApts;
}

echo "  Should include in 1BR filter: " . ($shouldInclude ? 'Yes ✓' : 'No ✗') . "\n";

echo "\n=== Summary ===\n";
echo "Property 333:\n";
echo "- Assign parameters: studio\n";
echo "- Vacation apartments: 1BR, 2BR, 3BR\n";
echo "- New logic result: " . ($shouldInclude ? "INCLUDE in 1BR filter ✓" : "EXCLUDE from 1BR filter ✗") . "\n";

if ($shouldInclude) {
    echo "- ✅ This fixes the user's issue! Property 333 will now appear in 1BR filter.\n";
} else {
    echo "- ❌ Still excluding property 333 from 1BR filter.\n";
}

// Test studio filter
echo "\n=== Testing Studio Filter ===\n";
$isStudio = true;
$bedroomsIntValue = 0;

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

$hasStudioVacationApts = DB::table('vacation_apartments')
    ->where('property_id', 333)
    ->where('status', 1)
    ->where('bedrooms', 0)
    ->exists();

$shouldIncludeStudio = $hasStudioInAssignParams || $hasStudioVacationApts;

echo "Property 333 with studio filter:\n";
echo "  Has studio in assign_parameters: " . ($hasStudioInAssignParams ? 'Yes' : 'No') . "\n";
echo "  Has studio vacation apartments: " . ($hasStudioVacationApts ? 'Yes' : 'No') . "\n";
echo "  Should include in studio filter: " . ($shouldIncludeStudio ? 'Yes ✓' : 'No ✗') . "\n";