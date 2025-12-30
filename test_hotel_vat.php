<?php

// Test script to verify hotel VAT field functionality
// This script will check if the hotel_vat field is properly handled in the Property model and controller

echo "=== Testing Hotel VAT Field Functionality ===\n\n";

// Test 1: Check if hotel_vat is in the Property model fillable array
echo "1. Checking Property model fillable fields...\n";
require_once __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\Property;

$property = new Property();
$fillable = $property->getFillable();

if (in_array('hotel_vat', $fillable)) {
    echo "✓ hotel_vat is in the fillable array\n";
} else {
    echo "✗ hotel_vat is NOT in the fillable array\n";
}

// Test 2: Check if we can create a property with hotel_vat
echo "\n2. Testing property creation with hotel_vat...\n";
try {
    // Create a test property with hotel_vat
    $testProperty = Property::create([
        'title' => 'Test Property with VAT',
        'category_id' => 1,
        'price' => 100,
        'hotel_vat' => 14.5,
        'property_classification' => 5, // Hotel booking
        'status' => 0,
        'propery_type' => 'hotel'
    ]);
    
    echo "✓ Successfully created property with hotel_vat: {$testProperty->hotel_vat}%\n";
    
    // Clean up
    $testProperty->delete();
    echo "✓ Test property cleaned up\n";
    
} catch (Exception $e) {
    echo "✗ Error creating property: " . $e->getMessage() . "\n";
}

// Test 3: Check if hotel_vat field exists in database
echo "\n3. Checking database structure...\n";
try {
    $columns = app('db')->select("SHOW COLUMNS FROM propertys LIKE 'hotel_vat'");
    if (count($columns) > 0) {
        echo "✓ hotel_vat column exists in propertys table\n";
        echo "  Type: {$columns[0]->Type}\n";
        echo "  Null: {$columns[0]->Null}\n";
        echo "  Default: " . ($columns[0]->Default ?? 'NULL') . "\n";
    } else {
        echo "✗ hotel_vat column does NOT exist in propertys table\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking database: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";