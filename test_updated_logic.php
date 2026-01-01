<?php

// Test the actual admin controller logic with the new hotel_room case
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;

echo "=== Testing Admin Controller Logic Fix ===\n\n";

// Simulate the isFlexibleReservation method
function isFlexibleReservation($reservation) {
    $paymentMethod = $reservation->payment_method ?? 'cash';
    return !($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment);
}

$testIds = [893, 894, 895];

foreach ($testIds as $id) {
    echo "Reservation {$id}:\n";
    
    $reservation = Reservation::with([
        'customer:id,name,email,mobile',
        'reservable',
        'reservable.property:id,title,property_classification',
        'reservable.roomType:id,name'
    ])->find($id);
    
    if (!$reservation) {
        echo "  Not found!\n\n";
        continue;
    }
    
    // Simulate the admin controller logic exactly
    $propertyName = 'N/A';
    $propertyType = 'N/A';
    
    if ($reservation->reservable_type === 'App\Models\Property') {
        $reservable = $reservation->reservable;
        if (!$reservable) {
            $propertyName = 'Property (Missing)';
            $propertyType = 'Orphaned';
        } else {
            $propertyName = $reservable->title ?? 'N/A';
            
            // Check property classification
            try {
                $propertyClassification = $reservable->getRawOriginal('property_classification');
            } catch (\Exception $e) {
                $propertyClassification = $reservable->property_classification ?? null;
                if (is_string($propertyClassification)) {
                    $propertyClassification = match($propertyClassification) {
                        'vacation_homes' => 4,
                        'hotel_booking' => 5,
                        'sell_rent' => 1,
                        'commercial' => 2,
                        'new_project' => 3,
                        default => null
                    };
                }
            }
            
            if ($propertyClassification == 4) {
                $propertyType = 'Vacation Home';
            } elseif ($propertyClassification == 5) {
                $propertyType = 'Hotel Property';
            } else {
                $propertyType = 'Property';
            }
        }
    } elseif ($reservation->reservable_type === 'App\Models\HotelRoom') {
        $reservable = $reservation->reservable;
        if (!$reservable) {
            $propertyName = 'Hotel Room (Missing)';
            $propertyType = 'Orphaned';
        } else {
            // Load property relationship if not already loaded
            if (!$reservable->relationLoaded('property')) {
                $reservable->load('property:id,title');
            }
            
            $propertyName = $reservable->property->title ?? 'N/A';
            $propertyType = 'Hotel Room';
            
            // Load roomType if not already loaded
            if (!$reservable->relationLoaded('roomType') && $reservable->room_type_id) {
                $reservable->load('roomType:id,name');
            }
            
            if ($reservable->roomType) {
                $propertyName .= ' - ' . $reservable->roomType->name;
            }
            
            // Determine refund policy based on payment method
            if (isFlexibleReservation($reservation)) {
                $propertyType .= ' (Flexible)';
            } else {
                $propertyType .= ' (Non-Refundable)';
            }
        }
    } elseif ($reservation->reservable_type === 'hotel_room') {
        echo "  Applying NEW hotel_room logic...\n";
        // Handle case where resolvable_type is 'hotel_room' (lowercase)
        if (!$reservation->reservable) {
            // Try to load the hotel room manually since the polymorphic relationship might not work
            $hotelRoom = \App\Models\HotelRoom::find($reservation->reservable_id);
            if ($hotelRoom) {
                echo "  Found hotel room {$hotelRoom->id}\n";
                // Load property relationship
                $property = \App\Models\Property::find($hotelRoom->property_id);
                if ($property) {
                    echo "  Found property {$property->id}\n";
                    $propertyName = $property->title ?? 'N/A';
                    $propertyType = 'Hotel Room';
                    
                    // Load room type if available
                    if ($hotelRoom->room_type_id) {
                        $roomType = \App\Models\RoomType::find($hotelRoom->room_type_id);
                        if ($roomType) {
                            $propertyName .= ' - ' . $roomType->name;
                        }
                    }
                    
                    // Determine refund policy based on payment method
                    if (isFlexibleReservation($reservation)) {
                        $propertyType .= ' (Flexible)';
                    } else {
                        $propertyType .= ' (Non-Refundable)';
                    }
                } else {
                    $propertyName = 'Property (Missing)';
                    $propertyType = 'Orphaned';
                }
            } else {
                $propertyName = 'Hotel Room (Missing)';
                $propertyType = 'Orphaned';
            }
        } else {
            echo "  Resolvable is loaded, using standard logic...\n";
            // Fallback to standard HotelRoom logic if resolvable is loaded
            $reservable = $reservation->reservable;
            // Load property relationship if not already loaded
            if (!$reservable->relationLoaded('property')) {
                $reservable->load('property:id,title');
            }
            
            $propertyName = $reservable->property->title ?? 'N/A';
            $propertyType = 'Hotel Room';
            
            // Load roomType if not already loaded
            if (!$reservable->relationLoaded('roomType') && $reservable->room_type_id) {
                $reservable->load('roomType:id,name');
            }
            
            if ($reservable->roomType) {
                $propertyName .= ' - ' . $reservable->roomType->name;
            }
            
            // Determine refund policy based on payment method
            if (isFlexibleReservation($reservation)) {
                $propertyType .= ' (Flexible)';
            } else {
                $propertyType .= ' (Non-Refundable)';
            }
        }
    }
    
    // NEW LOGIC: Handle property-level reservations (when resolvable_type is empty but property_id is set)
    if (empty($reservation->reservable_type) && $reservation->property_id) {
        echo "  Applying NEW property-level logic...\n";
        $property = \App\Models\Property::find($reservation->property_id);
        if ($property) {
            $propertyName = $property->title ?? 'N/A';
            
            // Check property classification
            try {
                $propertyClassification = $property->getRawOriginal('property_classification');
            } catch (\Exception $e) {
                $propertyClassification = $property->property_classification ?? null;
                if (is_string($propertyClassification)) {
                    $propertyClassification = match($propertyClassification) {
                        'vacation_homes' => 4,
                        'hotel_booking' => 5,
                        'sell_rent' => 1,
                        'commercial' => 2,
                        'new_project' => 3,
                        default => null
                    };
                }
            }
            
            if ($propertyClassification == 4) {
                $propertyType = 'Vacation Home';
            } elseif ($propertyClassification == 5) {
                $propertyType = 'Hotel Property';
            } else {
                $propertyType = 'Property';
            }
            
            // Determine refund policy based on payment method
            if (isFlexibleReservation($reservation)) {
                $propertyType .= ' (Flexible)';
            } else {
                $propertyType .= ' (Non-Refundable)';
            }
        } else {
            $propertyName = 'Property (Missing)';
            $propertyType = 'Orphaned';
        }
    }
    
    echo "  Property Name: {$propertyName}\n";
    echo "  Property Type: {$propertyType}\n";
    echo "  Reservable Type: " . ($reservation->reservable_type ?? 'EMPTY') . "\n";
    echo "  Property ID: {$reservation->property_id}\n";
    echo "  Reservable ID: " . ($reservation->reservable_id ?? 'EMPTY') . "\n";
    echo "\n";
}

echo "=== Summary ===\n";
echo "Reservations 893 and 894 should now show property data with the new logic.\n";