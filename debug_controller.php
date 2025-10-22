<?php

// Debug script to test the exact controller logic
echo "=== Debugging ReservationController Logic ===\n\n";

try {
    // Bootstrap Laravel
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    use App\Models\Reservation;
    use App\Models\Customer;
    use App\Models\Property;
    
    $customerId = 14;
    $perPage = 20;
    
    echo "Testing with customer_id: {$customerId}\n\n";
    
    // Test 1: Check if customer exists
    echo "1. Checking if customer exists...\n";
    $customer = Customer::find($customerId);
    if ($customer) {
        echo "✓ Customer found: {$customer->name} ({$customer->email})\n";
    } else {
        echo "✗ Customer not found\n";
        exit;
    }
    
    // Test 2: Check if customer has properties
    echo "\n2. Checking customer's properties...\n";
    $properties = Property::where('added_by', $customerId)->get();
    echo "✓ Customer has " . $properties->count() . " properties\n";
    
    if ($properties->count() > 0) {
        foreach ($properties->take(3) as $prop) {
            echo "  - Property ID: {$prop->id}, Title: {$prop->title}\n";
        }
    }
    
    // Test 3: Test the basic query
    echo "\n3. Testing basic reservations query...\n";
    $query = Reservation::query();
    $query->whereHas('property', function ($propertyQuery) use ($customerId) {
        $propertyQuery->where('added_by', $customerId);
    });
    
    $count = $query->count();
    echo "✓ Found {$count} reservations for customer's properties\n";
    
    // Test 4: Test with relationships (this might be where it fails)
    echo "\n4. Testing with relationships...\n";
    try {
        $reservations = $query->with([
            'customer:id,name,email,mobile',
            'property:id,title,category_id,price,title_image,property_classification',
            'property.category:id,category,image',
            'reservable'
        ])
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
        
        echo "✓ Successfully loaded " . $reservations->count() . " reservations with relationships\n";
        
        // Test 5: Test data transformation
        echo "\n5. Testing data transformation...\n";
        foreach ($reservations as $reservation) {
            try {
                $data = $reservation->toArray();
                echo "✓ Reservation {$reservation->id} transformed successfully\n";
                
                // Test the specific fields that might cause issues
                if ($reservation->customer) {
                    echo "  - Customer: {$reservation->customer->name}\n";
                }
                
                if ($reservation->property) {
                    echo "  - Property: {$reservation->property->title}\n";
                }
                
                if ($reservation->reservable) {
                    echo "  - Reservable: " . get_class($reservation->reservable) . " (ID: {$reservation->reservable->id})\n";
                }
                
            } catch (Exception $e) {
                echo "✗ Error transforming reservation {$reservation->id}: " . $e->getMessage() . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "✗ Error loading relationships: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
    }
    
    // Test 6: Test pagination
    echo "\n6. Testing pagination...\n";
    try {
        $paginatedReservations = $query->with([
            'customer:id,name,email,mobile',
            'property:id,title,category_id,price,title_image,property_classification',
            'property.category:id,category,image',
            'reservable'
        ])
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
        
        echo "✓ Pagination successful: " . $paginatedReservations->total() . " total, " . $paginatedReservations->count() . " on this page\n";
        
    } catch (Exception $e) {
        echo "✗ Error with pagination: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Fatal error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug Complete ===\n";
