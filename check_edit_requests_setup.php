<?php
/**
 * Diagnostic script to check property edit requests setup
 * Run: php check_edit_requests_setup.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Property Edit Requests Setup Check\n";
echo "========================================\n\n";

// 1. Check if table exists
echo "1. Checking if 'property_edit_requests' table exists...\n";
if (Schema::hasTable('property_edit_requests')) {
    echo "   ✓ Table exists\n\n";
    
    // 2. Check table structure
    echo "2. Checking table structure...\n";
    $columns = Schema::getColumnListing('property_edit_requests');
    $requiredColumns = ['id', 'property_id', 'requested_by', 'status', 'edited_data', 'original_data'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (empty($missingColumns)) {
        echo "   ✓ All required columns exist\n";
        echo "   Columns: " . implode(', ', $columns) . "\n\n";
    } else {
        echo "   ✗ Missing columns: " . implode(', ', $missingColumns) . "\n\n";
    }
    
    // 3. Check for data
    echo "3. Checking for existing edit requests...\n";
    $count = DB::table('property_edit_requests')->count();
    echo "   Total edit requests: {$count}\n";
    
    $pendingCount = DB::table('property_edit_requests')->where('status', 'pending')->count();
    $approvedCount = DB::table('property_edit_requests')->where('status', 'approved')->count();
    $rejectedCount = DB::table('property_edit_requests')->where('status', 'rejected')->count();
    
    echo "   - Pending: {$pendingCount}\n";
    echo "   - Approved: {$approvedCount}\n";
    echo "   - Rejected: {$rejectedCount}\n\n";
    
} else {
    echo "   ✗ Table does NOT exist\n\n";
    echo "   SOLUTION: Run the migration:\n";
    echo "   php artisan migrate\n\n";
    echo "   Or manually run:\n";
    echo "   php artisan migrate --path=database/migrations/2025_01_25_000000_create_property_edit_requests_table.php\n\n";
}

// 4. Check if model exists
echo "4. Checking if PropertyEditRequest model exists...\n";
if (class_exists('App\Models\PropertyEditRequest')) {
    echo "   ✓ Model exists\n\n";
} else {
    echo "   ✗ Model does NOT exist\n";
    echo "   Expected location: app/Models/PropertyEditRequest.php\n\n";
}

// 5. Check if service exists
echo "5. Checking if PropertyEditRequestService exists...\n";
if (class_exists('App\Services\PropertyEditRequestService')) {
    echo "   ✓ Service exists\n\n";
} else {
    echo "   ✗ Service does NOT exist\n";
    echo "   Expected location: app/Services/PropertyEditRequestService.php\n\n";
}

// 6. Check routes
echo "6. Checking routes...\n";
$routes = \Illuminate\Support\Facades\Route::getRoutes();
$editRequestRoutes = [];
foreach ($routes as $route) {
    if (strpos($route->uri(), 'property-edit-requests') !== false) {
        $editRequestRoutes[] = $route->uri() . ' [' . implode('|', $route->methods()) . ']';
    }
}

if (!empty($editRequestRoutes)) {
    echo "   ✓ Routes found:\n";
    foreach ($editRequestRoutes as $route) {
        echo "     - {$route}\n";
    }
    echo "\n";
} else {
    echo "   ✗ No routes found for property-edit-requests\n\n";
}

echo "========================================\n";
echo "Check Complete\n";
echo "========================================\n";

