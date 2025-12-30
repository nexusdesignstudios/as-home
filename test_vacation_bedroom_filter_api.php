<?php
// File: as-home-dashboard-Admin/test_vacation_bedroom_filter_api.php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Property;
use Illuminate\Http\Request;

echo "================================================\n";
echo "Vacation Homes Bedroom Filter API Test\n";
echo "================================================\n\n";

// Test Case 1: Vacation Homes with 2 bedrooms, property_type=1, category_id=1
echo "TEST CASE 1: Vacation Homes - 2 Bedrooms, Rent (type=1), Category=1\n";
echo str_repeat("-", 50) . "\n";

$request1 = new Request([
    'property_classification' => 4,
    'property_type' => 1,
    'category_id' => 3, // Changed from 1 to 3 (Apartment) - actual category_id for vacation homes
    'bedrooms' => '2',
    'page' => 1,
    'limit' => 10
]);

echo "Request Parameters:\n";
echo "- property_classification: 4 (Vacation Homes)\n";
echo "- property_type: 1 (Rent)\n";
echo "- category_id: 3 (Apartment) - ACTUAL category_id for vacation homes\n";
echo "- bedrooms: 2\n\n";

// Simulate the query building process
$select = ['id', 'title', 'price', 'propery_type', 'property_classification', 'category_id', 'status', 'request_status'];
$property = Property::select($select)
    ->with('category:id,category,image,slug_id,parameter_types', 'vacationApartments')
    ->where(['status' => 1, 'request_status' => 'approved']);

// Apply property_classification
$propertyClassification = (int) $request1->property_classification;
$property = $property->where('property_classification', $propertyClassification);
echo "✓ Applied property_classification = {$propertyClassification}\n";

// Apply property_type
$property_type = $request1->property_type;
if (isset($property_type) && (!empty($property_type) || $property_type == 0)) {
    $property_type_int = (int) $property_type;
    $property = $property->where('propery_type', $property_type_int);
    echo "✓ Applied property_type = {$property_type_int}\n";
}

// Apply category_id (NEW: Applied BEFORE bedroom filter)
if ($request1->has('category_id') && !empty($request1->category_id)) {
    $categoryId = $request1->category_id;
    $property = $property->where('category_id', $categoryId);
    echo "✓ Applied category_id = {$categoryId} (BEFORE bedroom filter)\n";
}

// Apply bedrooms filter
if ($request1->has('bedrooms') && $request1->bedrooms !== null && $request1->bedrooms !== '') {
    $bedroomsValue = (string) $request1->bedrooms;
    $bedroomsIntValue = (int) $bedroomsValue;
    $isVacationHomes = ($propertyClassification == 4 || $propertyClassification == '4');
    
    echo "✓ Applying bedrooms filter = {$bedroomsValue} (int: {$bedroomsIntValue})\n";
    echo "  - isVacationHomes: " . ($isVacationHomes ? 'true' : 'false') . "\n";
    
    $property = $property->where(function ($query) use ($bedroomsValue, $bedroomsIntValue, $isVacationHomes) {
        if ($isVacationHomes) {
            // For vacation homes, check vacation_apartments FIRST
            $query->whereHas('vacationApartments', function ($aptQuery) use ($bedroomsIntValue) {
                $aptQuery->where('status', 1)
                    ->where('bedrooms', $bedroomsIntValue);
            })
            ->orWhere(function ($assignParamsQuery) use ($bedroomsValue) {
                $assignParamsQuery->whereExists(function ($existsQuery) use ($bedroomsValue) {
                    $existsQuery->select(DB::raw(1))
                        ->from('assign_parameters')
                        ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                        ->where(function ($linkQuery) {
                            $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                                ->orWhere(function ($modalQuery) {
                                    $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                        ->where(function ($typeQuery) {
                                            $typeQuery->where('assign_parameters.modal_type', 'App\\Models\\Property')
                                                ->orWhere('assign_parameters.modal_type', 'property');
                                        });
                                });
                        })
                        ->where(function ($nameQuery) {
                            $nameQuery->where('parameters.name', 'LIKE', '%bedroom%')
                                ->orWhere('parameters.name', 'LIKE', '%bed%');
                        })
                        ->where(function ($valueQuery) use ($bedroomsValue) {
                            $valueQuery->whereNotNull('assign_parameters.value')
                                ->where('assign_parameters.value', '!=', '')
                                ->where('assign_parameters.value', '!=', 'null')
                                ->whereRaw('TRIM(assign_parameters.value) != ?', [''])
                                ->where(function ($exactQuery) use ($bedroomsValue) {
                                    $exactQuery->where('assign_parameters.value', $bedroomsValue)
                                        ->orWhere('assign_parameters.value', '"' . $bedroomsValue . '"')
                                        ->orWhereRaw('TRIM(assign_parameters.value) = ?', [$bedroomsValue])
                                        ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', [$bedroomsValue])
                                        ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', [$bedroomsValue])
                                        ->orWhereRaw('TRIM(CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR)) = ?', [$bedroomsValue]);
                                });
                        });
                });
            });
        }
    });
}

