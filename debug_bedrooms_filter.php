<?php

/**
 * Debug script to check why some properties with 2 bedrooms are not being returned
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Property;
use App\Models\AssignParameters;

echo "========================================\n";
echo "Debugging Bedrooms Filter Issue\n";
echo "========================================\n\n";

// Get all properties with 2 bedrooms from assign_parameters
$allPropertiesWith2Bedrooms = DB::table('assign_parameters')
    ->join('propertys', function($join) {
        $join->on('assign_parameters.property_id', '=', 'propertys.id')
             ->orOn('assign_parameters.modal_id', '=', 'propertys.id');
    })
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where('propertys.propery_type', 0)
    ->where('propertys.status', 1)
    ->where('propertys.request_status', 'approved')
    ->where(function($q) {
        $q->where('parameters.name', 'LIKE', '%bedroom%')
          ->orWhere('parameters.name', 'LIKE', '%bed%');
    })
    ->where(function($q) {
        $q->where('assign_parameters.value', '2')
          ->orWhere('assign_parameters.value', '"2"')
          ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', ['2'])
          ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', ['2']);
    })
    ->select('propertys.id', 'propertys.title', 
             'assign_parameters.id as assign_param_id',
             'assign_parameters.modal_type',
             'assign_parameters.modal_id',
             'assign_parameters.property_id',
             'assign_parameters.value as raw_value',
             'parameters.name as param_name')
    ->get();

echo "Total properties with 2 bedrooms found: " . $allPropertiesWith2Bedrooms->count() . "\n\n";

// Check which ones are found by the relationship
$foundByRelationship = [];
$notFoundByRelationship = [];

foreach ($allPropertiesWith2Bedrooms as $row) {
    $property = Property::find($row->id);
    if (!$property) {
        continue;
    }
    
    $assignParams = $property->assignParameter()
        ->whereHas('parameter', function($q) {
            $q->where(function($nameQuery) {
                $nameQuery->where('name', 'LIKE', '%bedroom%')
                    ->orWhere('name', 'LIKE', '%bed%');
            });
        })
        ->get();
    
    $has2Bedrooms = false;
    foreach ($assignParams as $ap) {
        $value = $ap->value;
        if ($value == "2" || $value == 2 || $value === "2" || $value === 2) {
            $has2Bedrooms = true;
            break;
        }
    }
    
    if ($has2Bedrooms) {
        $foundByRelationship[] = $row;
    } else {
        $notFoundByRelationship[] = $row;
    }
}

echo "Properties FOUND by assignParameter relationship: " . count($foundByRelationship) . "\n";
echo "Properties NOT FOUND by assignParameter relationship: " . count($notFoundByRelationship) . "\n\n";

if (count($notFoundByRelationship) > 0) {
    echo "Properties NOT found by relationship:\n";
    foreach ($notFoundByRelationship as $row) {
        echo "  - ID: {$row->id}, Title: {$row->title}\n";
        echo "    assign_param_id: {$row->assign_param_id}\n";
        echo "    modal_type: {$row->modal_type}\n";
        echo "    modal_id: {$row->modal_id}\n";
        echo "    property_id: {$row->property_id}\n";
        echo "    raw_value: {$row->raw_value}\n";
        echo "    param_name: {$row->param_name}\n";
        
        // Check the property's assignParameter relationship
        $property = Property::find($row->id);
        $allAssignParams = $property->assignParameter()->get();
        echo "    Total assignParameter records for this property: " . $allAssignParams->count() . "\n";
        foreach ($allAssignParams as $ap) {
            echo "      - assign_param_id: {$ap->id}, modal_type: {$ap->modal_type}, modal_id: {$ap->modal_id}, property_id: {$ap->property_id}, value: {$ap->value}\n";
        }
        echo "\n";
    }
}

// Test the actual filter query
echo "\n========================================\n";
echo "Testing Filter Query\n";
echo "========================================\n\n";

$bedroomsValue = "2";
$filteredProperties = Property::where('propery_type', 0)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->where(function ($query) use ($bedroomsValue) {
        // Use the new query logic that handles both property_id and modal_id
        $query->whereExists(function ($existsQuery) use ($bedroomsValue) {
            $existsQuery->select(DB::raw(1))
                ->from('assign_parameters')
                ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                ->where(function ($linkQuery) {
                    // Match by property_id OR by modal_id (polymorphic)
                    $linkQuery->whereColumn('assign_parameters.property_id', 'propertys.id')
                        ->orWhere(function ($modalQuery) {
                            $modalQuery->whereColumn('assign_parameters.modal_id', 'propertys.id')
                                ->where('assign_parameters.modal_type', 'App\\Models\\Property');
                        });
                })
                ->where(function ($nameQuery) {
                    $nameQuery->where('parameters.name', 'LIKE', '%bedroom%')
                        ->orWhere('parameters.name', 'LIKE', '%bed%');
                })
                ->where(function ($valueQuery) use ($bedroomsValue) {
                    $valueQuery->where('assign_parameters.value', $bedroomsValue)
                        ->orWhere('assign_parameters.value', '"' . $bedroomsValue . '"')
                        ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', [$bedroomsValue])
                        ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', [$bedroomsValue]);
                });
        });
    })
    ->get(['id', 'title']);

echo "Properties returned by filter: " . $filteredProperties->count() . "\n";
echo "Expected: 23 (8 with property_id + 15 with modal_id)\n\n";

if ($filteredProperties->count() == 23) {
    echo "✅ SUCCESS: All 23 properties are now being returned!\n";
} elseif ($filteredProperties->count() > 8) {
    echo "✅ IMPROVEMENT: More properties are being returned (was 8, now " . $filteredProperties->count() . ")\n";
} else {
    echo "⚠️  Still missing some properties\n";
}

echo "\nAll returned properties:\n";
foreach ($filteredProperties as $prop) {
    echo "  - ID: {$prop->id}, Title: {$prop->title}\n";
}

echo "\n";

