<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture(),
    Illuminate\Http\Request::capture()
);

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\DB;

// Enable query logging
DB::enableQueryLog();

$apiController = new ApiController();

// Test Studio filter with query logging
echo "=== Testing Studio Filter with Query Logging ===\n";

$requestStudio = new Request([
    'offset' => 0,
    'limit' => 10,
    'bedrooms' => '0'
]);

$responseStudio = $apiController->get_property($requestStudio);
$dataStudio = is_array($responseStudio) ? $responseStudio : json_decode($responseStudio->getContent(), true);

// Get the executed queries
$queries = DB::getQueryLog();

echo "Number of queries executed: " . count($queries) . "\n";
foreach ($queries as $index => $query) {
    echo "Query " . ($index + 1) . ":\n";
    echo "SQL: " . $query['query'] . "\n";
    echo "Bindings: " . json_encode($query['bindings']) . "\n";
    echo "Time: " . $query['time'] . "ms\n\n";
}

echo "Properties found: " . ($dataStudio['total'] ?? 'unknown') . "\n";
if (isset($dataStudio['data']) && is_array($dataStudio['data'])) {
    foreach ($dataStudio['data'] as $property) {
        echo "Property ID: {$property['id']}, Classification: {$property['property_classification']}\n";
    }
}