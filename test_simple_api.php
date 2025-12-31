<?php

// Simple test to see if the API is working
$baseUrl = 'http://localhost:8000';
$endpoint = '/api/get-property-list';

// Test with simple parameters (no price filtering)
$params = [
    'property_classification' => '5',
    'limit' => '2',
    'offset' => '0'
];

$url = $baseUrl . $endpoint . '?' . http_build_query($params);

echo "🧪 Testing Simple API Request:\n";
echo "URL: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Shorter timeout
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ cURL Error: $error\n";
} elseif ($httpCode !== 200) {
    echo "❌ HTTP Error: $httpCode\n";
    echo "Response: $response\n";
} else {
    $data = json_decode($response, true);
    
    if (isset($data['error']) && $data['error'] === false) {
        echo "✅ API Call Successful!\n";
        echo "Total Properties Found: " . ($data['total'] ?? 0) . "\n";
        echo "Properties Returned: " . count($data['data'] ?? []) . "\n\n";
        
        if (!empty($data['data'])) {
            echo "📋 Sample Properties:\n";
            foreach (array_slice($data['data'], 0, 2) as $index => $property) {
                $title = $property['title'] ?? 'Unknown Title';
                $price = $property['price'] ?? 'N/A';
                echo sprintf("%d. %s (Price: %s EGP)\n", $index + 1, $title, $price);
            }
        }
    } else {
        echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
}

echo "\n🎯 Test Complete!\n";