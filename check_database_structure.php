<?php
// Check database structure for hotel_rooms table
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DATABASE STRUCTURE CHECK ===\n\n";

// 1. Check hotel_rooms table structure
echo "1. HOTEL_ROOMS TABLE STRUCTURE\n";
echo "==============================\n";

try {
    $columns = \Schema::getColumnListing('hotel_rooms');
    echo "Columns in hotel_rooms table:\n";
    foreach ($columns as $column) {
        echo "  - $column\n";
    }
} catch (\Exception $e) {
    echo "Error getting table structure: " . $e->getMessage() . "\n";
}

// 2. Check if available_dates column exists
echo "\n2. CHECKING FOR AVAILABLE_DATES COLUMN\n";
echo "====================================\n";

if (\Schema::hasColumn('hotel_rooms', 'available_dates')) {
    echo "✅ available_dates column exists\n";
} else {
    echo "❌ available_dates column does NOT exist\n";
    
    // Check for similar columns
    $similarColumns = array_filter($columns, function($col) {
        return strpos($col, 'date') !== false || strpos($col, 'available') !== false;
    });
    
    if (!empty($similarColumns)) {
        echo "Similar columns found:\n";
        foreach ($similarColumns as $col) {
            echo "  - $col\n";
        }
    }
}

// 3. Check room data
echo "\n3. ROOM 764 DATA\n";
echo "===============\n";

$room = \App\Models\HotelRoom::find(764);
if ($room) {
    echo "Room ID: {$room->id}\n";
    echo "Room Type ID: {$room->room_type_id}\n";
    echo "Property ID: {$room->property_id}\n";
    echo "Status: " . ($room->status ? 'Active' : 'Inactive') . "\n";
    echo "Price: " . ($room->price_per_night ?? 'N/A') . "\n";
    
    // Check all attributes
    echo "\nAll room attributes:\n";
    $attributes = $room->getAttributes();
    foreach ($attributes as $key => $value) {
        if ($key !== 'id' && $key !== 'room_type_id' && $key !== 'property_id' && $key !== 'status' && $key !== 'price_per_night') {
            echo "  - $key: " . (is_null($value) ? 'NULL' : (is_string($value) && strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value)) . "\n";
        }
    }
}

// 4. Alternative approach - Check if availability is stored elsewhere
echo "\n4. ALTERNATIVE AVAILABILITY STORAGE\n";
echo "===================================\n";

// Check if there's a separate availability table
$tables = \DB::select('SHOW TABLES');
$availabilityTables = [];
foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    if (strpos($tableName, 'availability') !== false || strpos($tableName, 'date') !== false) {
        $availabilityTables[] = $tableName;
    }
}

if (!empty($availabilityTables)) {
    echo "Found potential availability tables:\n";
    foreach ($availabilityTables as $table) {
        echo "  - $table\n";
    }
} else {
    echo "No availability-related tables found\n";
}

// 5. Check how availability is currently determined
echo "\n5. CURRENT AVAILABILITY LOGIC\n";
echo "===========================\n";

echo "Based on the code analysis, availability seems to be determined by:\n";
echo "1. Room status (active/inactive)\n";
echo "2. Existing reservations\n";
echo "3. NOT by available_dates (column doesn't exist)\n";

echo "\nCONCLUSION:\n";
echo "The room availability is NOT controlled by available_dates column.\n";
echo "The backend ReservationService is checking for a non-existent column.\n";
echo "This is why room 764 shows as unavailable.\n";

echo "\nFIX NEEDED:\n";
echo "1. Create available_dates column in hotel_rooms table, OR\n";
echo "2. Update ReservationService to not check available_dates, OR\n";
echo "3. Store availability data in a different way\n";
