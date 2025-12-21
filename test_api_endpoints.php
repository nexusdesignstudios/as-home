<?php
/**
 * API Endpoint Testing Script
 * 
 * Usage:
 *   php test_api_endpoints.php
 * 
 * Or test specific endpoint:
 *   php test_api_endpoints.php get_categories
 */

$baseUrl = isset($argv[1]) && $argv[1] === 'local' 
    ? 'http://localhost:8000/api'
    : 'https://maroon-fox-767665.hostingersite.com/api';

$testSpecific = isset($argv[2]) ? $argv[2] : null;

$endpoints = [
    'get_categories' => [
        'url' => '/get_categories',
        'method' => 'GET',
        'params' => []
    ],
    'get_categories_with_params' => [
        'url' => '/get_categories',
        'method' => 'GET',
        'params' => ['offset' => 0, 'limit' => 10]
    ],
    'get_categories_empty_params' => [
        'url' => '/get_categories',
        'method' => 'GET',
        'params' => ['slug_id' => '', 'is_promoted' => '']
    ],
    'web_settings' => [
        'url' => '/web-settings',
        'method' => 'GET',
        'params' => []
    ],
    'homepage_data' => [
        'url' => '/homepage-data',
        'method' => 'GET',
        'params' => []
    ],
    'homepage_data_empty_coords' => [
        'url' => '/homepage-data',
        'method' => 'GET',
        'params' => ['latitude' => '', 'longitude' => '', 'radius' => '']
    ],
    'get_added_properties' => [
        'url' => '/get-added-properties',
        'method' => 'GET',
        'params' => ['offset' => 0, 'limit' => 100]
    ],
    'get_added_properties_empty' => [
        'url' => '/get-added-properties',
        'method' => 'GET',
        'params' => ['slug_id' => '', 'is_promoted' => '']
    ],
];

function testEndpoint($baseUrl, $name, $config) {
    $url = $baseUrl . $config['url'];
    if (!empty($config['params'])) {
        $url .= '?' . http_build_query($config['params']);
    }
    
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "Testing: {$name}\n";
    echo "URL: {$url}\n";
    echo str_repeat('-', 80) . "\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for testing
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable SSL host verification for testing
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $curlError = curl_error($ch);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    curl_close($ch);
    
    if ($curlError) {
        echo "CURL Error: {$curlError}\n";
        return [
            'name' => $name,
            'http_code' => 0,
            'is_json' => false,
            'has_error_field' => false,
            'has_message_field' => false,
            'is_valid_format' => false,
            'is_success' => false,
            'response' => null
        ];
    }
    
    // Parse JSON
    $json = json_decode($body, true);
    $jsonError = json_last_error();
    
    echo "HTTP Code: {$httpCode}\n";
    $isJson = strpos($headers, 'Content-Type: application/json') !== false;
    echo "Content-Type: " . ($isJson ? 'JSON ✓' : 'NOT JSON ✗') . "\n";
    
    if ($jsonError === JSON_ERROR_NONE) {
        echo "JSON Valid: ✓\n";
        echo "Has 'error' field: " . (isset($json['error']) ? '✓' : '✗') . "\n";
        echo "Has 'message' field: " . (isset($json['message']) ? '✓' : '✗') . "\n";
        
        if (isset($json['error'])) {
            echo "Error value: " . ($json['error'] ? 'true' : 'false') . "\n";
        }
        
        if (isset($json['message'])) {
            $msg = substr($json['message'], 0, 100);
            echo "Message: {$msg}\n";
        }
        
        if (isset($json['data'])) {
            $dataType = gettype($json['data']);
            echo "Data type: {$dataType}\n";
            if (is_array($json['data'])) {
                echo "Data count: " . count($json['data']) . "\n";
            }
        }
        
        // Check if response format is correct
        $isValid = isset($json['error']) && isset($json['message']);
        echo "\nResponse Format: " . ($isValid ? "✓ VALID" : "✗ INVALID") . "\n";
        
        // Check if it's an error
        if ($httpCode >= 400) {
            echo "Status: ✗ ERROR (HTTP {$httpCode})\n";
        } else {
            echo "Status: ✓ SUCCESS\n";
        }
        
    } else {
        echo "JSON Valid: ✗ (Error: " . json_last_error_msg() . ")\n";
        echo "Response Body (first 200 chars):\n";
        echo substr($body, 0, 200) . "\n";
        echo "\nStatus: ✗ INVALID RESPONSE\n";
    }
    
    return [
        'name' => $name,
        'http_code' => $httpCode,
        'is_json' => $jsonError === JSON_ERROR_NONE,
        'has_error_field' => isset($json['error']),
        'has_message_field' => isset($json['message']),
        'is_valid_format' => isset($json['error']) && isset($json['message']),
        'is_success' => $httpCode < 400 && isset($json['error']) && $json['error'] === false,
        'response' => $json
    ];
}

// Run tests
echo "API Endpoint Testing\n";
echo "Base URL: {$baseUrl}\n";
echo "Testing " . ($testSpecific ? "specific endpoint: {$testSpecific}" : "all endpoints") . "\n";

$results = [];
foreach ($endpoints as $name => $config) {
    if ($testSpecific && $name !== $testSpecific) {
        continue;
    }
    $results[] = testEndpoint($baseUrl, $name, $config);
    sleep(1); // Rate limiting
}

// Summary
echo "\n" . str_repeat('=', 80) . "\n";
echo "SUMMARY\n";
echo str_repeat('=', 80) . "\n";

$total = count($results);
$success = count(array_filter($results, function($r) { return $r['is_success']; }));
$validFormat = count(array_filter($results, function($r) { return $r['is_valid_format']; }));
$errors = count(array_filter($results, function($r) { return $r['http_code'] >= 400; }));

echo "Total Tests: {$total}\n";
echo "Successful: {$success}/{$total}\n";
echo "Valid Format: {$validFormat}/{$total}\n";
echo "Errors: {$errors}/{$total}\n";

if ($errors > 0) {
    echo "\nFailed Endpoints:\n";
    foreach ($results as $result) {
        if (!$result['is_success']) {
            echo "  ✗ {$result['name']} (HTTP {$result['http_code']})\n";
        }
    }
}

if ($validFormat < $total) {
    echo "\nInvalid Format Endpoints:\n";
    foreach ($results as $result) {
        if (!$result['is_valid_format']) {
            echo "  ✗ {$result['name']} - Missing required fields\n";
        }
    }
}

echo "\n";

