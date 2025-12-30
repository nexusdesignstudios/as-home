<?php

// Check what properties actually have assign_parameters vs vacation_apartments conflicts
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "=== Analyzing Property Conflicts ===\n";

// Get all vacation homes (property_classification = 4)
$vacationHomes = DB::table('propertys')
    ->where('property_classification', 4)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->select('id', 'title')
    ->get();

echo "Total vacation homes: " . $vacationHomes->count() . "\n\n";

foreach ($vacationHomes as $property) {
    echo "Property {$property->id}: {$property->title}\n";
    
    // Check assign_parameters
    $assignParams = DB::table('assign_parameters')
        ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
        ->where(function($q) use ($property) {
            $q->where('assign_parameters.property_id', $property->id)
              ->orWhere(function($q2) use ($property) {
                  $q2->where('assign_parameters.modal_id', $property->id)
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
    
    // Check vacation_apartments
    $vacationApts = DB::table('vacation_apartments')
        ->where('property_id', $property->id)
        ->where('status', 1)
        ->select('bedrooms', DB::raw('COUNT(*) as count'))
        ->groupBy('bedrooms')
        ->get();
    
    echo "  Assign Parameters: " . ($assignParams ? "{$assignParams->name} = {$assignParams->value}" : 'None') . "\n";
    echo "  Vacation Apartments: ";
    if ($vacationApts->count() > 0) {
        $aptCounts = [];
        foreach ($vacationApts as $apt) {
            $aptCounts[] = "{$apt->bedrooms}BR ({$apt->count})";
        }
        echo implode(', ', $aptCounts) . "\n";
    } else {
        echo "None\n";
    }
    
    // Check for conflicts
    if ($assignParams && $vacationApts->count() > 0) {
        $assignValue = strtolower(trim($assignParams->value));
        $hasStudioApt = $vacationApts->contains('bedrooms', 0);
        $hasOneBedApt = $vacationApts->contains('bedrooms', 1);
        $hasTwoBedApt = $vacationApts->contains('bedrooms', 2);
        $hasThreeBedApt = $vacationApts->contains('bedrooms', 3);
        
        echo "  Conflict Analysis:\n";
        
        if ($assignValue === 'studio' && $hasOneBedApt) {
            echo "    ❌ CONFLICT: Assign says 'studio' but has 1BR apartments\n";
            echo "    → Current logic: Excluded from 1BR filter\n";
            echo "    → User expectation: Should show in 1BR filter\n";
        } elseif ($assignValue === '1' && $hasStudioApt) {
            echo "    ❌ CONFLICT: Assign says '1' but has studio apartments\n";
            echo "    → Current logic: Excluded from studio filter\n";
            echo "    → User expectation: Should show in studio filter\n";
        } else {
            echo "    ✓ No major conflicts\n";
        }
    }
    
    echo "\n";
}