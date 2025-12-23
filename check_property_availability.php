<?php

/**
 * Check property availability for "Amazing 01-Bedrroom in Dream Hotel 01"
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\VacationApartment;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Property Availability Check\n";
echo "========================================\n\n";

// Search for the property
$propertyTitle = "Amazing 01-Bedrroom in Dream Hotel 01";
echo "Searching for property: {$propertyTitle}\n\n";

$property = Property::where('title', 'LIKE', '%Amazing 01-Bedrroom%')
    ->orWhere('title', 'LIKE', '%Dream Hotel 01%')
    ->orWhere('title', 'LIKE', '%Amazing%')
    ->first();

if (!$property) {
    // Try broader search
    $property = Property::where('title', 'LIKE', '%01-Bedrroom%')
        ->orWhere('title', 'LIKE', '%Dream Hotel%')
        ->first();
}

if (!$property) {
    echo "❌ Property not found. Listing all properties with 'Amazing' or 'Dream' in title:\n";
    $allProperties = Property::where('title', 'LIKE', '%Amazing%')
        ->orWhere('title', 'LIKE', '%Dream%')
        ->get(['id', 'title', 'property_classification']);
    
    foreach ($allProperties as $prop) {
        echo "   - ID: {$prop->id}, Title: {$prop->title}, Classification: {$prop->property_classification}\n";
    }
    exit;
}

echo "✅ Property Found!\n";
echo "   ID: {$property->id}\n";
echo "   Title: {$property->title}\n";
echo "   Classification: {$property->property_classification} (" . ($property->property_classification == 4 ? 'Vacation Home' : 'Other') . ")\n";
echo "   Status: " . ($property->status ?? 'N/A') . "\n";
echo "\n";

// Check if it's a vacation home
$isVacationHome = $property->getRawOriginal('property_classification') == 4;

if ($isVacationHome) {
    echo "========================================\n";
    echo "Vacation Home Details\n";
    echo "========================================\n\n";
    
    // Check property-level available_dates
    echo "1. Property-Level Available Dates:\n";
    $propertyAvailableDates = $property->available_dates ?? [];
    if (empty($propertyAvailableDates)) {
        echo "   ❌ No available_dates set at property level\n";
    } else {
        echo "   ✅ Available dates found:\n";
        if (is_string($propertyAvailableDates)) {
            $propertyAvailableDates = json_decode($propertyAvailableDates, true);
        }
        foreach ($propertyAvailableDates as $dateRange) {
            $from = $dateRange['from'] ?? 'N/A';
            $to = $dateRange['to'] ?? 'N/A';
            $type = $dateRange['type'] ?? 'N/A';
            $price = $dateRange['price'] ?? 'N/A';
            echo "      - From: {$from}, To: {$to}, Type: {$type}, Price: {$price}\n";
        }
    }
    echo "\n";
    
    // Check availability_type
    echo "2. Property Availability Type:\n";
    $availabilityType = $property->availability_type ?? null;
    if ($availabilityType === 1) {
        echo "   ✅ Availability Type: 1 (Available Days - only these dates are available)\n";
    } elseif ($availabilityType === 2) {
        echo "   ✅ Availability Type: 2 (Busy Days - all dates except these are available)\n";
    } else {
        echo "   ⚠️  Availability Type: " . ($availabilityType ?? 'NULL') . " (Not set)\n";
    }
    echo "\n";
    
    // Check vacation apartments
    echo "3. Vacation Apartments:\n";
    $apartments = VacationApartment::where('property_id', $property->id)
        ->get(['id', 'apartment_number', 'status', 'quantity', 'availability_type', 'available_dates']);
    
    if ($apartments->isEmpty()) {
        echo "   ❌ No vacation apartments found for this property\n";
    } else {
        echo "   ✅ Found {$apartments->count()} apartment(s):\n\n";
        foreach ($apartments as $apt) {
            echo "   Apartment ID: {$apt->id}\n";
            echo "   Number: {$apt->apartment_number}\n";
            echo "   Status: " . ($apt->status ? 'Active' : 'Inactive') . "\n";
            echo "   Quantity: {$apt->quantity} unit(s)\n";
            echo "   Availability Type: " . ($apt->availability_type ?? 'NULL') . "\n";
            
            // Check apartment-level available_dates
            $aptAvailableDates = $apt->available_dates ?? [];
            if (empty($aptAvailableDates)) {
                echo "   Available Dates: ❌ None set\n";
            } else {
                echo "   Available Dates: ✅ Found\n";
                if (is_string($aptAvailableDates)) {
                    $aptAvailableDates = json_decode($aptAvailableDates, true);
                }
                if (is_array($aptAvailableDates)) {
                    foreach ($aptAvailableDates as $dateRange) {
                        $from = $dateRange['from'] ?? 'N/A';
                        $to = $dateRange['to'] ?? 'N/A';
                        $type = $dateRange['type'] ?? 'N/A';
                        $price = $dateRange['price'] ?? 'N/A';
                        echo "      - From: {$from}, To: {$to}, Type: {$type}, Price: {$price}\n";
                    }
                }
            }
            echo "\n";
        }
    }
} else {
    echo "========================================\n";
    echo "Property Details (Not a Vacation Home)\n";
    echo "========================================\n\n";
    
    echo "1. Available Dates:\n";
    $availableDates = $property->available_dates ?? [];
    if (empty($availableDates)) {
        echo "   ❌ No available_dates set\n";
    } else {
        echo "   ✅ Available dates found:\n";
        if (is_string($availableDates)) {
            $availableDates = json_decode($availableDates, true);
        }
        foreach ($availableDates as $dateRange) {
            $from = $dateRange['from'] ?? 'N/A';
            $to = $dateRange['to'] ?? 'N/A';
            $type = $dateRange['type'] ?? 'N/A';
            $price = $dateRange['price'] ?? 'N/A';
            echo "      - From: {$from}, To: {$to}, Type: {$type}, Price: {$price}\n";
        }
    }
    echo "\n";
    
    echo "2. Availability Type:\n";
    $availabilityType = $property->availability_type ?? null;
    echo "   Availability Type: " . ($availabilityType ?? 'NULL') . "\n";
    echo "\n";
}

// Check reservations
echo "========================================\n";
echo "Reservations Check\n";
echo "========================================\n\n";
$reservations = DB::table('reservations')
    ->where('property_id', $property->id)
    ->where('status', 'confirmed')
    ->get(['id', 'check_in_date', 'check_out_date', 'status', 'apartment_id']);

echo "Confirmed reservations: {$reservations->count()}\n";
foreach ($reservations as $res) {
    echo "   - Reservation ID: {$res->id}, Check-in: {$res->check_in_date}, Check-out: {$res->check_out_date}";
    if ($res->apartment_id) {
        echo ", Apartment ID: {$res->apartment_id}";
    }
    echo "\n";
}
echo "\n";

// Summary
echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Property ID: {$property->id}\n";
echo "Property Title: {$property->title}\n";
echo "Is Vacation Home: " . ($isVacationHome ? 'Yes' : 'No') . "\n";

if ($isVacationHome) {
    $apartments = VacationApartment::where('property_id', $property->id)->get();
    echo "Vacation Apartments: {$apartments->count()}\n";
    
    $hasAvailableDates = false;
    foreach ($apartments as $apt) {
        if (!empty($apt->available_dates)) {
            $hasAvailableDates = true;
            break;
        }
    }
    
    if (!$hasAvailableDates && empty($property->available_dates)) {
        echo "❌ ISSUE: No available_dates set at property or apartment level!\n";
        echo "   This is why the calendar shows no available days.\n";
        echo "\n";
        echo "SOLUTION:\n";
        echo "1. Set available_dates at the property level, OR\n";
        echo "2. Set available_dates for each vacation apartment\n";
    } else {
        echo "✅ Available dates are configured\n";
    }
} else {
    if (empty($property->available_dates)) {
        echo "❌ ISSUE: No available_dates set!\n";
        echo "   This is why the calendar shows no available days.\n";
    } else {
        echo "✅ Available dates are configured\n";
    }
}

