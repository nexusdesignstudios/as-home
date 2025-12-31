<?php

// Test the API endpoint directly
$baseUrl = 'http://localhost:8000'; // Adjust this to your backend URL
$endpoint = '/api/get-property-list';

// Test parameters matching the frontend
$params = [
    'property_classification' => '5',
    'min_price' => '1000',
    'max_price' => '2000',
    'limit' => '10',
    'offset' => '0'
];

$url = $baseUrl . $endpoint . '?' . http_build_query($params);

echo "🧪 Testing API Endpoint:\n";
echo "URL: $url\n\n";

// Make the API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
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
            echo "📋 Found Properties:\n";
            foreach ($data['data'] as $index => $property) {
                $title = $property['title'] ?? 'Unknown Title';
                $price = $property['price'] ?? 'N/A';
                $city = $property['city'] ?? 'Unknown City';
                $classification = $property['property_classification'] ?? 'N/A';
                
                echo sprintf(
                    "%d. %s (Classification: %s, Price: %s EGP, Location: %s)\n",
                    $index + 1,
                    $title,
                    $classification,
                    $price,
                    $city
                );
            }
        } else {
            echo "⚠️  No properties found in the specified price range.\n";
        }
    } else {
        echo "❌ API Error: " . ($data['message'] ?? 'Unknown error') . "\n";
        echo "Full Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    }
}

echo "\n🎯 Test Complete!\n";