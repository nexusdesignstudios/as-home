<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Models\VacationApartment;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Finding 'Amazing 01-Bedrroom in Dream Hotel 01'\n";
echo "========================================\n\n";

// Try multiple search patterns
$searchTerms = [
    'Amazing 01-Bedrroom in Dream Hotel 01',
    'Amazing 01-Bedrroom',
    'Dream Hotel 01',
    '01-Bedrroom',
    'Dream Hotel'
];

$foundProperties = [];

foreach ($searchTerms as $term) {
    $properties = Property::where('title', 'LIKE', "%{$term}%")
        ->get(['id', 'title', 'property_classification', 'status']);
    
    foreach ($properties as $prop) {
        if (!isset($foundProperties[$prop->id])) {
            $foundProperties[$prop->id] = $prop;
        }
    }
}

if (empty($foundProperties)) {
    echo "❌ Property not found with search terms.\n";
    echo "Listing all properties with 'Dream' or '01' in title:\n\n";
    
    $allProps = Property::where('title', 'LIKE', '%Dream%')
        ->orWhere('title', 'LIKE', '%01%')
        ->get(['id', 'title', 'property_classification']);
    
    foreach ($allProps as $p) {
        echo "ID: {$p->id}, Title: {$p->title}, Classification: {$p->property_classification}\n";
    }
    exit;
}

echo "Found " . count($foundProperties) . " property(ies):\n\n";

foreach ($foundProperties as $property) {
    echo "========================================\n";
    echo "Property Details\n";
    echo "========================================\n";
    echo "ID: {$property->id}\n";
    echo "Title: {$property->title}\n";
    echo "Classification: {$property->property_classification}\n";
    echo "Status: " . ($property->status ?? 'N/A') . "\n";
    
    $isVacationHome = $property->getRawOriginal('property_classification') == 4;
    echo "Is Vacation Home: " . ($isVacationHome ? 'Yes' : 'No') . "\n";
    echo "\n";
    
    if ($isVacationHome) {
        // Check property available_dates
        $propDates = $property->available_dates ?? [];
        echo "Property Available Dates: ";
        if (empty($propDates)) {
            echo "❌ NONE SET\n";
        } else {
            echo "✅ Found\n";
            if (is_string($propDates)) {
                $propDates = json_decode($propDates, true);
            }
            if (is_array($propDates)) {
                foreach ($propDates as $range) {
                    echo "   - {$range['from']} to {$range['to']} (type: {$range['type']})\n";
                }
            }
        }
        echo "\n";
        
        // Check apartments
        $apartments = VacationApartment::where('property_id', $property->id)->get();
        echo "Vacation Apartments: {$apartments->count()}\n";
        
        if ($apartments->isEmpty()) {
            echo "❌ NO APARTMENTS FOUND!\n";
            echo "   This property needs vacation apartments to be bookable.\n";
        } else {
            foreach ($apartments as $apt) {
                echo "\n   Apartment ID: {$apt->id}\n";
                echo "   Number: {$apt->apartment_number}\n";
                echo "   Status: " . ($apt->status ? 'Active' : 'Inactive') . "\n";
                echo "   Quantity: {$apt->quantity}\n";
                
                $aptDates = $apt->available_dates ?? [];
                echo "   Available Dates: ";
                if (empty($aptDates)) {
                    echo "❌ NONE SET\n";
                } else {
                    echo "✅ Found\n";
                    if (is_string($aptDates)) {
                        $aptDates = json_decode($aptDates, true);
                    }
                    if (is_array($aptDates)) {
                        foreach ($aptDates as $range) {
                            echo "      - {$range['from']} to {$range['to']} (type: {$range['type']})\n";
                        }
                    }
                }
            }
        }
    } else {
        $propDates = $property->available_dates ?? [];
        echo "Available Dates: ";
        if (empty($propDates)) {
            echo "❌ NONE SET\n";
        } else {
            echo "✅ Found\n";
        }
    }
    
    echo "\n";
    echo "========================================\n";
    echo "ISSUE DIAGNOSIS\n";
    echo "========================================\n";
    
    if ($isVacationHome) {
        $apartments = VacationApartment::where('property_id', $property->id)->get();
        $hasApartmentDates = false;
        foreach ($apartments as $apt) {
            if (!empty($apt->available_dates)) {
                $hasApartmentDates = true;
                break;
            }
        }
        
        if ($apartments->isEmpty()) {
            echo "❌ PROBLEM: No vacation apartments found!\n";
            echo "   SOLUTION: Create at least one vacation apartment for this property.\n";
        } elseif (!$hasApartmentDates && empty($propDates)) {
            echo "❌ PROBLEM: No available_dates set!\n";
            echo "   SOLUTION: Set available_dates either at:\n";
            echo "   1. Property level (property.available_dates), OR\n";
            echo "   2. Apartment level (vacation_apartments.available_dates)\n";
        } else {
            echo "✅ Property has apartments and available dates configured.\n";
            echo "   If calendar still shows no dates, check:\n";
            echo "   1. Are the dates in the future?\n";
            echo "   2. Are date types set to 'open' (not 'dead' or 'reserved')?\n";
            echo "   3. Check browser console for errors\n";
        }
    } else {
        if (empty($propDates)) {
            echo "❌ PROBLEM: No available_dates set!\n";
            echo "   SOLUTION: Set available_dates for this property.\n";
        } else {
            echo "✅ Available dates are configured.\n";
        }
    }
    
    echo "\n";
}

