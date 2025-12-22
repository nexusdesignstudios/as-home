<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Property;

$ids = [79, 313, 320, 321, 322, 332, 335, 336];

echo "Checking the 8 properties that were found before but are missing now...\n\n";

foreach($ids as $id) {
    $prop = Property::find($id);
    if (!$prop) {
        echo "Property ID $id: NOT FOUND\n\n";
        continue;
    }
    
    echo "Property ID $id: {$prop->title}\n";
    
    // Check assign_parameters with all possible links
    $records = DB::table('assign_parameters')
        ->where(function($q) use ($id) {
            $q->where('property_id', $id)
              ->orWhere('modal_id', $id);
        })
        ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
        ->where(function($nq) {
            $nq->where('parameters.name', 'LIKE', '%bedroom%')
               ->orWhere('parameters.name', 'LIKE', '%bed%');
        })
        ->select('assign_parameters.*', 'parameters.name as param_name')
        ->get();
    
    echo "  Found in assign_parameters: " . $records->count() . " records\n";
    
    if ($records->count() > 0) {
        foreach($records as $r) {
            echo "    - property_id: {$r->property_id}, modal_id: {$r->modal_id}, modal_type: {$r->modal_type}, value: {$r->value}, param: {$r->param_name}\n";
        }
    } else {
        // Check if property has assignParameter relationship working
        $assignParams = $prop->assignParameter()->get();
        echo "  assignParameter relationship returns: " . $assignParams->count() . " records\n";
        if ($assignParams->count() > 0) {
            foreach($assignParams as $ap) {
                $param = $ap->parameter;
                echo "    - ID: {$ap->id}, property_id: {$ap->property_id}, modal_id: {$ap->modal_id}, modal_type: {$ap->modal_type}, value: {$ap->value}";
                if ($param) {
                    echo ", param_name: {$param->name}";
                }
                echo "\n";
            }
        }
    }
    
    echo "\n";
}

