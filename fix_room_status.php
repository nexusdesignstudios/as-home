<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Starting Room Status Fix Script...\n";

try {
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->handle(Illuminate\Http\Request::capture());

    $roomId = 844;
    echo "Inspecting Room ID: $roomId\n";

    $room = \App\Models\HotelRoom::find($roomId);
    
    if (!$room) {
        die("❌ Room not found in database!\n");
    }

    echo "Current Status (Raw): '" . $room->getRawOriginal('status') . "'\n";
    echo "Current Status (Cast): '" . $room->status . "'\n";
    
    // Check type of status column
    $connection = \Illuminate\Support\Facades\DB::connection();
    $columns = $connection->select("SHOW COLUMNS FROM hotel_rooms WHERE Field = 'status'");
    echo "Column Type: " . $columns[0]->Type . "\n";

    // Fix it
    echo "Attempting to set status to 1...\n";
    
    // Direct DB update to bypass model mutators potentially causing issues
    \Illuminate\Support\Facades\DB::table('hotel_rooms')
        ->where('id', $roomId)
        ->update(['status' => 1]);
        
    $room->refresh();
    echo "New Status: " . $room->status . "\n";
    
    if ($room->status == 1) {
        echo "✅ Room status successfully updated to Active (1).\n";
    } else {
        echo "❌ Failed to update room status.\n";
    }

} catch (\Throwable $e) {
    echo "\n❌ CRITICAL ERROR:\n";
    echo $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
