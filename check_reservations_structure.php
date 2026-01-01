<?php
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING RESERVATIONS TABLE STRUCTURE ===\n\n";

// Get table schema
$columns = DB::select('DESCRIBE reservations');

echo "📊 Reservations table columns:\n";
foreach ($columns as $column) {
    echo "- {$column->Field} ({$column->Type})\n";
}

echo "\n=== SAMPLE RESERVATION DATA ===\n";
$sampleReservations = DB::table('reservations')
    ->where('property_id', 351)
    ->limit(3)
    ->get();

foreach ($sampleReservations as $reservation) {
    echo "🔒 Reservation ID: {$reservation->id}\n";
    echo "🛏️ Room Info: " . json_encode($reservation->room_ids ?? $reservation->room_id ?? 'N/A') . "\n";
    echo "📅 Check-in: {$reservation->check_in_date}\n";
    echo "📅 Check-out: {$reservation->check_out_date}\n";
    echo "🔖 Status: {$reservation->status}\n";
    echo "---\n";
}