<?php

$url = "http://127.0.0.1:8001/api/get-property-list?property_classification=1&property_type=0&sort_by=price_desc&limit=8&offset=0";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
if ($response === false) {
    echo "Curl error: " . curl_error($ch);
}
curl_close($ch);

echo "Page 1 Response:\n";
$data = json_decode($response, true);
if (isset($data['data'])) {
    foreach ($data['data'] as $property) {
        echo "ID: " . $property['id'] . " - Price: " . $property['price'] . "\n";
    }
} else {
    echo "No data found or error: " . substr($response, 0, 200) . "\n";
}

$url2 = "http://127.0.0.1:8001/api/get-property-list?property_classification=1&property_type=0&sort_by=price_desc&limit=8&offset=8";
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $url2);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
$response2 = curl_exec($ch2);
curl_close($ch2);

echo "\nPage 2 Response:\n";
$data2 = json_decode($response2, true);
if (isset($data2['data'])) {
    foreach ($data2['data'] as $property) {
        echo "ID: " . $property['id'] . " - Price: " . $property['price'] . "\n";
    }
} else {
    echo "No data found or error: " . substr($response2, 0, 200) . "\n";
}
