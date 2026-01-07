<?php

require_once 'vendor/autoload.php';

use App\Models\PaymobPayment;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== Analyzing Refund Process Integration ===\n\n";

// 1. Check current refund approval workflow
echo "1. Current Refund Approval Workflow Analysis:\n";

// Get sample refund payment with full details
$samplePayment = PaymobPayment::with(['reservation'])
    ->where('transaction_id', 'RES_1761400778_32_5204')
    ->first();

if ($samplePayment) {
    echo "Sample Payment Record:\n";
    echo json_encode($samplePayment->toArray(), JSON_PRETTY_PRINT) . "\n";
    
    if ($samplePayment->reservation) {
        echo "Associated Reservation:\n";
        echo json_encode($samplePayment->reservation->toArray(), JSON_PRETTY_PRINT) . "\n";
    }
}

echo "\n2. Check Update Refund Approval Status Method:\n";

// Check the updateRefundApprovalStatus method
echo "Looking at updateRefundApprovalStatus method...\n";

// Test the approval workflow
$testPaymentId = 222; // One of the pending payments
$testPayment = PaymobPayment::find($testPaymentId);

if ($testPayment) {
    echo "Test Payment (ID: {$testPaymentId}):\n";
    echo "  Current Status: {$testPayment->refund_status}\n";
    echo "  Requires Approval: " . ($testPayment->requires_approval ? 'Yes' : 'No') . "\n";
    echo "  Refund Amount: {$testPayment->refund_amount}\n";
    echo "  Created: {$testPayment->created_at}\n";
    
    // Check if this payment has a reservation
    if ($testPayment->reservation) {
        echo "  Reservation Status: {$testPayment->reservation->status}\n";
        echo "  Reservation Payment Status: {$testPayment->reservation->payment_status}\n";
        echo "  Property ID: {$testPayment->reservation->property_id}\n";
        
        // Check property ownership
        if ($testPayment->reservation->reservable_type === 'App\\Models\\Property') {
            $property = \App\Models\Property::find($testPayment->reservation->reservable_id);
            if ($property) {
                echo "  Property Owner: {$property->added_by}\n";
            }
        } elseif ($testPayment->reservation->reservable_type === 'App\\Models\\HotelRoom') {
            $room = \App\Models\HotelRoom::find($testPayment->reservation->reservable_id);
            if ($room && $room->property) {
                echo "  Property Owner: {$room->property->added_by}\n";
            }
        }
    }
}

echo "\n3. Database Schema Analysis:\n";

// Check the relationship between payments and reservations
echo "Payment-Reservation Relationship:\n";
$paymentReservationLinks = DB::table('paymob_payments')
    ->select('id', 'reservation_id', 'refund_status', 'requires_approval')
    ->where('refund_status', '!=', null)
    ->limit(5)
    ->get();

foreach ($paymentReservationLinks as $link) {
    echo "  Payment ID: {$link->id} -> Reservation ID: {$link->reservation_id} (Refund: {$link->refund_status}, Requires Approval: " . ($link->requires_approval ? 'Yes' : 'No') . ")\n";
}

echo "\n4. Check Frontend Integration Points:\n";

// Check how the frontend consumes this data
echo "Frontend Integration Analysis:\n";

// Look for any API endpoints that handle refund approvals
echo "Available refund-related routes:\n";

// Check routes file for refund endpoints
$routesFile = base_path('routes/api.php');
if (file_exists($routesFile)) {
    $routesContent = file_get_contents($routesFile);
    
    // Look for refund-related routes
    if (preg_match_all('/Route::(get|post)\s*\([\'"][^\'"]+refund[^\'"]*[\'"][^\'"]*\)/', $routesContent, $matches)) {
        foreach ($matches as $match) {
            echo "  Found: " . trim($match[0]) . "\n";
        }
    }
}

echo "\n5. Process Flow Analysis:\n";

// Analyze the complete refund process flow
echo "Refund Process Flow:\n";
echo "1. Customer requests refund -> creates PaymobPayment record\n";
echo "2. If reservation is active -> requires_approval = true\n";
echo "3. Property owner gets notification (should be implemented)\n";
echo "4. Property owner approves/rejects via updateRefundApprovalStatus\n";
echo "5. If approved -> Paymob processes refund\n";
echo "6. Payment status updated to 'refunded'\n";

echo "\n6. Issues Identified:\n";

// Identify potential issues with old integration
echo "Potential Issues:\n";

// Check for inconsistent data
$inconsistentRefunds = PaymobPayment::where('refund_status', 'pending')
    ->where('created_at', '<', '2026-01-01')
    ->whereHas('reservation', function ($query) {
        $query->where('payment_status', '!=', 'refunded');
    })
    ->get();

if ($inconsistentRefunds->count() > 0) {
    echo "  ISSUE: Found pending refunds for non-refunded reservations\n";
    foreach ($inconsistentRefunds as $refund) {
        echo "    Payment ID: {$refund->id}, Reservation Payment Status: " . ($refund->reservation->payment_status ?? 'N/A') . "\n";
    }
}

// Check for orphaned payments
$orphanedPayments = PaymobPayment::whereNull('reservation_id')
    ->where('refund_status', '!=', null)
    ->where('created_at', '<', '2026-01-01')
    ->count();

if ($orphanedPayments > 0) {
    echo "  ISSUE: Found {$orphanedPayments} orphaned payment records\n";
}

echo "\n=== Analysis Complete ===\n";
