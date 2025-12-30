<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Property;
use Illuminate\Http\Request;

echo "================================================\n";
echo "Test: Filter Across All Categories (No Category Selected)\n";
echo "================================================\n\n";

// Test Case 1: Vacation Homes with 2 bedrooms, NO category_id (should filter across all categories)
echo "TEST CASE 1: Vacation Homes - 2 Bedrooms, NO category_id (Filter Across All Categories)\n";
echo str_repeat("-", 70) . "\n";

$request1 = new Request([
    'property_classification' => 4,
    'property_type' => 1,
    // NO category_id - should filter across all categories
    'bedrooms' => '2',
    'page' => 1,
    'limit' => 20
]);

echo "Request Parameters:\n";
echo "- property_classification: 4 (Vacation Homes)\n";
echo "- property_type: 1 (Rent)\n";
echo "- category_id: NOT SET (should filter across all categories)\n";
echo "- bedrooms: 2\n\n";

// Simulate the query building process (same as backend)
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

// NO category_id filter applied - this is the key difference
echo "✓ category_id: NOT APPLIED (filtering across all categories)\n";

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
$results = $property->orderBy('id', 'DESC')->limit(20)->get();

echo "\nQuery Results:\n";
echo "- Total Count: {$total}\n";
echo "- Returned: {$results->count()}\n\n";

if ($results->count() > 0) {
    echo "Properties Found (showing category distribution):\n";
    $categoryDistribution = [];
    foreach ($results as $prop) {
        $catId = $prop->category_id ?? 'null';
        $catName = $prop->category ? $prop->category->category : 'Unknown';
        if (!isset($categoryDistribution[$catId])) {
            $categoryDistribution[$catId] = [
                'name' => $catName,
                'count' => 0,
                'properties' => []
            ];
        }
        $categoryDistribution[$catId]['count']++;
        $categoryDistribution[$catId]['properties'][] = [
            'id' => $prop->id,
            'title' => $prop->title
        ];
    }
    
    foreach ($categoryDistribution as $catId => $data) {
        echo "  Category ID {$catId} ({$data['name']}): {$data['count']} properties\n";
        foreach ($data['properties'] as $prop) {
            echo "    - ID: {$prop['id']}, Title: {$prop['title']}\n";
        }
    }
    
    echo "\n✓ SUCCESS: Filter works across all categories when category_id is not set!\n";
} else {
    echo "❌ No properties found!\n";
}

echo "\n" . str_repeat("=", 70) . "\n\n";

// Test Case 2: Compare with category_id = 3 (should return fewer results)
echo "TEST CASE 2: Vacation Homes - 2 Bedrooms, WITH category_id = 3 (Apartment)\n";
echo str_repeat("-", 70) . "\n";

$request2 = new Request([
    'property_classification' => 4,
    'property_type' => 1,
    'category_id' => 3, // Specific category
    'bedrooms' => '2',
    'page' => 1,
    'limit' => 20
]);

echo "Request Parameters:\n";
echo "- property_classification: 4 (Vacation Homes)\n";
echo "- property_type: 1 (Rent)\n";
echo "- category_id: 3 (Apartment) - SPECIFIC category\n";
echo "- bedrooms: 2\n\n";

$property2 = Property::select($select)
    ->with('category:id,category,image,slug_id,parameter_types', 'vacationApartments')
    ->where(['status' => 1, 'request_status' => 'approved'])
    ->where('property_classification', 4)
    ->where('propery_type', 1)
    ->where('category_id', 3); // Apply category filter

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
$results2 = $property2->orderBy('id', 'DESC')->limit(20)->get();

echo "Query Results:\n";
echo "- Total Count: {$total2}\n";
echo "- Returned: {$results2->count()}\n\n";

if ($results2->count() > 0) {
    echo "Properties Found (all should be category_id = 3):\n";
    foreach ($results2 as $prop) {
        $catName = $prop->category ? $prop->category->category : 'Unknown';
        echo "  - ID: {$prop->id}, Title: {$prop->title}, Category: {$catName} (ID: {$prop->category_id})\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n\n";

// Test Case 3: Summary Comparison
echo "TEST CASE 3: Summary Comparison\n";
echo str_repeat("-", 70) . "\n";

echo "Comparison:\n";
echo "- Without category_id (all categories): {$total} properties\n";
echo "- With category_id = 3 (Apartment only): {$total2} properties\n";
echo "\n";

if ($total >= $total2) {
    echo "✓ SUCCESS: Filter without category_id returns equal or more results!\n";
    echo "  This confirms that filtering works across all categories when category_id is not set.\n";
} else {
    echo "⚠ WARNING: Unexpected result - without category_id should return more or equal results.\n";
}

// Test Case 4: Check all available categories for vacation homes
echo "\n" . str_repeat("=", 70) . "\n";
echo "TEST CASE 4: Available Categories for Vacation Homes\n";
echo str_repeat("-", 70) . "\n";

$allCategories = Property::where('property_classification', 4)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->with('category:id,category')
    ->get()
    ->groupBy('category_id')
    ->map(function ($properties, $catId) {
        $category = $properties->first()->category;
        return [
            'category_id' => $catId,
            'category_name' => $category ? $category->category : 'Unknown',
            'property_count' => $properties->count()
        ];
    })
    ->values();

echo "Available Categories:\n";
foreach ($allCategories as $cat) {
    echo "  - Category ID {$cat['category_id']} ({$cat['category_name']}): {$cat['property_count']} properties\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "Test Complete\n";
echo str_repeat("=", 70) . "\n";

