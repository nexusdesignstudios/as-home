<?php

// Test script to debug bedroom filter issue

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Testing Bedroom Filter Issue ===\n\n";

// Test the specific properties that are showing up incorrectly
echo "1. Checking properties that appeared with '1' bedroom filter:\n";
$testProperties = [334, 333];

foreach ($testProperties as $propertyId) {
    echo "\n--- Property ID: $propertyId ---\n";
    
    // Check property basic info
    $property = DB::table('propertys')
        ->where('id', $propertyId)
        ->select('id', 'title', 'property_classification', 'propery_type')
        ->first();
    
    if ($property) {
        echo "Title: {$property->title}\n";
        echo "Property Classification: {$property->property_classification}\n";
        echo "Property Type: {$property->propery_type}\n";
    }
    
    // Check assign_parameters for bedroom info
    echo "\nAssign Parameters (bedroom related):\n";
    $assignParams = DB::table('assign_parameters')
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
        ->select('parameters.name', 'assign_parameters.value')
        ->get();
    
    foreach ($assignParams as $param) {
        echo "  Parameter: {$param->name}, Value: {$param->value}\n";
    }
    
    // Check vacation_apartments for bedroom info
    echo "\nVacation Apartments:\n";
    $vacationApts = DB::table('vacation_apartments')
        ->where('property_id', $propertyId)
        ->where('status', 1)
        ->select('bedrooms', 'bathrooms', 'status')
        ->get();
    
    foreach ($vacationApts as $apt) {
        echo "  Bedrooms: {$apt->bedrooms}, Bathrooms: {$apt->bathrooms}, Status: {$apt->status}\n";
    }
}

echo "\n\n2. Testing current bedroom filter logic for '1' bedroom:\n";

// Simulate the current logic for bedrooms = "1"
$bedroomsValue = "1";
$bedroomsIntValue = 1;
$isStudio = false;

echo "Bedrooms filter: '$bedroomsValue' (isStudio: " . ($isStudio ? 'true' : 'false') . ")\n";

// Test the OR condition that might be causing the issue
echo "\n3. Testing assign_parameters check (OR condition):\n";
$assignParamsCheck = DB::table('propertys')
    ->where(function($query) use ($bedroomsValue) {
        $query->whereExists(function($existsQuery) use ($bedroomsValue) {
            $existsQuery->select(DB::raw(1))
                ->from('assign_parameters')
                ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                ->where(function($linkQuery) {
                    $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                        ->orWhere(function($modalQuery) {
                            $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                ->where(function($typeQuery) {
                                    $typeQuery->where('assign_parameters.modal_type', 'like', '%Property%');
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
                        ->orWhereRaw('TRIM(assign_parameters.value) = ?', [$bedroomsValue])
                        ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', [$bedroomsValue])
                        ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', [$bedroomsValue])
                        ->orWhereRaw('TRIM(CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR)) = ?', [$bedroomsValue]);
                });
        });
    })
    ->whereIn('propertys.id', [333, 334])
    ->select('propertys.id', 'propertys.title')
    ->get();

echo "Properties matching assign_parameters check:\n";
foreach ($assignParamsCheck as $prop) {
    echo "  ID: {$prop->id}, Title: {$prop->title}\n";
}

echo "\n4. Testing vacation_apartments check (OR condition):\n";
$vacationAptsCheck = DB::table('propertys')
    ->where(function($query) use ($bedroomsIntValue) {
        $query->where('property_classification', 4)
            ->whereHas('vacationApartments', function($aptQuery) use ($bedroomsIntValue) {
                $aptQuery->where('status', 1)
                    ->where('bedrooms', $bedroomsIntValue);
            });
    })
    ->whereIn('propertys.id', [333, 334])
    ->select('propertys.id', 'propertys.title')
    ->get();

echo "Properties matching vacation_apartments check:\n";
foreach ($vacationAptsCheck as $prop) {
    echo "  ID: {$prop->id}, Title: {$prop->title}\n";
}