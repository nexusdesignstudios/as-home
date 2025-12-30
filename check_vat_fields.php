<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Bootstrap the application
$request = Illuminate\Http\Request::capture();
$kernel->bootstrap();

// Show all columns from propertys table
echo "=== Propertys Table Structure ===\n";
$columns = app('db')->select("SHOW COLUMNS FROM propertys");

foreach ($columns as $column) {
    echo "Field: {$column->Field}, Type: {$column->Type}, Null: {$column->Null}, Key: {$column->Key}, Default: {$column->Default}, Extra: {$column->Extra}\n";
}

echo "\n=== Searching for VAT/TAX related fields ===\n";
$vatColumns = app('db')->select("SHOW COLUMNS FROM propertys WHERE Field LIKE '%vat%' OR Field LIKE '%tax%' OR Field LIKE '%hotel%' OR Field LIKE '%vat%'");

if (count($vatColumns) > 0) {
    foreach ($vatColumns as $column) {
        echo "Found VAT/TAX Field: {$column->Field}, Type: {$column->Type}\n";
    }
} else {
    echo "No VAT/TAX related fields found in propertys table\n";
}

echo "\n=== Searching in other related tables ===\n";
$tables = app('db')->select("SHOW TABLES");
foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    if (stripos($tableName, 'vat') !== false || stripos($tableName, 'tax') !== false || stripos($tableName, 'hotel') !== false) {
        echo "Found related table: {$tableName}\n";
    }
}