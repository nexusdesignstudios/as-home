<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Property;

echo "Checking all 23 properties with 2 bedrooms...\n\n";

// Get all 23 property IDs
$all23Ids = [79, 313, 320, 321, 322, 332, 335, 336, 81, 92, 93, 94, 99, 132, 139, 140, 141, 161, 163, 166, 167, 170, 171];

$bedroomsValue = "2";

// Test the new query
$filteredProperties = Property::where('propery_type', 0)
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->where(function ($query) use ($bedroomsValue) {
        $query->whereExists(function ($existsQuery) use ($bedroomsValue) {
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
                    $valueQuery->where('assign_parameters.value', $bedroomsValue)
                        ->orWhere('assign_parameters.value', '"' . $bedroomsValue . '"')
                        ->orWhereRaw('JSON_EXTRACT(assign_parameters.value, "$") = ?', [$bedroomsValue])
                        ->orWhereRaw('CAST(JSON_EXTRACT(assign_parameters.value, "$") AS CHAR) = ?', [$bedroomsValue]);
                });
        });
    })
    ->pluck('id')
    ->toArray();

echo "Properties found by new query: " . count($filteredProperties) . "\n";
echo "Expected: 23\n\n";

$missing = array_diff($all23Ids, $filteredProperties);
if (count($missing) > 0) {
    echo "Missing properties: " . implode(', ', $missing) . "\n\n";
    
    // Check why they're missing
    foreach ($missing as $id) {
        $prop = Property::find($id);
        if ($prop) {
            $assignParams = DB::table('assign_parameters')
                ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
                ->where(function($q) use ($id) {
                    $q->where('assign_parameters.property_id', $id)
                      ->orWhere(function($mq) use ($id) {
                          $mq->where('assign_parameters.modal_id', $id)
                             ->where('assign_parameters.modal_type', 'App\\Models\\Property');
                      });
                })
                ->where(function($nq) {
                    $nq->where('parameters.name', 'LIKE', '%bedroom%')
                       ->orWhere('parameters.name', 'LIKE', '%bed%');
                })
                ->where(function($vq) use ($bedroomsValue) {
                    $vq->where('assign_parameters.value', $bedroomsValue)
                       ->orWhere('assign_parameters.value', '"' . $bedroomsValue . '"');
                })
                ->select('assign_parameters.*', 'parameters.name')
                ->get();
            
            echo "Property ID $id ({$prop->title}):\n";
            echo "  Found in assign_parameters: " . $assignParams->count() . " records\n";
            foreach ($assignParams as $ap) {
                echo "    - property_id: {$ap->property_id}, modal_id: {$ap->modal_id}, modal_type: {$ap->modal_type}, value: {$ap->value}\n";
            }
            echo "\n";
        }
    }
} else {
    echo "✅ All 23 properties are being returned!\n";
}

