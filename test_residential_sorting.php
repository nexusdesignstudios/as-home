<?php

// Simulate the frontend request for Residential (Buy), Sorted by Price High to Low
// URL: http://localhost:3000/search/?selectedClassification=1&selectedPropType=0&isCommercial=false&activeTab=0
// Params based on SearchPage.jsx logic:
// property_classification = 1
// property_type = 0
// is_commercial = 0
// sort_by = price_desc
// limit = 8
// offset = 0 (Page 1)

$baseUrl = "http://127.0.0.1:8001/api/get-property-list";
$params = [
    'property_classification' => '1',
    'property_type' => '0',
    'is_commercial' => '0',
    'sort_by' => 'price_desc',
    'limit' => '8',
    'offset' => '0',
    // Add other params that might be sent empty
    'min_price' => '0',
    'max_price' => '',
    'min_area' => '',
    'max_area' => '',
    'search' => '',
    'category_id' => '', // Assuming no category selected
];

$queryString = http_build_query($params);
$url = $baseUrl . "?" . $queryString;

echo "Testing URL: " . $url . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if ($response === false) {
    echo "Curl error: " . curl_error($ch);
    exit;
}
curl_close($ch);

$data = json_decode($response, true);

echo "Page 1 Results (Top 8 High to Low):\n";
if (isset($data['data'])) {
    foreach ($data['data'] as $index => $property) {
        echo ($index + 1) . ". ID: " . $property['id'] . " | Price: " . $property['price'] . " | Title: " . $property['title'] . "\n";
    }
} else {
    echo "No data found or error in response.\n";
    print_r($data);
}

// Test Page 2
$params['offset'] = '8';
$queryString2 = http_build_query($params);
$url2 = $baseUrl . "?" . $queryString2;

echo "\nTesting Page 2 URL: " . $url2 . "\n\n";

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $url2);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
$response2 = curl_exec($ch2);

if ($response2 === false) {
    echo "Curl error: " . curl_error($ch2);
    exit;
}
curl_close($ch2);

$data2 = json_decode($response2, true);

echo "Page 2 Results (Next 8):\n";
if (isset($data2['data'])) {
    foreach ($data2['data'] as $index => $property) {
        echo ($index + 1) . ". ID: " . $property['id'] . " | Price: " . $property['price'] . " | Title: " . $property['title'] . "\n";
    }
} else {
    echo "No data found or error in response.\n";
}
