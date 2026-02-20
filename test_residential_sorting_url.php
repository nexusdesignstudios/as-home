<?php

function fetch_properties($offset) {
    $url = "http://127.0.0.1:8001/api/get-property-list?property_classification=1&property_type=0&is_commercial=0&sort_by=price_desc&limit=8&offset=" . $offset;
    echo "Fetching: $url\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    
    if ($response === false) {
        echo "Curl error: " . curl_error($ch) . "\n";
        return [];
    }
    curl_close($ch);
    
    $data = json_decode($response, true);
    if (isset($data['data'])) {
        return $data['data'];
    } else {
        echo "No data found or error: " . substr($response, 0, 200) . "\n";
        return [];
    }
}

echo "Testing Residential Sorting URL Scenario (Price High to Low)\n";
echo "=========================================================\n";

// Page 1
echo "\n--- Page 1 (Offset 0) ---\n";
$page1 = fetch_properties(0);
$last_price = PHP_FLOAT_MAX;
$page1_last_price = 0;

foreach ($page1 as $p) {
    $price = (float)$p['price'];
    echo "ID: " . $p['id'] . " | Price: " . number_format($price, 2) . "\n";
    
    if ($price > $last_price) {
        echo "ERROR: Price increase detected! " . number_format($price, 2) . " > " . number_format($last_price, 2) . "\n";
    }
    $last_price = $price;
    $page1_last_price = $price;
}

// Page 2
echo "\n--- Page 2 (Offset 8) ---\n";
$page2 = fetch_properties(8);
$last_price = $page1_last_price; // Start check from last price of page 1

foreach ($page2 as $p) {
    $price = (float)$p['price'];
    echo "ID: " . $p['id'] . " | Price: " . number_format($price, 2) . "\n";
    
    if ($price > $last_price) {
        echo "ERROR: Price increase detected between pages! " . number_format($price, 2) . " > " . number_format($last_price, 2) . "\n";
    }
    $last_price = $price;
}

echo "\nTest Complete.\n";
