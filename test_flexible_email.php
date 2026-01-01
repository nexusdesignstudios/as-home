<?php

// Test script to verify flexible hotel booking confirmation email functionality
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\HelperService;
use Illuminate\Support\Facades\DB;

echo "=== Testing Flexible Hotel Booking Confirmation Email ===\n\n";

// Test 1: Check if the HelperService case exists
echo "1. Checking HelperService for flexible_hotel_booking_confirmation case...\n";
$helperService = new HelperService();
try {
    $result = $helperService->replaceEmailVariables('', 'flexible_hotel_booking_confirmation');
    echo "✓ HelperService case exists!\n";
} catch (Exception $e) {
    echo "✗ HelperService case missing: " . $e->getMessage() . "\n";
}

// Test 2: Check if the email template exists in database
echo "\n2. Checking database for email template...\n";
$template = DB::table('settings')
    ->where('type', 'flexible_hotel_booking_confirmation_mail_template')
    ->first();

if ($template) {
    echo "✓ Email template found in database!\n";
    echo "Template ID: " . $template->id . "\n";
    echo "Template Type: " . $template->type . "\n";
    echo "Template length: " . strlen($template->data) . " characters\n";
} else {
    echo "✗ Email template not found in database!\n";
}

// Test 3: Test email variable replacement
echo "\n3. Testing email variable replacement...\n";
$testVariables = [
    'app_name' => 'Test App',
    'customer_name' => 'John Doe',
    'hotel_name' => 'Test Hotel',
    'room_type' => 'Deluxe Room',
    'room_number' => '101',
    'reservation_id' => '12345',
    'check_in_date' => '2026-01-15',
    'check_out_date' => '2026-01-18',
    'number_of_guests' => '2',
    'total_amount' => '1500',
    'currency_symbol' => 'EGP',
    'payment_status' => 'Paid',
    'hotel_address' => '123 Test Street, Cairo',
    'special_requests' => '<p>Special requests: None</p>'
];

if ($template) {
    $content = $template->data;
    foreach ($testVariables as $key => $value) {
        $content = str_replace("{{$key}}", $value, $content);
    }
    
    // Check if all variables were replaced
    $remainingVariables = [];
    if (preg_match_all('/\{([^}]+)\}/', $content, $matches)) {
        $remainingVariables = $matches[1];
    }
    
    if (empty($remainingVariables)) {
        echo "✓ All test variables replaced successfully!\n";
    } else {
        echo "✗ Some variables not replaced: " . implode(', ', $remainingVariables) . "\n";
    }
    
    // Show a snippet of the processed content
    echo "\nProcessed content snippet:\n";
    echo substr(strip_tags($content), 0, 200) . "...\n";
}

echo "\n=== Test Complete ===\n";