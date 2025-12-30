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
                    $valueQuery->whereNotNull('assign_parameters.value')
                        ->where('assign_parameters.value', '!=', '')
                        ->where('assign_parameters.value', '!=', 'null')
                        ->whereRaw('TRIM(assign_parameters.value) != ?', ['']);
                    
                    $valueQuery->where(function($exactQuery) use ($bedroomsValue) {
                        $exactQuery->where('assign_parameters.value', $bedroomsValue)
                            ->orWhere('assign_parameters.value', '"' . $bedroomsValue . '"')
                            ->orWhereRaw('TRIM(assign_parameters.value) = ?', [$bedroomsValue])
                            ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', [$bedroomsValue])
                            ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', [$bedroomsValue])
                            ->orWhereRaw('TRIM(CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR)) = ?', [$bedroomsValue]);
                    });
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
            ->whereExists(function($existsQuery) use ($bedroomsIntValue) {
                $existsQuery->select(DB::raw(1))
                    ->from('vacation_apartments')
                    ->whereRaw('vacation_apartments.property_id = propertys.id')
                    ->where('vacation_apartments.status', 1)
                    ->where('vacation_apartments.bedrooms', $bedroomsIntValue);
            });
    })
    ->whereIn('propertys.id', [333, 334])
    ->select('propertys.id', 'propertys.title')
    ->get();

echo "Properties matching vacation_apartments check:\n";
foreach ($vacationAptsCheck as $prop) {
    echo "  ID: {$prop->id}, Title: {$prop->title}\n";
}

echo "\n\n=== ANALYSIS ===\n";
echo "The issue is that Property 333 has 'studio' in assign_parameters but 1 bedroom in vacation_apartments.\n";
echo "When filtering for '1' bedroom, the current OR logic matches properties that have:\n";
echo "1. assign_parameters.value = '1' OR\n";
echo "2. vacation_apartments.bedrooms = 1\n";
echo "\nProperty 333 matches condition 2 (vacation_apartments.bedrooms = 1) even though it has 'studio' in assign_parameters.\n";
echo "\nFor vacation homes (property_classification = 4), the logic should prioritize vacation_apartments data\n";
echo "and ignore assign_parameters bedroom data to avoid conflicts.\n";