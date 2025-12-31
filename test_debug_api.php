<?php
// Simple test to debug API parameters
$baseUrl = 'http://127.0.0.1:8001';

// Test 1: Both min and max price
echo "🧪 Test 1: Both min_price=1000 and max_price=2000\n";
$url1 = $baseUrl . '/api/get-property-list?property_classification=5&min_price=1000&max_price=2000&limit=1';
echo "URL: " . $url1 . "\n";
$response1 = file_get_contents($url1);
$data1 = json_decode($response1, true);
echo "Results: " . $data1['total'] . "\n\n";

// Test 2: Only min price
echo "🧪 Test 2: Only min_price=1000\n";
$url2 = $baseUrl . '/api/get-property-list?property_classification=5&min_price=1000&limit=1';
echo "URL: " . $url2 . "\n";
$response2 = file_get_contents($url2);
$data2 = json_decode($response2, true);
echo "Results: " . $data2['total'] . "\n\n";

// Test 3: Only max price
echo "🧪 Test 3: Only max_price=2000\n";
$url3 = $baseUrl . '/api/get-property-list?property_classification=5&max_price=2000&limit=1';
echo "URL: " . $url3 . "\n";
$response3 = file_get_contents($url3);
$data3 = json_decode($response3, true);
echo "Results: " . $data3['total'] . "\n\n";

echo "✅ Debug complete!\n";