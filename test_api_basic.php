<?php

// Test basic API endpoint without filters
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Basic API Endpoint ===\n\n";

// Test basic endpoint without filters
echo "1. Testing basic endpoint (no filters):\n";
$basicResponse = file_get_contents('http://127.0.0.1:8000/api/get_property?offset=0&limit=5');
$basicData = json_decode($basicResponse, true);

echo "Total properties: " . ($basicData['totalProperties'] ?? 0) . "\n";
echo "Returned properties: " . ($basicData['returnedProperties'] ?? 0) . "\n";

if (isset($basicData['properties']) && count($basicData['properties']) > 0) {
    echo "Sample properties:\n";
    foreach ($basicData['properties'] as $prop) {
        echo "- ID: {$prop['id']}, Title: {$prop['title']}\n";
    }
} else {
    echo "❌ No properties found\n";
}

// Test with just property_classification filter
echo "\n2. Testing with property_classification=4 only:\n";
$classificationResponse = file_get_contents('http://127.0.0.1:8000/api/get_property?property_classification=4&offset=0&limit=5');
$classificationData = json_decode($classificationResponse, true);

echo "Total properties: " . ($classificationData['totalProperties'] ?? 0) . "\n";
echo "Returned properties: " . ($classificationData['returnedProperties'] ?? 0) . "\n";

if (isset($classificationData['properties']) && count($classificationData['properties']) > 0) {
    echo "Vacation homes found:\n";
    foreach ($classificationData['properties'] as $prop) {
        echo "- ID: {$prop['id']}, Title: {$prop['title']}\n";
    }
} else {
    echo "❌ No vacation homes found\n";
}

// Test with property_classification=4 and bedrooms=studio
echo "\n3. Testing with property_classification=4 and bedrooms=studio:\n";
$studioSimpleResponse = file_get_contents('http://127.0.0.1:8000/api/get_property?property_classification=4&bedrooms=studio&offset=0&limit=5');
$studioSimpleData = json_decode($studioSimpleResponse, true);

echo "Total properties: " . ($studioSimpleData['totalProperties'] ?? 0) . "\n";
echo "Returned properties: " . ($studioSimpleData['returnedProperties'] ?? 0) . "\n";

if (isset($studioSimpleData['properties']) && count($studioSimpleData['properties']) > 0) {
    echo "Studio vacation homes found:\n";
    foreach ($studioSimpleData['properties'] as $prop) {
        echo "- ID: {$prop['id']}, Title: {$prop['title']}\n";
    }
} else {
    echo "❌ No studio vacation homes found\n";
}

echo "\n=== API Status ===\n";
if (($basicData['totalProperties'] ?? 0) > 0) {
    echo "✅ API endpoint is working\n";
} else {
    echo "❌ API endpoint is not returning data\n";
}