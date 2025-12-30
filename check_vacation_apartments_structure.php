<?php

// Check vacation_apartments table structure
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

// Get table schema
$columns = DB::select('DESCRIBE vacation_apartments');

echo "=== vacation_apartments Table Structure ===\n";
foreach ($columns as $column) {
    echo "Field: {$column->Field}, Type: {$column->Type}, Null: {$column->Null}, Key: {$column->Key}, Default: {$column->Default}\n";
}

echo "\n=== Sample Data ===\n";
$sample = DB::table('vacation_apartments')
    ->where('property_id', 333)
    ->first();

if ($sample) {
    echo "Property 333 vacation apartment data:\n";
    foreach (get_object_vars($sample) as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
} else {
    echo "No vacation apartments found for property 333\n";
}