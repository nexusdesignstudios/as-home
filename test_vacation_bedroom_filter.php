<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Property;

echo "Testing Vacation Homes Bedroom Filter (1 bedroom)\n";
echo "================================================\n\n";

// Test parameters
$bedroomsValue = "1";
$bedroomsIntValue = 1;
$propertyClassification = 4; // Vacation homes
$propertyType = 1; // Rent (assuming from the URL)

echo "Filter Parameters:\n";
echo "- Bedrooms: {$bedroomsValue} (int: {$bedroomsIntValue})\n";
echo "- Property Classification: {$propertyClassification} (Vacation Homes)\n";
echo "- Property Type: {$propertyType} (Rent)\n\n";

// Step 1: Check how many vacation homes exist
echo "Step 1: Total Vacation Homes\n";
$totalVacationHomes = Property::where('property_classification', 4)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->count();
echo "Total vacation homes (status=1, request_status=approved): {$totalVacationHomes}\n\n";

// Step 2: Check vacation homes with property_type = 1
echo "Step 2: Vacation Homes with property_type = {$propertyType}\n";
$vacationHomesWithType = Property::where('property_classification', 4)
    ->where('propery_type', $propertyType)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->count();
echo "Vacation homes with property_type = {$propertyType}: {$vacationHomesWithType}\n\n";

// Step 3: Check vacation apartments with bedrooms = 1
echo "Step 3: Vacation Apartments with bedrooms = {$bedroomsIntValue}\n";
$vacationAptsWithBedrooms = DB::table('vacation_apartments')
    ->join('propertys', 'vacation_apartments.property_id', '=', 'propertys.id')
    ->where('propertys.property_classification', 4)
    ->where('propertys.status', 1)
    ->where('propertys.request_status', 'approved')
    ->where('vacation_apartments.status', 1)
    ->where('vacation_apartments.bedrooms', $bedroomsIntValue)
    ->distinct('propertys.id')
    ->count('propertys.id');
echo "Properties with vacation_apartments.bedrooms = {$bedroomsIntValue}: {$vacationAptsWithBedrooms}\n\n";

// Step 4: Check vacation apartments with bedrooms = 1 AND property_type = 1
echo "Step 4: Vacation Apartments with bedrooms = {$bedroomsIntValue} AND property_type = {$propertyType}\n";
$vacationAptsWithBedroomsAndType = DB::table('vacation_apartments')
    ->join('propertys', 'vacation_apartments.property_id', '=', 'propertys.id')
    ->where('propertys.property_classification', 4)
    ->where('propertys.propery_type', $propertyType)
    ->where('propertys.status', 1)
    ->where('propertys.request_status', 'approved')
    ->where('vacation_apartments.status', 1)
    ->where('vacation_apartments.bedrooms', $bedroomsIntValue)
    ->distinct('propertys.id')
    ->count('propertys.id');
echo "Properties with vacation_apartments.bedrooms = {$bedroomsIntValue} AND property_type = {$propertyType}: {$vacationAptsWithBedroomsAndType}\n\n";

// Step 5: Test the actual whereHas query
echo "Step 5: Testing whereHas Query\n";
$propertiesWithWhereHas = Property::where('property_classification', 4)
    ->where('propery_type', $propertyType)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->whereHas('vacationApartments', function ($aptQuery) use ($bedroomsIntValue) {
        $aptQuery->where('status', 1)
            ->where('bedrooms', $bedroomsIntValue);
    })
    ->count();
echo "Properties matched by whereHas query: {$propertiesWithWhereHas}\n\n";

// Step 6: Show sample properties
echo "Step 6: Sample Properties (first 5)\n";
$sampleProperties = Property::where('property_classification', 4)
    ->where('propery_type', $propertyType)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->whereHas('vacationApartments', function ($aptQuery) use ($bedroomsIntValue) {
        $aptQuery->where('status', 1)
            ->where('bedrooms', $bedroomsIntValue);
    })
    ->with(['vacationApartments' => function ($query) use ($bedroomsIntValue) {
        $query->where('status', 1)
            ->where('bedrooms', $bedroomsIntValue);
    }])
    ->limit(5)
    ->get(['id', 'title', 'propery_type', 'property_classification']);

if ($sampleProperties->count() > 0) {
    foreach ($sampleProperties as $prop) {
        echo "  - ID: {$prop->id}, Title: {$prop->title}\n";
        echo "    Property Type: {$prop->propery_type}, Classification: {$prop->property_classification}\n";
        echo "    Vacation Apartments: " . $prop->vacationApartments->count() . "\n";
        foreach ($prop->vacationApartments as $apt) {
            echo "      - Apt #{$apt->apartment_number}: bedrooms={$apt->bedrooms}, status={$apt->status}\n";
        }
    }
} else {
    echo "  No properties found\n";
}

echo "\n";

// Step 7: Check assign_parameters for vacation homes
echo "Step 7: Vacation Homes with assign_parameters bedrooms = {$bedroomsValue}\n";
$assignParamsCount = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->join('propertys', function($join) {
        $join->on('assign_parameters.property_id', '=', 'propertys.id')
            ->orOn(function($q) {
                $q->on('assign_parameters.modal_id', '=', 'propertys.id')
                  ->where(function($typeQuery) {
                      $typeQuery->where('assign_parameters.modal_type', 'App\\Models\\Property')
                                ->orWhere('assign_parameters.modal_type', 'property');
                  });
            });
    })
    ->where('propertys.property_classification', 4)
    ->where('propertys.propery_type', $propertyType)
    ->where('propertys.status', 1)
    ->where('propertys.request_status', 'approved')
    ->where(function ($nameQuery) {
        $nameQuery->where('parameters.name', 'LIKE', '%bedroom%')
            ->orWhere('parameters.name', 'LIKE', '%bed%');
    })
    ->where(function ($valueQuery) use ($bedroomsValue) {
        $valueQuery->where('assign_parameters.value', $bedroomsValue)
            ->orWhere('assign_parameters.value', '"' . $bedroomsValue . '"')
            ->orWhereRaw('TRIM(assign_parameters.value) = ?', [$bedroomsValue])
            ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', [$bedroomsValue])
            ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', [$bedroomsValue])
            ->orWhereRaw('TRIM(CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR)) = ?', [$bedroomsValue]);
    })
    ->distinct('propertys.id')
    ->count('propertys.id');
echo "Properties matched via assign_parameters: {$assignParamsCount}\n\n";

// Step 8: Combined query (simulating the actual API query)
echo "Step 8: Combined Query (whereHas OR whereExists)\n";
$combinedQuery = Property::where('property_classification', 4)
    ->where('propery_type', $propertyType)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->where(function ($query) use ($bedroomsValue, $bedroomsIntValue) {
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
    })
    ->count();
echo "Properties matched by combined query: {$combinedQuery}\n\n";

echo "================================================\n";
echo "Test Complete\n";

