<?php

// Test why the fallback logic isn't working for property 333
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

// Simulate the exact API query for property 333 with 1 bedroom filter
echo "=== Simulating API Query for Property 333 (1 bedroom) ===\n";

// First, let's check if property 333 meets the basic requirements
$basicCheck = DB::table('propertys')
    ->where('id', 333)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->where('property_classification', 4)
    ->first();

echo "Basic Property Check:\n";
echo "  Property exists and meets basic requirements: " . ($basicCheck ? 'Yes' : 'No') . "\n";
if ($basicCheck) {
    echo "  Property Classification: {$basicCheck->property_classification}\n";
}

// Now test the assign_parameters part of the query
echo "\n=== Assign Parameters Check ===\n";

$assignParamsExists = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where(function($linkQuery) {
        $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
            ->orWhere(function($modalQuery) {
                $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                    ->where(function($typeQuery) {
                        $typeQuery->where('assign_parameters.modal_type', 'like', '%Property%')
                            ->orWhere('assign_parameters.modal_type', 'like', '%property%');
                    });
            });
    })
    ->where('parameters.name', 'bedrooms')
    ->where('assign_parameters.value', '1')
    ->where('propertys.id', 333)
    ->exists();

echo "  Assign parameters with bedrooms=1 exists: " . ($assignParamsExists ? 'Yes' : 'No') . "\n";

// Test the NOT EXISTS part (fallback logic)
echo "\n=== Fallback Logic Check ===\n";

$assignParamsAny = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where(function($linkQuery) {
        $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
            ->orWhere(function($modalQuery) {
                $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                    ->where(function($typeQuery) {
                        $typeQuery->where('assign_parameters.modal_type', 'like', '%Property%')
                            ->orWhere('assign_parameters.modal_type', 'like', '%property%');
                    });
            });
    })
    ->where('parameters.name', 'bedrooms')
    ->where('propertys.id', 333)
    ->exists();

echo "  Any assign parameters for bedrooms exists: " . ($assignParamsAny ? 'Yes' : 'No') . "\n";

if (!$assignParamsAny) {
    echo "  ✓ Fallback should trigger - checking vacation_apartments\n";
    
    $vacationApts = DB::table('vacation_apartments')
        ->where('property_id', 333)
        ->where('status', 1)
        ->where('bedrooms', 1)
        ->exists();
    
    echo "  Vacation apartments with 1 bedroom exists: " . ($vacationApts ? 'Yes' : 'No') . "\n";
} else {
    echo "  ❌ Fallback will NOT trigger - assign_parameters exists\n";
}

// Now let's test the full query logic
echo "\n=== Full Query Simulation ===\n";

$fullQuery = DB::table('propertys')
    ->where('propertys.status', 1)
    ->where('propertys.request_status', 'approved')
    ->where('propertys.property_classification', 4);

// Add the bedroom filter logic
$fullQuery->where(function($bedroomQuery) {
    // First condition: assign_parameters matches
    $bedroomQuery->where(function($assignQuery) {
        $assignQuery->whereExists(function($existsQuery) {
            $existsQuery->select(DB::raw(1))
                ->from('assign_parameters')
                ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                ->where(function($linkQuery) {
                    $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                        ->orWhere(function($modalQuery) {
                            $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                ->where(function($typeQuery) {
                                    $typeQuery->where('assign_parameters.modal_type', 'like', '%Property%')
                                        ->orWhere('assign_parameters.modal_type', 'like', '%property%');
                                });
                        });
                })
                ->where('parameters.name', 'bedrooms')
                ->where('assign_parameters.value', '1');
        });
    })
    // Second condition: no assign_parameters exists AND vacation_apartments matches
    ->orWhere(function($fallbackQuery) {
        $fallbackQuery->whereNotExists(function($notExistsQuery) {
            $notExistsQuery->select(DB::raw(1))
                ->from('assign_parameters')
                ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                ->where(function($linkQuery) {
                    $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                        ->orWhere(function($modalQuery) {
                            $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                ->where(function($typeQuery) {
                                    $typeQuery->where('assign_parameters.modal_type', 'like', '%Property%')
                                        ->orWhere('assign_parameters.modal_type', 'like', '%property%');
                                });
                        });
                })
                ->where('parameters.name', 'bedrooms');
        })
        ->whereExists(function($vacationExistsQuery) {
            $vacationExistsQuery->select(DB::raw(1))
                ->from('vacation_apartments')
                ->whereColumn('vacation_apartments.property_id', 'propertys.id')
                ->where('vacation_apartments.status', 1)
                ->where('vacation_apartments.bedrooms', 1);
        });
    });
});

$fullQuery->where('propertys.id', 333);
$result = $fullQuery->exists();

echo "  Full query would include property 333: " . ($result ? 'Yes' : 'No') . "\n";