// Get results
$total = $property->count();
$results = $property->orderBy('id', 'DESC')->limit(10)->get();

echo "\nQuery Results:\n";
echo "- Total Count: {$total}\n";
echo "- Returned: {$results->count()}\n\n";

if ($results->count() > 0) {
    echo "Sample Properties:\n";
    foreach ($results as $prop) {
        $apts = $prop->vacationApartments->where('bedrooms', 2)->where('status', 1);
        echo "  - ID: {$prop->id}, Title: {$prop->title}\n";
        echo "    Category ID: {$prop->category_id}, Property Type: {$prop->propery_type}\n";
        echo "    Vacation Apartments with 2 bedrooms: {$apts->count()}\n";
        if ($apts->count() > 0) {
            foreach ($apts->take(2) as $apt) {
                echo "      * Apt #{$apt->apartment_number}: {$apt->bedrooms} bedrooms\n";
            }
        }
    }
} else {
    echo "❌ No properties found!\n\n";
    
    // Debug: Check each filter separately
    echo "Debugging - Checking filters separately:\n";
    
    // Check 1: Total vacation homes
    $totalVacation = Property::where('property_classification', 4)
        ->where('status', 1)
        ->where('request_status', 'approved')
        ->count();
    echo "  - Total vacation homes: {$totalVacation}\n";
    
    // Check 2: With property_type
    $withType = Property::where('property_classification', 4)
        ->where('propery_type', 1)
        ->where('status', 1)
        ->where('request_status', 'approved')
        ->count();
    echo "  - With property_type=1: {$withType}\n";
    
    // Check 3: With category_id
    $withCategory = Property::where('property_classification', 4)
        ->where('category_id', 3)
        ->where('status', 1)
        ->where('request_status', 'approved')
        ->count();
    echo "  - With category_id=3: {$withCategory}\n";
    
    // Check 4: With both
    $withBoth = Property::where('property_classification', 4)
        ->where('propery_type', 1)
        ->where('category_id', 3)
        ->where('status', 1)
        ->where('request_status', 'approved')
        ->count();
    echo "  - With property_type=1 AND category_id=3: {$withBoth}\n";
    
    // Check 5: Vacation apartments with 2 bedrooms
    $withBedrooms = DB::table('vacation_apartments')
        ->join('propertys', 'vacation_apartments.property_id', '=', 'propertys.id')
        ->where('propertys.property_classification', 4)
        ->where('propertys.propery_type', 1)
        ->where('propertys.category_id', 3)
        ->where('propertys.status', 1)
        ->where('propertys.request_status', 'approved')
        ->where('vacation_apartments.status', 1)
        ->where('vacation_apartments.bedrooms', 2)
        ->distinct('propertys.id')
        ->count('propertys.id');
    echo "  - With ALL filters (including bedrooms=2): {$withBedrooms}\n";
    
    // Check 6: Show SQL query
    echo "\n  SQL Query:\n";
    $sqlQuery = $property->toSql();
    $bindings = $property->getBindings();
    echo "  " . $sqlQuery . "\n";
    echo "  Bindings: " . json_encode($bindings) . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test Case 2: Vacation Homes with 1 bedroom, property_type=1, category_id=1
echo "TEST CASE 2: Vacation Homes - 1 Bedroom, Rent (type=1), Category=1\n";
echo str_repeat("-", 50) . "\n";

$request2 = new Request([
    'property_classification' => 4,
    'property_type' => 1,
    'category_id' => 3, // Changed from 1 to 3 (Apartment) - actual category_id for vacation homes
    'bedrooms' => '1',
    'page' => 1,
    'limit' => 10
]);

echo "Request Parameters:\n";
echo "- property_classification: 4 (Vacation Homes)\n";
echo "- property_type: 1 (Rent)\n";
echo "- category_id: 3 (Apartment) - ACTUAL category_id for vacation homes\n";
echo "- bedrooms: 1\n\n";

$property2 = Property::select($select)
    ->with('category:id,category,image,slug_id,parameter_types', 'vacationApartments')
    ->where(['status' => 1, 'request_status' => 'approved'])
    ->where('property_classification', 4)
    ->where('propery_type', 1)
    ->where('category_id', 3);

if ($request2->has('bedrooms') && $request2->bedrooms !== null && $request2->bedrooms !== '') {
    $bedroomsValue = (string) $request2->bedrooms;
    $bedroomsIntValue = (int) $bedroomsValue;
    
    $property2 = $property2->where(function ($query) use ($bedroomsValue, $bedroomsIntValue) {
        $query->whereHas('vacationApartments', function ($aptQuery) use ($bedroomsIntValue) {
            $aptQuery->where('status', 1)
                ->where('bedrooms', $bedroomsIntValue);
        })
        ->orWhere(function ($assignParamsQuery) use ($bedroomsValue) {
            $assignParamsQuery->whereExists(function ($existsQuery) use ($bedroomsValue) {
                $existsQuery->select(DB::raw(1))
                    ->from('assign_parameters')
                    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                    ->where(function ($linkQuery) {
                        $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                            ->orWhere(function ($modalQuery) {
                                $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                    ->where(function ($typeQuery) {
                                        $typeQuery->where('assign_parameters.modal_type', 'App\\Models\\Property')
                                            ->orWhere('assign_parameters.modal_type', 'property');
                                    });
                            });
                    })
                    ->where(function ($nameQuery) {
                        $nameQuery->where('parameters.name', 'LIKE', '%bedroom%')
                            ->orWhere('parameters.name', 'LIKE', '%bed%');
                    })
                    ->where(function ($valueQuery) use ($bedroomsValue) {
                        $valueQuery->whereNotNull('assign_parameters.value')
                            ->where('assign_parameters.value', '!=', '')
                            ->where('assign_parameters.value', '!=', 'null')
                            ->whereRaw('TRIM(assign_parameters.value) != ?', [''])
                            ->where(function ($exactQuery) use ($bedroomsValue) {
                                $exactQuery->where('assign_parameters.value', $bedroomsValue)
                                    ->orWhere('assign_parameters.value', '"' . $bedroomsValue . '"')
                                    ->orWhereRaw('TRIM(assign_parameters.value) = ?', [$bedroomsValue])
                                    ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', [$bedroomsValue])
                                    ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', [$bedroomsValue])
                                    ->orWhereRaw('TRIM(CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR)) = ?', [$bedroomsValue]);
                            });
                    });
            });
        });
    });
}

$total2 = $property2->count();
$results2 = $property2->orderBy('id', 'DESC')->limit(10)->get();

echo "Query Results:\n";
echo "- Total Count: {$total2}\n";
echo "- Returned: {$results2->count()}\n\n";

if ($results2->count() > 0) {
    echo "Sample Properties:\n";
    foreach ($results2 as $prop) {
        $apts = $prop->vacationApartments->where('bedrooms', 1)->where('status', 1);
        echo "  - ID: {$prop->id}, Title: {$prop->title}\n";
        echo "    Category ID: {$prop->category_id}, Property Type: {$prop->propery_type}\n";
        echo "    Vacation Apartments with 1 bedroom: {$apts->count()}\n";
    }
} else {
    echo "❌ No properties found!\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test Case 3: Show SQL query structure
echo "TEST CASE 3: SQL Query Structure Analysis\n";
echo str_repeat("-", 50) . "\n";

$testQuery = Property::where('property_classification', 4)
    ->where('propery_type', 1)
    ->where('category_id', 3) // Changed from 1 to 3 (Apartment)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->whereHas('vacationApartments', function ($aptQuery) {
        $aptQuery->where('status', 1)
            ->where('bedrooms', 2);
    });

echo "SQL Query (with whereHas):\n";
echo $testQuery->toSql() . "\n\n";
echo "Bindings:\n";
print_r($testQuery->getBindings());

echo "\n" . str_repeat("=", 50) . "\n";
echo "Test Complete\n";
echo str_repeat("=", 50) . "\n";

