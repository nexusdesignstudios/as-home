<?php

// Check what's the real assign_parameters data for property 333
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "=== Checking ALL assign_parameters for Property 333 ===\n";

// Get ALL assign_parameters data for property 333
$allAssignParams = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where(function($q) {
        $q->where('assign_parameters.property_id', 333)
          ->orWhere('assign_parameters.modal_id', 333);
    })
    ->select('assign_parameters.*', 'parameters.name as param_name')
    ->get();

echo "Total assign_parameters records for property 333: " . $allAssignParams->count() . "\n\n";

foreach ($allAssignParams as $param) {
    echo "Record ID: {$param->id}\n";
    echo "  Property ID: {$param->property_id}\n";
    echo "  Modal ID: {$param->modal_id}\n";
    echo "  Modal Type: {$param->modal_type}\n";
    echo "  Parameter ID: {$param->parameter_id}\n";
    echo "  Parameter Name: {$param->param_name}\n";
    echo "  Value: {$param->value}\n";
    echo "  Created: {$param->created_at}\n\n";
}

// Now test the specific query that's used in the API
echo "=== Testing API's Specific Query ===\n";

$apiQueryResults = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where(function($q) {
        $q->where('assign_parameters.property_id', 333)
          ->orWhere(function($q2) {
              $q2->where('assign_parameters.modal_id', 333)
                 ->where(function($typeQuery) {
                     $typeQuery->where('assign_parameters.modal_type', 'like', '%Property%')
                               ->orWhere('assign_parameters.modal_type', 'like', '%property%');
                 });
          });
    })
    ->where(function($nameQuery) {
        $nameQuery->where('parameters.name', 'LIKE', '%bedroom%')
            ->orWhere('parameters.name', 'LIKE', '%bed%');
    })
    ->select('assign_parameters.value', 'parameters.name')
    ->get();

echo "API query results: " . $apiQueryResults->count() . " records found\n";
foreach ($apiQueryResults as $result) {
    echo "  Parameter: {$result->name}, Value: {$result->value}\n";
}