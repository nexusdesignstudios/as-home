<?php

/**
 * Full API test for Studio filter
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;

echo "========================================\n";
echo "Full API Test - Studio Filter\n";
echo "========================================\n\n";

// Create a request exactly as frontend would send it
$request = Request::create('/api/get-property-list', 'GET', [
    'bedrooms' => '0',  // Studio
    'offset' => 0,
    'limit' => 20
]);

// Get the controller instance
$controller = new ApiController();

// Call the method
try {
    $response = $controller->getPropertyList($request);
    $responseData = json_decode($response->getContent(), true);
    
    echo "1. API Response:\n";
    echo "   Error: " . ($responseData['error'] ? 'YES' : 'NO') . "\n";
    echo "   Message: " . ($responseData['message'] ?? 'N/A') . "\n";
    echo "   Total: " . ($responseData['total'] ?? 0) . "\n";
    echo "   Data count: " . (isset($responseData['data']) ? count($responseData['data']) : 0) . "\n\n";
    
    if (isset($responseData['data']) && is_array($responseData['data'])) {
        echo "2. Properties Returned:\n";
        foreach ($responseData['data'] as $index => $property) {
            $title = $property['title'] ?? 'N/A';
            $id = $property['id'] ?? 'N/A';
            $classification = $property['property_classification'] ?? 'N/A';
            echo "   " . ($index + 1) . ". ID: {$id}, Title: {$title}, Classification: {$classification}\n";
        }
    }
    
    echo "\n3. Verification:\n";
    $expectedCount = 4; // From previous test
    $actualCount = isset($responseData['data']) ? count($responseData['data']) : 0;
    
    if ($actualCount == $expectedCount) {
        echo "   ✅ Expected {$expectedCount} properties, got {$actualCount}\n";
    } else {
        echo "   ⚠️  Expected {$expectedCount} properties, but got {$actualCount}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

