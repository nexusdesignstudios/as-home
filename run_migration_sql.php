<?php

// Simple script to run the migration SQL manually
// This will add the missing columns to the reservations table

// Database configuration - UPDATE THESE VALUES
$host = 'localhost';
$dbname = 'your_database_name'; // Replace with your actual database name
$username = 'your_username'; // Replace with your actual username  
$password = 'your_password'; // Replace with your actual password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n";
    
    // Check if columns already exist
    $checkColumns = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'customer_name'");
    if ($checkColumns->rowCount() > 0) {
        echo "Columns already exist. Migration not needed.\n";
        exit;
    }
    
    // Add the missing columns
    $sql = "
        ALTER TABLE reservations 
        ADD COLUMN customer_name VARCHAR(255) NULL AFTER customer_id,
        ADD COLUMN customer_phone VARCHAR(255) NULL AFTER customer_name,
        ADD COLUMN customer_email VARCHAR(255) NULL AFTER customer_phone,
        ADD COLUMN review_url VARCHAR(255) NULL AFTER transaction_id,
        ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER review_url,
        ADD COLUMN requires_approval BOOLEAN DEFAULT FALSE AFTER approval_status,
        ADD COLUMN booking_type VARCHAR(255) NULL AFTER requires_approval,
        ADD COLUMN property_details JSON NULL AFTER booking_type,
        ADD COLUMN reservable_data JSON NULL AFTER property_details
    ";
    
    $pdo->exec($sql);
    echo "Migration completed successfully! All columns have been added to the reservations table.\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
