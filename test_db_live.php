<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

echo "PHP Version: " . phpversion() . "\n";
echo "Laravel Environment: " . app()->environment() . "\n";

echo "\n--- Checking PaymentFormSubmission Model ---\n";

try {
    if (class_exists(\App\Models\PaymentFormSubmission::class)) {
        echo "✅ Class \\App\\Models\\PaymentFormSubmission exists.\n";
        
        try {
            $count = \App\Models\PaymentFormSubmission::count();
            echo "✅ Database connection successful. Row count: " . $count . "\n";
        } catch (\Exception $e) {
            echo "❌ Database Error: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ Class \\App\\Models\\PaymentFormSubmission NOT found.\n";
        echo "   Please check if app/Models/PaymentFormSubmission.php exists on the server.\n";
    }
} catch (\Throwable $e) {
    echo "❌ Critical Error: " . $e->getMessage() . "\n";
}

echo "\n--- Checking Reservations Table ---\n";
try {
    $hasStatus = \Illuminate\Support\Facades\Schema::hasColumn('reservations', 'status');
    echo "Reservations table exists: " . ($hasStatus ? 'Yes' : 'No') . "\n";
    
    // Check for 'approved' in enum if possible, or just try to insert/update a dummy to see if it accepts 'approved'
    // For now, just checking column existence is a good start.
} catch (\Exception $e) {
    echo "❌ Schema Error: " . $e->getMessage() . "\n";
}
