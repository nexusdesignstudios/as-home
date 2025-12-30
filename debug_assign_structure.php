<?php

// Check the exact assign_parameters data structure for property 333
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "=== Checking Assign Parameters Data Structure for Property 333 ===\n";

// Check all assign_parameters for property 333
$allAssignParams = DB::table('assign_parameters')
    ->where(function($q) {
        $q->where('property_id', 333)
          ->orWhere('modal_id', 333);
    })
    ->select('*')
    ->get();

echo "All assign_parameters for property 333:\n";
foreach ($allAssignParams as $param) {
    echo "  ID: {$param->id}\n";
    echo "  Property ID: {$param->property_id}\n";
    echo "  Modal ID: {$param->modal_id}\n";
    echo "  Modal Type: {$param->modal_type}\n";
    echo "  Parameter ID: {$param->parameter_id}\n";
    echo "  Value: {$param->value}\n";
    echo "  ---\n";
}

// Check the parameter name for parameter_id = 2 (bedroom parameter)
$parameter = DB::table('parameters')
    ->where('id', 2)
    ->first();

echo "Parameter ID 2: {$parameter->name}\n";

// Now check the specific bedroom parameter
$bedroomParam = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where(function($q) {
        $q->where('assign_parameters.property_id', 333)
          ->orWhere(function($q2) {
              $q2->where('assign_parameters.modal_id', 333)
                 ->where('assign_parameters.modal_type', 'like', '%Property%');
          });
    })
    ->where('parameters.name', 'bedrooms')
    ->first();

echo "\nBedroom parameter for property 333:\n";
if ($bedroomParam) {
    echo "  Found: {$bedroomParam->name} = {$bedroomParam->value}\n";
} else {
    echo "  Not found\n";
}