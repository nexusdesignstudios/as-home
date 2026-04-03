<?php
// Updated script to check March 2026 reservations without shell_exec
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Property;
use App\Models\Reservation;
use Carbon\Carbon;

$startDate = Carbon::parse('2026-03-01')->startOfMonth();
$endDate = Carbon::parse('2026-03-01')->endOfMonth();

echo "Checking reservations between " . $startDate->toDateString() . " and " . $endDate->toDateString() . "\n";

// Get hotel properties
$hotelPropertyIds = Property::where('property_classification', 5)->pluck('id');
echo "Found " . $hotelPropertyIds->count() . " hotel properties.\n";

// Get reservations
$reservations = Reservation::where(function($query) use ($hotelPropertyIds) {
    $query->where('reservable_type', 'App\Models\Property')
          ->whereIn('reservable_id', $hotelPropertyIds);
})
->where('status', 'confirmed')
->whereIn('payment_status', ['paid', 'cash'])
->whereBetween('check_out_date', [$startDate, $endDate])
->with(['reservable', 'customer'])
->get();

echo "Found " . $reservations->count() . " confirmed hotel reservations in March 2026.\n";

foreach ($reservations as $res) {
    echo "- Res ID: " . $res->id . ", Property: [" . ($res->reservable->id ?? 'N/A') . "] " . ($res->reservable->title ?? 'N/A') . ", Method: " . ($res->payment_method ?? 'cash') . ", Amount: " . $res->total_price . ", Checkout: " . $res->check_out_date->toDateString() . "\n";
}

// Check for log entries
$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    echo "Checking last few lines of log for tax invoice related entries...\n";
    $lines = file($logPath);
    $lastLines = array_slice($lines, -500);
    $matches = array_filter($lastLines, function($line) {
        return stripos($line, 'tax invoice') !== false;
    });
    echo "Found " . count($matches) . " related log entries in last 500 lines.\n";
    foreach ($matches as $match) {
        echo trim($match) . "\n";
    }
}
