<?php

// Simple database connectivity and table structure check
echo "=== Database Connectivity Check ===\n\n";

try {
    // Load Laravel config
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    $config = config('database.connections.mysql');
    
    echo "Database config:\n";
    echo "- Host: " . $config['host'] . "\n";
    echo "- Database: " . $config['database'] . "\n";
    echo "- Username: " . $config['username'] . "\n\n";
    
    // Test database connection
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']}", 
        $config['username'], 
        $config['password']
    );
    
    echo "✓ Database connection successful\n\n";
    
    // Check if required tables exist
    $tables = ['customers', 'reservations', 'propertys', 'categories', 'hotel_rooms'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "✓ Table '{$table}' exists with {$result['count']} records\n";
        } catch (Exception $e) {
            echo "✗ Table '{$table}' error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // Check specific data for customer 14
    echo "Checking data for customer 14:\n";
    
    // Check customer
    $stmt = $pdo->prepare("SELECT id, name, email FROM customers WHERE id = ?");
    $stmt->execute([14]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        echo "✓ Customer 14: {$customer['name']} ({$customer['email']})\n";
    } else {
        echo "✗ Customer 14 not found\n";
    }
    
    // Check properties
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM propertys WHERE added_by = ?");
    $stmt->execute([14]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Properties owned by customer 14: {$result['count']}\n";
    
    // Check reservations
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations WHERE customer_id = ?");
    $stmt->execute([14]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Reservations for customer 14: {$result['count']}\n";
    
    // Check reservations for customer's properties
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM reservations r 
        INNER JOIN propertys p ON r.property_id = p.id 
        WHERE p.added_by = ?
    ");
    $stmt->execute([14]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Reservations for customer 14's properties: {$result['count']}\n";
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== Check Complete ===\n";
