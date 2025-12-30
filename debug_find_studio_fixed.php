<?php

// Check all parameters to find bedroom-related ones
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "=== Checking All Parameters ===\n";

$parameters = DB::table('parameters')
    ->where('name', 'like', '%bedroom%')
    ->orWhere('name', 'like', '%studio%')
    ->get();

echo "Bedroom/Studio related parameters:\n";
foreach ($parameters as $param) {
    echo "  ID: {$param->id}, Name: {$param->name}\n";
}

// Check all assign_parameters for property 333
echo "\n=== All Assign Parameters for Property 333 ===\n";
$allAssignParams = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where(function($q) {
        $q->where('assign_parameters.property_id', 333)
          ->orWhere(function($q2) {
              $q2->where('assign_parameters.modal_id', 333)
                 ->where('assign_parameters.modal_type', 'like', '%Property%');
          });
    })
    ->select('parameters.name', 'assign_parameters.value', 'assign_parameters.parameter_id')
    ->get();

foreach ($allAssignParams as $param) {
    echo "  Parameter: {$param->name} = {$param->value} (ID: {$param->parameter_id})\n";
}

// Check if there's any "studio" value anywhere
echo "\n=== Searching for 'studio' in assign_parameters ===\n";
$studioParams = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where('assign_parameters.value', 'like', '%studio%')
    ->select('parameters.name', 'assign_parameters.value', 'assign_parameters.property_id', 'assign_parameters.modal_id')
    ->get();

foreach ($studioParams as $param) {
    echo "  Property ID: {$param->property_id}, Modal ID: {$param->modal_id}\n";
    echo "  Parameter: {$param->name} = {$param->value}\n";
    echo "  ---\n";
}

// Let's also check the user's original data - they mentioned property 333 has "studio" in assign_parameters
// Maybe it's in a different format or was added recently
echo "\n=== Checking if any properties have 'studio' in assign_parameters ===\n";

// Check the specific properties mentioned by user
$testProperties = [333, 334];
foreach ($testProperties as $propId) {
    echo "\nProperty $propId:\n";
    
    // Check assign_parameters
    $assignParams = DB::table('assign_parameters')
        ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
        ->where(function($q) use ($propId) {
            $q->where('assign_parameters.property_id', $propId)
              ->orWhere(function($q2) use ($propId) {
                  $q2->where('assign_parameters.modal_id', $propId)
                     ->where('assign_parameters.modal_type', 'like', '%Property%');
              });
        })
        ->select('parameters.name', 'assign_parameters.value')
        ->get();
    
    foreach ($assignParams as $param) {
        echo "  {$param->name} = {$param->value}\n";
    }
    
    if (count($assignParams) === 0) {
        echo "  No assign_parameters found\n";
    }
}