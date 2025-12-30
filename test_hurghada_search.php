<?php

// Test script to debug Hurghada search issue

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Testing Hurghada Search Issue ===\n\n";

// First, let's check the Coral Mirage Hotel directly
echo "1. Checking Coral Mirage Hotel (property_id: 239):\n";
$coralMirage = DB::table('propertys')
    ->where('id', 239)
    ->select('id', 'title', 'city', 'status', 'request_status', 'property_classification')
    ->first();

if ($coralMirage) {
    echo "   Found: ID={$coralMirage->id}, Title='{$coralMirage->title}'\n";
    echo "   City: '{$coralMirage->city}' (note: case sensitive)\n";
    echo "   Status: {$coralMirage->status}, Request Status: {$coralMirage->request_status}\n";
    echo "   Property Classification: {$coralMirage->property_classification}\n";
} else {
    echo "   ❌ Coral Mirage Hotel not found!\n";
}

echo "\n2. Testing case-insensitive search for 'hurghada':\n";

// Test with lowercase 'hurghada'
$lowercaseResults = DB::table('propertys')
    ->whereRaw('LOWER(city) = LOWER(?)', ['hurghada'])
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->select('id', 'title', 'city')
    ->get();

echo "   Lowercase 'hurghada' search results: " . $lowercaseResults->count() . " properties\n";
foreach ($lowercaseResults as $property) {
    echo "   - ID: {$property->id}, Title: '{$property->title}', City: '{$property->city}'\n";
}

// Test with uppercase 'Hurghada'
echo "\n3. Testing case-insensitive search for 'Hurghada':\n";
$uppercaseResults = DB::table('propertys')
    ->whereRaw('LOWER(city) = LOWER(?)', ['Hurghada'])
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->select('id', 'title', 'city')
    ->get();

echo "   Uppercase 'Hurghada' search results: " . $uppercaseResults->count() . " properties\n";
foreach ($uppercaseResults as $property) {
    echo "   - ID: {$property->id}, Title: '{$property->title}', City: '{$property->city}'\n";
}

// Test with LIKE for any variation
echo "\n4. Testing case-insensitive LIKE search for '%hurghada%':\n";
$likeResults = DB::table('propertys')
    ->whereRaw('LOWER(city) LIKE LOWER(?)', ['%hurghada%'])
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->select('id', 'title', 'city')
    ->get();

echo "   LIKE '%hurghada%' search results: " . $likeResults->count() . " properties\n";
foreach ($likeResults as $property) {
    echo "   - ID: {$property->id}, Title: '{$property->title}', City: '{$property->city}'\n";
}

// Check all unique city values
echo "\n5. All unique city values in database:\n";
$cities = DB::table('propertys')
    ->select('city')
    ->distinct()
    ->orderBy('city')
    ->get();

foreach ($cities as $city) {
    if (stripos($city->city, 'hurghada') !== false) {
        echo "   - '{$city->city}' (contains 'hurghada')\n";
    }
}

echo "\n=== Test Complete ===\n";