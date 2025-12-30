<?php

// Test the exact fallback query that's failing
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "=== Testing the Exact Fallback Query ===\n";

// Test the NOT EXISTS part of the fallback query
echo "1. Testing NOT EXISTS query for property 333:\n";

$notExists = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where(function($linkQuery) {
        $linkQuery->where('assign_parameters.property_id', 333)
            ->orWhere(function($modalQuery) {
                $modalQuery->where('assign_parameters.modal_id', 333)
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
    ->exists();

echo "   NOT EXISTS result: " . (!$notExists ? 'True (should trigger fallback)' : 'False (fallback blocked)') . "\n";

// Test the vacation_apartments part
echo "\n2. Testing vacation_apartments query for property 333:\n";

$vacationExists = DB::table('vacation_apartments')
    ->where('property_id', 333)
    ->where('status', 1)
    ->where('bedrooms', 1)
    ->exists();

echo "   Vacation apartments with 1 bedroom exists: " . ($vacationExists ? 'Yes' : 'No') . "\n";

// Now test the combined logic
echo "\n3. Testing combined fallback logic:\n";

$fallbackShouldWork = !$notExists && $vacationExists;
echo "   Fallback should work: " . ($fallbackShouldWork ? 'Yes' : 'No') . "\n";

if (!$fallbackShouldWork) {
    echo "   ❌ Issue found!\n";
    if ($notExists) {
        echo "   → NOT EXISTS is blocking fallback (should be TRUE for fallback)\n";
    }
    if (!$vacationExists) {
        echo "   → No vacation apartments with matching bedrooms\n";
    }
} else {
    echo "   ✓ Fallback logic should work correctly\n";
}

echo "\n=== Checking the Issue ===\n";

// Let's check if there's any assign_parameters data that might be blocking
$anyAssignData = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where(function($linkQuery) {
        $linkQuery->where('assign_parameters.property_id', 333)
            ->orWhere(function($modalQuery) {
                $modalQuery->where('assign_parameters.modal_id', 333)
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
    ->select('assign_parameters.value', 'parameters.name')
    ->first();

if ($anyAssignData) {
    echo "Found assign_parameters data that blocks fallback:\n";
    echo "  Parameter: {$anyAssignData->name}\n";
    echo "  Value: {$anyAssignData->value}\n";
} else {
    echo "No assign_parameters data found - fallback should work\n";
}