<?php
// Debug why areDatesAvailable returns NOT AVAILABLE
require_once 'vendor/autoload.php';

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUG AVAILABILITY CHECK ===\n\n";

$roomId = 764;
$checkInDate = '2026-01-13';
$checkOutDate = '2026-01-14';

echo "Room ID: $roomId\n";
echo "Check-in: $checkInDate\n";
echo "Check-out: $checkOutDate\n\n";

// 1. Check room status
echo "1. ROOM STATUS\n";
echo "============\n";
$room = \App\Models\HotelRoom::find($roomId);
echo "Room Status: " . ($room->status ? 'Active' : 'Inactive') . "\n";
echo "Room Exists: " . ($room ? 'Yes' : 'No') . "\n\n";

// 2. Check datesOverlap directly
echo "2. datesOverlap METHOD\n";
echo "====================\n";
$hasOverlap = \App\Models\Reservation::datesOverlap($checkInDate, $checkOutDate, $roomId, 'App\\Models\\HotelRoom');
echo "datesOverlap result: " . ($hasOverlap ? "TRUE (has overlap)" : "FALSE (no overlap)") . "\n\n";

// 3. Check ReservationService with debug
echo "3. RESERVATIONSERVICE DEBUG\n";
echo "========================\n";

// Enable error logging
\Log::info('=== DEBUG AVAILABILITY CHECK ===');
\Log::info("Checking room $roomId for $checkInDate to $checkOutDate");

$reservationService = app(\App\Services\ReservationService::class);

// Check the internal logic
$modelType = 'App\\Models\\HotelRoom';
$modelId = $roomId;

echo "Model Type: $modelType\n";
echo "Model ID: $modelId\n";

// Get the model instance
$model = $reservationService->getModelInstance($modelType, $modelId);
echo "Model Instance: " . ($model ? 'Found' : 'Not Found') . "\n";

if ($model) {
    echo "Model ID matches: " . ($model->id == $modelId ? 'Yes' : 'No') . "\n";
    echo "Model Status: " . ($model->status ? 'Active' : 'Inactive') . "\n";
}

// Check if it's a hotel room
if ($modelType === 'App\\Models\\HotelRoom') {
    echo "\nHOTEL ROOM SPECIFIC CHECKS:\n";
    echo "----------------------------\n";
    
    // Check existing reservations
    $existingReservations = \App\Models\Reservation::where('reservable_id', $modelId)
        ->where('reservable_type', 'App\\Models\\HotelRoom')
        ->whereIn('status', ['confirmed', 'approved', 'pending'])
        ->where(function($q) use ($checkInDate, $checkOutDate) {
            $q->where('check_in_date', '>=', $checkInDate)
                ->where('check_in_date', '<', $checkOutDate);
        })->orWhere(function($q) use ($checkInDate, $checkOutDate) {
            $q->where('check_out_date', '>', $checkInDate)
                ->where('check_out_date', '<', $checkOutDate);
        })->orWhere(function($q) use ($checkInDate, $checkOutDate) {
            $q->where('check_in_date', '<=', $checkInDate)
                ->where('check_out_date', '>', $checkOutDate);
        })->get();
    
    echo "Existing reservations count: " . $existingReservations->count() . "\n";
    
    foreach ($existingReservations as $res) {
        echo "  - Reservation {$res->id}: {$res->status} ({$res->check_in_date} to {$res->check_out_date})\n";
    }
    
    // Check datesOverlap again
    $hasOverlap = \App\Models\Reservation::datesOverlap($checkInDate, $checkOutDate, $modelId, $modelType);
    echo "datesOverlap result: " . ($hasOverlap ? "TRUE - BLOCKING" : "FALSE - AVAILABLE") . "\n";
    
    if ($hasOverlap) {
        echo "❌ Room is blocked due to existing reservation(s)\n";
    } else {
        echo "✅ Room should be available\n";
    }
}

// 4. Check the actual areDatesAvailable result
echo "\n4. FINAL AVAILABILITY CHECK\n";
echo "========================\n";

$isAvailable = $reservationService->areDatesAvailable($modelType, $modelId, $checkInDate, $checkOutDate);
echo "areDatesAvailable result: " . ($isAvailable ? "AVAILABLE" : "NOT AVAILABLE") . "\n";

// 5. Check Laravel logs for any errors
echo "\n5. RECENT LARAVEL LOGS\n";
echo "====================\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $lastLines = array_slice($lines, -20);
    
    echo "Last 20 log entries:\n";
    foreach ($lastLines as $line) {
        if (!empty(trim($line))) {
            echo "  " . $line . "\n";
        }
    }
} else {
    echo "No log file found\n";
}

// 6. Check for any database connection issues
echo "\n6. DATABASE CONNECTION\n";
echo "====================\n";

try {
    $dbConnection = \DB::connection();
    echo "Database connection: OK\n";
    echo "Database name: " . $dbConnection->getDatabaseName() . "\n";
} catch (\Exception $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
}

echo "\nDebug completed.\n";
