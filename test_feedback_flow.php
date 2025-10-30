<?php
/**
 * Test Feedback Flow Verification Script
 * Run this to verify the complete feedback email and submission flow
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Reservation;
use App\Models\PropertyQuestionAnswer;

echo "=== Feedback Flow Verification ===\n\n";

// Test reservation ID
$reservationId = 584;

echo "1. Checking Reservation {$reservationId}:\n";
$reservation = Reservation::with('customer', 'reservable')->find($reservationId);

if (!$reservation) {
    echo "   ❌ Reservation not found!\n";
    exit(1);
}

echo "   ✓ Reservation found\n";
echo "   - Customer: " . ($reservation->customer->name ?? 'N/A') . "\n";
echo "   - Email: " . ($reservation->customer->email ?? 'N/A') . "\n";
echo "   - Feedback Token: " . ($reservation->feedback_token ?? 'NOT SET') . "\n";
echo "   - Email Sent At: " . ($reservation->feedback_email_sent_at ?? 'NOT SENT') . "\n";

// Get property
$property = null;
if ($reservation->reservable_type === 'App\\Models\\Property') {
    $property = $reservation->reservable;
} elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
    $property = $reservation->reservable->property ?? null;
}

if ($property) {
    echo "\n2. Checking Property:\n";
    echo "   - Property ID: {$property->id}\n";
    echo "   - Property Name: {$property->title}\n";
    echo "   - Classification: " . ($property->getRawOriginal('property_classification') ?? 'N/A') . "\n";
    
    echo "\n3. Checking Feedback Answers:\n";
    $answers = PropertyQuestionAnswer::where('property_id', $property->id)
        ->where('reservation_id', $reservationId)
        ->with(['property_question_field', 'customer'])
        ->get();
    
    echo "   - Total Answers: " . $answers->count() . "\n";
    
    if ($answers->count() > 0) {
        echo "\n   Answers Found:\n";
        foreach ($answers as $answer) {
            echo "   - Question: " . ($answer->property_question_field->name ?? 'N/A') . "\n";
            echo "     Value: " . substr($answer->value, 0, 50) . "\n";
            echo "     Customer: " . ($answer->customer->name ?? 'N/A') . "\n";
            echo "     Submitted: " . ($answer->created_at ? $answer->created_at->format('Y-m-d H:i:s') : 'N/A') . "\n";
        }
        echo "\n   ✓ Answers are being saved correctly!\n";
    } else {
        echo "   ⚠ No answers found for this reservation yet.\n";
        echo "   → Submit the feedback form to test the flow.\n";
    }
} else {
    echo "\n   ❌ Property not found!\n";
}

echo "\n=== Verification Complete ===\n";

