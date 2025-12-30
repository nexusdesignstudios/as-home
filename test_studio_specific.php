<?php

// Test studio filter specifically for "Apartment Test 01-20-12-2025" property
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;

echo "=== Testing Studio Filter for 'Apartment Test 01-20-12-2025' ===\n\n";

// Find the main property (property 333)
$mainProperty = Property::find(333);
if (!$mainProperty) {
    echo "❌ Property 333 not found\n";
    exit;
}

echo "Main Property Details:\n";
echo "- ID: {$mainProperty->id}\n";
echo "- Title: {$mainProperty->title}\n";
echo "- Classification: {$mainProperty->property_classification}\n";
echo "- Status: {$mainProperty->status}\n\n";

// Check vacation apartments
$vacationApts = DB::table('vacation_apartments')
    ->where('property_id', 333)
    ->select('id', 'apartment_number', 'bedrooms', 'bathrooms', 'status')
    ->get();

echo "Vacation Apartments ({$vacationApts->count()} total):\n";
foreach ($vacationApts as $apt) {
    echo "- Apartment {$apt->apartment_number}: {$apt->bedrooms} bedrooms, {$apt->bathrooms} bathrooms (Status: {$apt->status})\n";
}

// Check assign_parameters
$assignParams = DB::table('assign_parameters')
    ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
    ->where(function($q) use ($mainProperty) {
        $q->where('assign_parameters.property_id', $mainProperty->id)
          ->orWhere(function($q2) use ($mainProperty) {
              $q2->where('assign_parameters.modal_id', $mainProperty->id)
                 ->where('assign_parameters.modal_type', 'like', '%Property%');
          });
    })
    ->where('parameters.name', 'like', '%Bedroom%')
    ->select('parameters.name as parameter_name', 'assign_parameters.value')
    ->get();

echo "\nAssign Parameters Bedroom Data:\n";
foreach ($assignParams as $param) {
    echo "- {$param->parameter_name}: {$param->value}\n";
}

echo "\n=== Testing API Filter Logic ===\n";

// Test the actual API filtering logic for studio
echo "\n1. Testing STUDIO filter (bedrooms = 'studio'):\n";

// Simulate the API request
$apiRequest = new \Illuminate\Http\Request();
$apiRequest->merge([
    'bedrooms' => 'studio',
    'property_classification' => '4', // vacation homes
    'property_type' => '1',
    'category_id' => 'all categories (not filtered)',
    'location' => 'none',
    'priceRange' => 'none',
    'facilities' => 'none',
    'bathrooms' => 'none'
]);

$apiController = new \App\Http\Controllers\ApiController();

try {
    $response = $apiController->get_property($apiRequest);
    $data = is_array($response) ? $response : json_decode($response->getContent(), true);
    
    echo "Total properties found: " . ($data['totalProperties'] ?? 0) . "\n";
    echo "Returned properties: " . ($data['returnedProperties'] ?? 0) . "\n";
    
    if (isset($data['properties']) && count($data['properties']) > 0) {
        echo "✅ Properties found in studio filter:\n";
        foreach ($data['properties'] as $prop) {
            echo "- ID: {$prop['id']}, Title: {$prop['title']}, Bedrooms: {$prop['bedroomsFromParams']}\n";
        }
    } else {
        echo "❌ No properties found in studio filter\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing studio filter: " . $e->getMessage() . "\n";
}

echo "\n2. Testing 1 BEDROOM filter (bedrooms = '1'):\n";

// Test 1 bedroom filter
$apiRequest->merge(['bedrooms' => '1']);

try {
    $response = $apiController->get_property($apiRequest);
    $data = is_array($response) ? $response : json_decode($response->getContent(), true);
    
    echo "Total properties found: " . ($data['totalProperties'] ?? 0) . "\n";
    echo "Returned properties: " . ($data['returnedProperties'] ?? 0) . "\n";
    
    if (isset($data['properties']) && count($data['properties']) > 0) {
        echo "✅ Properties found in 1 bedroom filter:\n";
        foreach ($data['properties'] as $prop) {
            echo "- ID: {$prop['id']}, Title: {$prop['title']}, Bedrooms: {$prop['bedroomsFromParams']}\n";
        }
    } else {
        echo "❌ No properties found in 1 bedroom filter\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing 1 bedroom filter: " . $e->getMessage() . "\n";
}

echo "\n=== Analysis ===\n";
echo "Based on the vacation apartments data above, property 333 should:\n";
echo "- Appear in STUDIO filter if any vacation apartment has 'studio' bedrooms\n";
echo "- Appear in 1 BEDROOM filter if any vacation apartment has '1' bedrooms\n";
echo "- The assign_parameters 'studio' value should only affect studio filter\n";