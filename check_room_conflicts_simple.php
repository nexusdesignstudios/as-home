<?php

// Simple database connection check for room conflicts
$host = 'localhost';
$dbname = 'ashome';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CHECKING FLEXIBLE HOTEL ROOM RESERVATION CONFLICTS ===\n\n";
    
    // Check for reservations with the same room_id and overlapping dates
    $sql = "
        SELECT 
            reservable_id,
            check_in_date,
            check_out_date,
            status,
            payment_method,
            payment_status,
            COUNT(*) as reservation_count,
            GROUP_CONCAT(id) as reservation_ids
        FROM reservations 
        WHERE reservable_type = 'App\\\\Models\\\\HotelRoom'
        AND status NOT IN ('cancelled', 'rejected')
        GROUP BY reservable_id, check_in_date, check_out_date
        HAVING COUNT(*) > 1
        ORDER BY reservation_count DESC
    ";
    
    $stmt = $pdo->query($sql);
    $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($conflicts) . " sets of conflicting reservations (same room, same dates)\n\n";
    
    foreach ($conflicts as $conflict) {
        echo "=== CONFLICT ===\n";
        echo "Room ID: " . $conflict['reservable_id'] . "\n";
        echo "Check-in: " . $conflict['check_in_date'] . "\n";
        echo "Check-out: " . $conflict['check_out_date'] . "\n";
        echo "Reservation Count: " . $conflict['reservation_count'] . "\n";
        echo "Reservation IDs: " . $conflict['reservation_ids'] . "\n";
        echo "Status: " . $conflict['status'] . "\n";
        echo "Payment Method: " . $conflict['payment_method'] . "\n";
        echo "Payment Status: " . $conflict['payment_status'] . "\n";
        
        // Get room details
        $roomSql = "SELECT hr.id, hrt.name as room_type_name, hr.room_number 
                   FROM hotel_rooms hr 
                   LEFT JOIN hotel_room_types hrt ON hr.room_type_id = hrt.id 
                   WHERE hr.id = ?";
        $roomStmt = $pdo->prepare($roomSql);
        $roomStmt->execute([$conflict['reservable_id']]);
        $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($room) {
            echo "Room Type: " . ($room['room_type_name'] ?? 'Unknown') . "\n";
            echo "Room Number: " . ($room['room_number'] ?? 'N/A') . "\n";
        }
        
        echo "\n";
        
        // Get individual reservations for this conflict
        $individualSql = "
            SELECT id, customer_name, customer_email, status, payment_method, payment_status, created_at
            FROM reservations 
            WHERE reservable_id = ? 
            AND check_in_date = ? 
            AND check_out_date = ?
            AND status NOT IN ('cancelled', 'rejected')
            ORDER BY created_at ASC
        ";
        $individualStmt = $pdo->prepare($individualSql);
        $individualStmt->execute([$conflict['reservable_id'], $conflict['check_in_date'], $conflict['check_out_date']]);
        $individualReservations = $individualStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Individual Reservations:\n";
        foreach ($individualReservations as $res) {
            echo "  - ID: " . $res['id'] . ", Customer: " . $res['customer_name'] . 
                 ", Status: " . $res['status'] . ", Payment: " . $res['payment_method'] . 
                 ", Created: " . $res['created_at'] . "\n";
        }
        echo "\n";
    }
    
    // Check for flexible reservations specifically
    echo "\n=== FLEXIBLE RESERVATION CONFLICTS ===\n\n";
    
    $flexibleSql = "
        SELECT 
            reservable_id,
            check_in_date,
            check_out_date,
            status,
            payment_method,
            payment_status,
            COUNT(*) as reservation_count,
            GROUP_CONCAT(id) as reservation_ids
        FROM reservations 
        WHERE reservable_type = 'App\\\\Models\\\\HotelRoom'
        AND status NOT IN ('cancelled', 'rejected')
        AND payment_method = 'cash'
        GROUP BY reservable_id, check_in_date, check_out_date
        HAVING COUNT(*) > 1
        ORDER BY reservation_count DESC
    ";
    
    $flexibleStmt = $pdo->query($flexibleSql);
    $flexibleConflicts = $flexibleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($flexibleConflicts) . " sets of conflicting flexible reservations\n\n";
    
    foreach ($flexibleConflicts as $conflict) {
        echo "=== FLEXIBLE CONFLICT ===\n";
        echo "Room ID: " . $conflict['reservable_id'] . "\n";
        echo "Check-in: " . $conflict['check_in_date'] . "\n";
        echo "Check-out: " . $conflict['check_out_date'] . "\n";
        echo "Reservation Count: " . $conflict['reservation_count'] . "\n";
        echo "Reservation IDs: " . $conflict['reservation_ids'] . "\n";
        echo "Status: " . $conflict['status'] . "\n";
        echo "Payment Status: " . $conflict['payment_status'] . "\n";
        
        // Get room details
        $roomStmt->execute([$conflict['reservable_id']]);
        $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($room) {
            echo "Room Type: " . ($room['room_type_name'] ?? 'Unknown') . "\n";
            echo "Room Number: " . ($room['room_number'] ?? 'N/A') . "\n";
        }
        
        echo "\n";
        
        // Get individual flexible reservations for this conflict
        $individualStmt->execute([$conflict['reservable_id'], $conflict['check_in_date'], $conflict['check_out_date']]);
        $individualReservations = $individualStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Individual Flexible Reservations:\n";
        foreach ($individualReservations as $res) {
            echo "  - ID: " . $res['id'] . ", Customer: " . $res['customer_name'] . 
                 ", Status: " . $res['status'] . ", Created: " . $res['created_at'] . "\n";
        }
        echo "\n";
    }
    
    // Check recent flexible reservations in the last 30 days
    echo "=== RECENT FLEXIBLE RESERVATIONS (Last 30 Days) ===\n\n";
    
    $recentSql = "
        SELECT 
            reservable_id,
            check_in_date,
            check_out_date,
            customer_name,
            customer_email,
            status,
            payment_method,
            payment_status,
            created_at
        FROM reservations 
        WHERE reservable_type = 'App\\\\Models\\\\HotelRoom'
        AND payment_method = 'cash'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY created_at DESC
        LIMIT 20
    ";
    
    $recentStmt = $pdo->query($recentSql);
    $recentFlexible = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($recentFlexible) . " recent flexible reservations (last 30 days)\n\n";
    
    foreach ($recentFlexible as $res) {
        echo "ID: " . $res['reservable_id'] . ", Customer: " . $res['customer_name'] . 
             ", Dates: " . $res['check_in_date'] . " to " . $res['check_out_date'] . 
             ", Status: " . $res['status'] . ", Created: " . $res['created_at'] . "\n";
    }
    
    echo "\n=== ANALYSIS COMPLETE ===\n";
    echo "ISSUE FOUND: The reservation system does NOT check for existing reservations before creating new ones.\n";
    echo "This allows multiple flexible reservations for the same room and dates.\n";
    echo "The createReservation method in ReservationService directly creates reservations without availability checking.\n";
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database credentials.\n";
}
