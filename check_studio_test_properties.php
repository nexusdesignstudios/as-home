<?php

/**
 * Check how Studio bedrooms are stored for the 3 test properties
 * Properties: Test 01-20-12-2025 - 101, 202, 303
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\AssignParameters;
use App\Models\Parameter;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Studio Test Properties Database Check\n";
echo "========================================\n\n";

// Find the 3 test properties
$testPropertyTitles = [
    'Test 01-20-12-2025 - 101',
    'Test 01-20-12-2025 - 202',
    'Test 01-20-12-2025 - 303'
];

$testProperties = Property::whereIn('title', $testPropertyTitles)
    ->get(['id', 'title', 'property_classification', 'status', 'request_status']);

echo "Found " . $testProperties->count() . " test properties:\n\n";

foreach ($testProperties as $property) {
    echo "----------------------------------------\n";
    echo "Property ID: {$property->id}\n";
    echo "Title: {$property->title}\n";
    echo "Classification: {$property->property_classification}\n";
    echo "Status: {$property->status}, Request Status: {$property->request_status}\n\n";
    
    // Check assign_parameters for bedrooms
    echo "Bedrooms in assign_parameters:\n";
    $bedroomParams = DB::table('assign_parameters')
        ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
        ->where(function ($query) use ($property) {
            $query->where('assign_parameters.property_id', $property->id)
                ->orWhere(function ($q) use ($property) {
                    $q->where('assign_parameters.modal_id', $property->id)
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
        ->select(
            'assign_parameters.id',
            'assign_parameters.property_id',
            'assign_parameters.modal_id',
            'assign_parameters.modal_type',
            'assign_parameters.value as raw_value',
            'parameters.name as parameter_name',
            'parameters.id as parameter_id'
        )
        ->get();
    
    if ($bedroomParams->isEmpty()) {
        echo "  ❌ No bedroom parameters found!\n";
    } else {
        foreach ($bedroomParams as $param) {
            echo "  - Parameter ID: {$param->parameter_id}\n";
            echo "    Parameter Name: {$param->parameter_name}\n";
            echo "    Raw Value (from DB): '{$param->raw_value}'\n";
            echo "    Value Length: " . strlen($param->raw_value) . "\n";
            echo "    Value Type: " . gettype($param->raw_value) . "\n";
            
            // Try to decode as JSON
            $decoded = json_decode($param->raw_value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "    JSON Decoded: " . var_export($decoded, true) . "\n";
            } else {
                echo "    Not JSON (error: " . json_last_error_msg() . ")\n";
            }
            
            // Check if it matches Studio patterns
            $valueLower = strtolower(trim($param->raw_value));
            $isStudio = (
                $param->raw_value === '0' ||
                $valueLower === 'studio' ||
                $param->raw_value === 'Studio' ||
                $param->raw_value === 'STUDIO' ||
                $param->raw_value === '"0"' ||
                $param->raw_value === '"Studio"' ||
                $param->raw_value === '"studio"' ||
                $param->raw_value === '"STUDIO"'
            );
            echo "    Matches Studio Pattern: " . ($isStudio ? "✅ YES" : "❌ NO") . "\n";
            echo "\n";
        }
    }
    
    // Check vacation_apartments if it's a vacation home
    if ($property->property_classification == 4) {
        echo "Bedrooms in vacation_apartments:\n";
        $vacationApts = DB::table('vacation_apartments')
            ->where('property_id', $property->id)
            ->where('status', 1)
            ->select('id', 'apartment_number', 'bedrooms', 'bathrooms')
            ->get();
        
        if ($vacationApts->isEmpty()) {
            echo "  ❌ No active vacation apartments found!\n";
        } else {
            foreach ($vacationApts as $apt) {
                echo "  - Apartment ID: {$apt->id}, Number: {$apt->apartment_number}\n";
                echo "    Bedrooms: {$apt->bedrooms} (type: " . gettype($apt->bedrooms) . ")\n";
                echo "    Is Studio (0): " . ($apt->bedrooms == 0 ? "✅ YES" : "❌ NO") . "\n";
            }
        }
        echo "\n";
    }
    
    echo "\n";
}

// Test the exact query used in API
echo "========================================\n";
echo "Testing API Query Logic\n";
echo "========================================\n\n";

$bedroomsValue = '0';
$isStudio = true;

foreach ($testProperties as $property) {
    echo "Testing query for Property ID: {$property->id} ({$property->title})\n";
    
    // Test the exact whereExists query
    $matches = DB::table('propertys')
        ->where('propertys.id', $property->id)
        ->whereExists(function ($existsQuery) use ($bedroomsValue, $isStudio) {
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
                ->where(function ($valueQuery) use ($isStudio) {
                    $valueQuery->whereNotNull('assign_parameters.value')
                        ->where('assign_parameters.value', '!=', '')
                        ->where('assign_parameters.value', '!=', 'null')
                        ->whereRaw('TRIM(assign_parameters.value) != ?', ['']);
                    
                    if ($isStudio) {
                        $valueQuery->where(function ($studioQuery) {
                            $studioQuery->where('assign_parameters.value', '0')
                                ->orWhereRaw('LOWER(TRIM(assign_parameters.value)) = ?', ['studio'])
                                ->orWhere('assign_parameters.value', 'Studio')
                                ->orWhere('assign_parameters.value', 'STUDIO')
                                ->orWhere('assign_parameters.value', '"0"')
                                ->orWhere('assign_parameters.value', '"Studio"')
                                ->orWhere('assign_parameters.value', '"studio"')
                                ->orWhere('assign_parameters.value', '"STUDIO"')
                                ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', ['0'])
                                ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', ['Studio'])
                                ->orWhereRaw('LOWER(TRIM(JSON_EXTRACT(assign_parameters.value, "$"))) = ?', ['studio'])
                                ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', ['0'])
                                ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', ['Studio']);
                        });
                    }
                });
        })
        ->exists();
    
    echo "  Query Matches: " . ($matches ? "✅ YES" : "❌ NO") . "\n\n";
}

echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Check the output above to see:\n";
echo "1. How Studio is stored in assign_parameters.value\n";
echo "2. Whether the API query matches each property\n";
echo "3. Any issues with the query logic\n";

