<?php

// Usage:
// php list_property_reservations.php "Amazing 01-Bedrroom in Dream Hotel 01"
//
// Lists reservations for properties matching the given title (partial match),
// including apartment and quantity information. By default, shows all statuses.
// Optional flags:
//   --only-confirmed    Show only confirmed/approved/completed
//   --ids=ID1,ID2       Show only specific reservation IDs (comma-separated)
//
// Examples:
//   php list_property_reservations.php "Amazing 01-Bedrroom" --only-confirmed
//   php list_property_reservations.php "Amazing 01-Bedrroom" --ids=840,841

use App\Models\Property;
use App\Models\Reservation;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$title = null;
$onlyConfirmed = false;
$idsFilter = [];

foreach ($argv as $idx => $arg) {
    if ($idx === 0) {
        continue;
    }
    if (strpos($arg, '--only-confirmed') === 0) {
        $onlyConfirmed = true;
    } elseif (strpos($arg, '--ids=') === 0) {
        $list = substr($arg, strlen('--ids='));
        $idsFilter = array_filter(array_map('trim', explode(',', $list)));
    } elseif ($title === null) {
        $title = $arg;
    }
}

if (!$title && empty($idsFilter)) {
    echo "Please pass a property title (partial match) as the first argument, or use --ids=ID1,ID2\n";
    exit(1);
}

$properties = [];
if (!empty($idsFilter)) {
    // If specific IDs passed, we may not need properties; but still fetch by title if provided
    if ($title) {
        $properties = Property::where('title', 'LIKE', '%' . $title . '%')->get(['id', 'title']);
    }
} else {
    $properties = Property::where('title', 'LIKE', '%' . $title . '%')->get(['id', 'title']);
}
if ($properties->isEmpty()) {
    echo "No properties found for: {$title}\n";
    exit(0);
}

foreach ($properties as $property) {
    echo "Property: {$property->title} (ID: {$property->id})\n";

    $query = Reservation::query();
    if ($property) {
        $query->where('property_id', $property->id);
    }
    if (!empty($idsFilter)) {
        $query->whereIn('id', $idsFilter);
    }
    if ($onlyConfirmed) {
        $query->whereIn('status', ['confirmed', 'approved', 'completed']);
    }

    $reservations = $query->orderBy('check_in_date')
        ->get([
            'id',
            'status',
            'payment_status',
            'reservable_type',
            'reservable_id',
            'apartment_id',
            'apartment_quantity',
            'check_in_date',
            'check_out_date',
            'special_requests',
            'customer_name',
            'customer_email',
            'property_id',
        ]);

    if ($reservations->isEmpty()) {
        echo "  No confirmed/approved/completed reservations found.\n";
        continue;
    }

    foreach ($reservations as $res) {
        $qty = $res->apartment_quantity ?? 1;

        // Fallback parse for older data stored in special_requests
        if (!$res->apartment_quantity && $res->special_requests) {
            if (preg_match('/Quantity:\s*(\d+)/i', $res->special_requests, $m)) {
                $qty = (int) $m[1];
            }
        }

        $aptId = $res->apartment_id;
        if (!$aptId && $res->special_requests) {
            if (preg_match('/Apartment ID:\s*(\d+)/i', $res->special_requests, $m)) {
                $aptId = (int) $m[1];
            }
        }

        echo "  - Res #{$res->id} | {$res->status}/{$res->payment_status} | ";
        echo "Check-in: {$res->check_in_date} -> Check-out: {$res->check_out_date} | ";
        echo "Apartment ID: " . ($aptId ?? 'N/A') . " | Qty: {$qty} | ";
        echo "Customer: " . ($res->customer_name ?? $res->customer_email ?? 'N/A') . "\n";
    }
    echo "\n";
}

