<?php

// Simple test script to debug the reservations API
echo "=== Testing Property Owner Reservations API ===\n\n";

// Test 1: Check if customer exists
echo "1. Testing if customer ID 14 exists...\n";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=your_database', 'username', 'password');
    $stmt = $pdo->prepare("SELECT id, name, email FROM customers WHERE id = ?");
    $stmt->execute([14]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        echo "✓ Customer found: " . json_encode($customer) . "\n";
    } else {
        echo "✗ Customer ID 14 not found\n";
    }
} catch (Exception $e) {
    echo "✗ Database connection error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Check if reservations table exists and has data
echo "2. Testing reservations table...\n";
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Reservations table exists, total records: " . $result['count'] . "\n";
    
    // Check if there are reservations for customer 14
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations WHERE customer_id = ?");
    $stmt->execute([14]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Reservations for customer 14: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "✗ Reservations table error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Check if properties table exists
echo "3. Testing properties table...\n";
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM propertys");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Properties table exists, total records: " . $result['count'] . "\n";
    
    // Check if customer 14 has properties
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM propertys WHERE added_by = ?");
    $stmt->execute([14]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Properties owned by customer 14: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "✗ Properties table error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Check the actual query that's failing
echo "4. Testing the problematic query...\n";
try {
    $sql = "SELECT r.*, c.name as customer_name, c.email as customer_email, c.mobile as customer_phone 
            FROM reservations r 
            LEFT JOIN customers c ON r.customer_id = c.id 
            WHERE r.customer_id = ? 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([14]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reservation) {
        echo "✓ Query works, sample reservation: " . json_encode($reservation) . "\n";
    } else {
        echo "✗ No reservations found for customer 14\n";
    }
    
} catch (Exception $e) {
    echo "✗ Query error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
