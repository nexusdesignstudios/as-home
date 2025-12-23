<?php

/**
 * Test classification filter in getAddedProperties API
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Classification Filter Test\n";
echo "========================================\n\n";

// Test each classification value
$classifications = [
    1 => "Sell/Rent (Residential)",
    2 => "Commercial",
    3 => "New Project",
    4 => "Vacation Homes",
    5 => "Hotel Booking"
];

foreach ($classifications as $classificationId => $label) {
    echo "Classification {$classificationId} ({$label}):\n";
    
    $count = Property::where('post_type', 1)
        ->where('property_classification', $classificationId)
        ->count();
    
    echo "  Total properties: {$count}\n";
    
    // Get a sample property
    $sample = Property::where('post_type', 1)
        ->where('property_classification', $classificationId)
        ->select('id', 'title', 'property_classification')
        ->first();
    
    if ($sample) {
        $rawClassification = $sample->getRawOriginal('property_classification');
        echo "  Sample property ID: {$sample->id}\n";
        echo "  Sample title: {$sample->title}\n";
        echo "  Raw classification: {$rawClassification}\n";
        echo "  Accessor classification: " . ($sample->property_classification ?? 'null') . "\n";
    }
    echo "\n";
}

// Test empty classification (should return all)
echo "========================================\n";
echo "Empty Classification (All):\n";
echo "========================================\n\n";

$allCount = Property::where('post_type', 1)->count();
echo "Total properties (all): {$allCount}\n\n";

// Test the actual query that would be used
echo "========================================\n";
echo "Testing Query Logic\n";
echo "========================================\n\n";

$testUserId = 32; // Example user ID

// Test with classification = 4 (Vacation Homes)
$query = Property::where(['post_type' => 1, 'added_by' => $testUserId])
    ->when(true, function ($query) {
        return $query->where('property_classification', 4);
    });

$result = $query->count();
echo "Properties for user {$testUserId} with classification 4: {$result}\n";

// Test with empty classification
$query2 = Property::where(['post_type' => 1, 'added_by' => $testUserId])
    ->when(false, function ($query) {
        return $query->where('property_classification', '');
    });

$result2 = $query2->count();
echo "Properties for user {$testUserId} with no classification filter: {$result2}\n\n";

// Check if property_classification can be string or integer
echo "========================================\n";
echo "Data Type Check\n";
echo "========================================\n\n";

$sample = Property::select('id', 'property_classification')
    ->where('post_type', 1)
    ->first();

if ($sample) {
    $raw = $sample->getRawOriginal('property_classification');
    echo "Sample property ID: {$sample->id}\n";
    echo "Raw value: " . var_export($raw, true) . " (type: " . gettype($raw) . ")\n";
    echo "Accessor value: " . var_export($sample->property_classification, true) . " (type: " . gettype($sample->property_classification) . ")\n";
}

