<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\Reservation;
use Carbon\Carbon;

$propertyId = 517;
$property = Property::find($propertyId);

if (!$property) {
    echo "Property $propertyId not found.\n";
    exit;
}

echo "Checking availability for Property: " . $property->name . " (ID: $propertyId)\n";

// Check existing bookings in the next 6 months
$startDate = Carbon::now();
$endDate = Carbon::now()->addMonths(6);

$bookings = Reservation::where('property_id', $propertyId)
    ->where(function ($query) use ($startDate, $endDate) {
        $query->whereBetween('check_in_date', [$startDate, $endDate])
              ->orWhereBetween('check_out_date', [$startDate, $endDate]);
    })
    ->get();

echo "Existing bookings:\n";
foreach ($bookings as $booking) {
    echo " - " . $booking->check_in_date->toDateString() . " to " . $booking->check_out_date->toDateString() . " (Status: " . $booking->status . ")\n";
}

// Find a free slot of 5 days
$checkDate = Carbon::now()->addDays(1);
$found = false;

while ($checkDate->lte($endDate)) {
    $potentialCheckIn = $checkDate->copy();
    $potentialCheckOut = $checkDate->copy()->addDays(5);
    
    $isBlocked = false;
    foreach ($bookings as $booking) {
        $bStart = $booking->check_in_date;
        $bEnd = $booking->check_out_date;
        
        // Check overlap
        if ($potentialCheckIn->lt($bEnd) && $potentialCheckOut->gt($bStart)) {
            $isBlocked = true;
            break;
        }
    }
    
    if (!$isBlocked) {
        echo "\nFound available slot:\n";
        echo "Check In: " . $potentialCheckIn->toDateString() . "\n";
        echo "Check Out: " . $potentialCheckOut->toDateString() . "\n";
        $found = true;
        break;
    }
    
    $checkDate->addDay();
}

if (!$found) {
    echo "\nNo 5-day slot found in the next 6 months.\n";
}
