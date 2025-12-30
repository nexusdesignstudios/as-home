<?php

// Simple test to check hotel_vat field in database
echo "=== Checking Hotel VAT Field in Database ===\n\n";

// Check if hotel_vat column exists in propertys table
try {
    $pdo = new PDO('mysql:host=localhost;dbname=as_home_dashboard', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SHOW COLUMNS FROM propertys LIKE 'hotel_vat'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✓ hotel_vat column exists in propertys table\n";
        echo "  Field: {$result['Field']}\n";
        echo "  Type: {$result['Type']}\n";
        echo "  Null: {$result['Null']}\n";
        echo "  Default: " . ($result['Default'] ?? 'NULL') . "\n";
    } else {
        echo "✗ hotel_vat column does NOT exist in propertys table\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Database connection error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";