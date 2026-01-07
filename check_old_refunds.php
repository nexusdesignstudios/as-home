<?php

require_once 'vendor/autoload.php';

use App\Models\PaymobPayment;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== Checking for Old Refund Data ===\n\n";

// Check PaymobPayment table for refund-related records
echo "1. PaymobPayment Table Analysis:\n";
$refundPayments = PaymobPayment::where(function($query) {
    $query->where('refund_status', '!=', null)
       ->orWhere('requires_approval', true);
})->get();

echo "Total refund-related payments: " . $refundPayments->count() . "\n";

foreach ($refundPayments as $payment) {
    echo "  Payment ID: {$payment->id}\n";
    echo "  Transaction ID: {$payment->transaction_id}\n";
    echo "  Amount: {$payment->amount}\n";
    echo "  Refund Amount: " . ($payment->refund_amount ?? 'N/A') . "\n";
    echo "  Refund Status: " . ($payment->refund_status ?? 'N/A') . "\n";
    echo "  Requires Approval: " . ($payment->requires_approval ? 'Yes' : 'No') . "\n";
    echo "  Refund Reason: " . ($payment->refund_reason ?? 'N/A') . "\n";
    echo "  Created: {$payment->created_at}\n";
    echo "  ---\n";
}

echo "\n2. Reservation Table Analysis:\n";
$reservations = Reservation::where(function($query) {
    $query->where('payment_status', 'refunded')
       ->orWhere('status', 'cancelled');
})->limit(10)->get();

echo "Recent refunded/cancelled reservations: " . $reservations->count() . "\n";

foreach ($reservations as $reservation) {
    echo "  Reservation ID: {$reservation->id}\n";
    echo "  Property ID: {$reservation->property_id}\n";
    echo "  Status: {$reservation->status}\n";
    echo "  Payment Status: {$reservation->payment_status}\n";
    echo "  Check-in: {$reservation->check_in_date}\n";
    echo "  Check-out: {$reservation->check_out_date}\n";
    echo "  Total Amount: {$reservation->total_amount}\n";
    echo "  Created: {$reservation->created_at}\n";
    echo "  ---\n";
}

echo "\n3. Database Schema Check:\n";

// Check table structures
echo "PaymobPayment table structure:\n";
$paymentColumns = DB::getSchemaBuilder()->getColumnListing('paymob_payments');
foreach ($paymentColumns as $column) {
    echo "  - {$column}\n";
}

echo "\nReservation table structure:\n";
$reservationColumns = DB::getSchemaBuilder()->getColumnListing('reservations');
foreach ($reservationColumns as $column) {
    echo "  - {$column}\n";
}

echo "\n4. Sample Refund Data:\n";

// Get specific sample records for detailed analysis
$samplePayments = PaymobPayment::with(['reservation'])
    ->where('refund_status', '!=', null)
    ->limit(3)
    ->get();

foreach ($samplePayments as $index => $payment) {
    echo "Sample " . ($index + 1) . ":\n";
    echo "  Payment Data:\n";
    echo json_encode($payment->toArray(), JSON_PRETTY_PRINT) . "\n";
    
    if ($payment->reservation) {
        echo "  Associated Reservation:\n";
        echo json_encode($payment->reservation->toArray(), JSON_PRETTY_PRINT) . "\n";
    }
    echo "  ---\n";
}

echo "\n=== Analysis Complete ===\n";
