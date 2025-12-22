<?php

/**
 * Script to check database for sell properties with 2 bedrooms
 * and verify if the filter is working correctly
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Property;
use App\Models\AssignParameters;
use App\Models\parameter;

echo "========================================\n";
echo "Checking Sell Properties with 2 Bedrooms\n";
echo "========================================\n\n";

// Step 1: Find all sell properties (propery_type = 0) that are active and approved
echo "Step 1: Finding all active sell properties...\n";
$sellProperties = Property::where('propery_type', 0)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->get(['id', 'title', 'propery_type', 'property_classification', 'status', 'request_status']);

echo "Total sell properties found: " . $sellProperties->count() . "\n\n";

// Step 2: Find bedroom parameter ID
echo "Step 2: Finding bedroom parameter...\n";
$bedroomParam = parameter::where(function($q) {
    $q->where('name', 'LIKE', '%bedroom%')
      ->orWhere('name', 'LIKE', '%bed%');
})->first(['id', 'name']);

if (!$bedroomParam) {
    echo "ERROR: Bedroom parameter not found in database!\n";
    exit(1);
}

echo "Bedroom parameter found: ID={$bedroomParam->id}, Name='{$bedroomParam->name}'\n\n";

// Step 3: Find all properties with 2 bedrooms in assign_parameters table
echo "Step 3: Finding properties with 2 bedrooms in assign_parameters table...\n";
$propertiesWith2Bedrooms = DB::table('assign_parameters')
    ->join('propertys', function($join) {
        $join->on('assign_parameters.property_id', '=', 'propertys.id')
             ->orOn('assign_parameters.modal_id', '=', 'propertys.id');
    })
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where('propertys.propery_type', 0) // Sell type
    ->where('propertys.status', 1) // Active
    ->where('propertys.request_status', 'approved') // Approved
    ->where(function($q) {
        $q->where('parameters.name', 'LIKE', '%bedroom%')
          ->orWhere('parameters.name', 'LIKE', '%bed%');
    })
    ->where(function($q) {
        // Check for value = "2" (string, JSON string, or JSON number)
        // Handle both plain string "2" and JSON encoded values
        $q->where('assign_parameters.value', '2')
          ->orWhere('assign_parameters.value', '"2"')
          ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', ['2'])
          ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', ['"2"'])
          ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', ['2']);
    })
    ->select('propertys.id', 'propertys.title', 'propertys.propery_type', 
             'propertys.property_classification', 'parameters.name as param_name',
             'assign_parameters.value as param_value', 'assign_parameters.id as assign_param_id')
    ->distinct()
    ->get();

echo "Properties with 2 bedrooms in assign_parameters: " . $propertiesWith2Bedrooms->count() . "\n\n";

if ($propertiesWith2Bedrooms->count() > 0) {
    echo "Properties found:\n";
    foreach ($propertiesWith2Bedrooms as $prop) {
        echo "  - ID: {$prop->id}, Title: {$prop->title}, Classification: {$prop->property_classification}, Value: {$prop->param_value}\n";
    }
    echo "\n";
}

// Step 4: Check using the actual filter query (simulating the API filter)
echo "Step 4: Testing the actual filter query (simulating API behavior with updated logic)...\n";
$bedroomsValue = "2";
$filteredProperties = Property::where('propery_type', 0)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->whereHas('assignParameter', function ($query) use ($bedroomsValue) {
        $query->whereHas('parameter', function ($paramQuery) {
            $paramQuery->where(function ($nameQuery) {
                $nameQuery->where('name', 'LIKE', '%bedroom%')
                    ->orWhere('name', 'LIKE', '%bed%');
            });
        })->where(function ($valueQuery) use ($bedroomsValue) {
            // Match plain string value
            $valueQuery->where('value', $bedroomsValue)
                // Match JSON-encoded string value
                ->orWhere('value', '"' . $bedroomsValue . '"')
                // Match JSON-encoded number value (for numeric strings)
                ->orWhereRaw('JSON_EXTRACT(value, "$") = ?', [$bedroomsValue])
                ->orWhereRaw('CAST(JSON_EXTRACT(value, "$") AS CHAR) = ?', [$bedroomsValue]);
        });
    })
    ->with(['assignParameter.parameter' => function($q) {
        $q->where(function($nameQuery) {
            $nameQuery->where('name', 'LIKE', '%bedroom%')
                ->orWhere('name', 'LIKE', '%bed%');
        });
    }])
    ->get(['id', 'title', 'propery_type', 'property_classification']);

echo "Properties returned by filter query: " . $filteredProperties->count() . "\n\n";

if ($filteredProperties->count() > 0) {
    echo "Filtered properties:\n";
    foreach ($filteredProperties as $prop) {
        $bedroomParam = $prop->assignParameter->first(function($ap) {
            $p = $ap->parameter;
            return $p && (stripos($p->name, 'bedroom') !== false || stripos($p->name, 'bed') !== false);
        });
        $bedroomValue = $bedroomParam ? $bedroomParam->value : 'N/A';
        echo "  - ID: {$prop->id}, Title: {$prop->title}, Classification: {$prop->property_classification}, Bedrooms: {$bedroomValue}\n";
    }
    echo "\n";
}

// Step 5: Check raw assign_parameters data for debugging
echo "Step 5: Checking raw assign_parameters data for sample properties...\n";
$sampleProperties = $sellProperties->take(10);
foreach ($sampleProperties as $prop) {
    $assignedParams = AssignParameters::where('property_id', $prop->id)
        ->orWhere('modal_id', $prop->id)
        ->with('parameter')
        ->get();
    
    $bedroomParams = $assignedParams->filter(function($ap) {
        $p = $ap->parameter;
        return $p && (stripos($p->name, 'bedroom') !== false || stripos($p->name, 'bed') !== false);
    });
    
    if ($bedroomParams->count() > 0) {
        echo "  Property ID {$prop->id} ({$prop->title}):\n";
        foreach ($bedroomParams as $bp) {
            $rawValue = DB::table('assign_parameters')
                ->where('id', $bp->id)
                ->value('value');
            echo "    - Parameter: {$bp->parameter->name}, Value (accessor): {$bp->value}, Value (raw): {$rawValue}\n";
        }
    }
}

// Step 6: Summary
echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Total sell properties: " . $sellProperties->count() . "\n";
echo "Properties with 2 bedrooms (assign_parameters): " . $propertiesWith2Bedrooms->count() . "\n";
echo "Properties returned by filter query: " . $filteredProperties->count() . "\n";
echo "\n";

if ($propertiesWith2Bedrooms->count() > 0 && $filteredProperties->count() == 0) {
    echo "⚠️  ISSUE DETECTED: Properties exist in database but filter returns 0 results!\n";
    echo "   This suggests a problem with the filter query logic or value matching.\n";
} elseif ($propertiesWith2Bedrooms->count() == 0) {
    echo "⚠️  NO DATA: No sell properties have 2 bedrooms assigned in assign_parameters table.\n";
    echo "   This means the filter will return 0 results because there's no data to filter.\n";
} elseif ($propertiesWith2Bedrooms->count() == $filteredProperties->count()) {
    echo "✅ SUCCESS: Filter is working correctly!\n";
} else {
    echo "⚠️  PARTIAL MATCH: Some properties with 2 bedrooms are not being returned by filter.\n";
}

echo "\n";

