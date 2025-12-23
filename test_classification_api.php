<?php

/**
 * Test classification filter API endpoint
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Http\Request;

echo "========================================\n";
echo "Testing Classification Filter API Logic\n";
echo "========================================\n\n";

// Simulate request scenarios
$scenarios = [
    ['property_classification' => '1', 'description' => 'Classification = 1 (Residential)'],
    ['property_classification' => '2', 'description' => 'Classification = 2 (Commercial)'],
    ['property_classification' => '4', 'description' => 'Classification = 4 (Vacation Homes)'],
    ['property_classification' => '', 'description' => 'Classification = empty string'],
    ['property_classification' => null, 'description' => 'Classification = null'],
    ['description' => 'Classification not provided'],
];

$testUserId = 32;

foreach ($scenarios as $scenario) {
    $description = $scenario['description'];
    unset($scenario['description']);
    
    echo "Scenario: {$description}\n";
    
    // Simulate the query logic
    $query = Property::where(['post_type' => 1, 'added_by' => $testUserId]);
    
    // Simulate the when() condition
    $hasClassification = isset($scenario['property_classification']);
    $classificationValue = $scenario['property_classification'] ?? null;
    $shouldFilter = $hasClassification && $classificationValue !== '' && $classificationValue !== null;
    
    if ($shouldFilter) {
        $query = $query->where('property_classification', (int)$classificationValue);
        echo "  Filter applied: property_classification = " . (int)$classificationValue . "\n";
    } else {
        echo "  No filter applied (showing all classifications)\n";
    }
    
    $count = $query->count();
    echo "  Result: {$count} properties\n\n";
}

// Test with actual values
echo "========================================\n";
echo "Testing with Real Data\n";
echo "========================================\n\n";

$userId = 32;
$classifications = [1, 2, 3, 4, 5];

foreach ($classifications as $classification) {
    $count = Property::where(['post_type' => 1, 'added_by' => $userId])
        ->where('property_classification', $classification)
        ->count();
    
    echo "User {$userId} - Classification {$classification}: {$count} properties\n";
}

$total = Property::where(['post_type' => 1, 'added_by' => $userId])->count();
echo "\nUser {$userId} - All Classifications: {$total} properties\n";

