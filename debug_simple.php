<?php

// Simple debug script to test the problematic query
echo "=== Debugging Property Owner Reservations Query ===\n\n";

// Test the specific query that's causing issues
echo "Testing the whereHas query...\n";

try {
    // Simulate the problematic query
    $customerId = 14;
    
    // First, let's check if customer 14 has any properties
    $pdo = new PDO('mysql:host=localhost;dbname=your_database', 'username', 'password');
    
    echo "1. Checking if customer 14 has properties...\n";
    $stmt = $pdo->prepare("SELECT id, title FROM propertys WHERE added_by = ? LIMIT 5");
    $stmt->execute([$customerId]);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($properties) {
        echo "✓ Customer 14 has " . count($properties) . " properties:\n";
        foreach ($properties as $prop) {
            echo "  - Property ID: {$prop['id']}, Title: {$prop['title']}\n";
        }
    } else {
        echo "✗ Customer 14 has no properties\n";
    }
    
    echo "\n2. Checking reservations for customer 14's properties...\n";
    if ($properties) {
        $propertyIds = array_column($properties, 'id');
        $placeholders = str_repeat('?,', count($propertyIds) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations WHERE property_id IN ($placeholders)");
        $stmt->execute($propertyIds);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Reservations for customer 14's properties: " . $result['count'] . "\n";
    }
    
    echo "\n3. Testing the exact whereHas query...\n";
    // This is the problematic part - let's test it step by step
    $stmt = $pdo->prepare("
        SELECT r.* 
        FROM reservations r 
        WHERE EXISTS (
            SELECT 1 FROM propertys p 
            WHERE p.id = r.property_id 
            AND p.added_by = ?
        )
        LIMIT 5
    ");
    $stmt->execute([$customerId]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($reservations) {
        echo "✓ Found " . count($reservations) . " reservations using whereHas logic:\n";
        foreach ($reservations as $res) {
            echo "  - Reservation ID: {$res['id']}, Property ID: {$res['property_id']}\n";
        }
    } else {
        echo "✗ No reservations found using whereHas logic\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== Debug Complete ===\n";
