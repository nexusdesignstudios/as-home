<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\PaymobPayoutTransaction;
use App\Services\Payment\PaymobPayoutService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutController extends Controller
{
    /**
     * Display a listing of pending payouts.
     */
    public function index(Request $request)
    {
        if (!has_permissions('read', 'property_payouts')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }

        // Get month and year from request or use current month/year as default
        $month = $request->get('month', Carbon::now()->format('m'));
        $year = $request->get('year', Carbon::now()->format('Y'));

        $pendingPayouts = $this->getPendingPayouts($month, $year);

        return view('payouts.index', compact('pendingPayouts'));
    }

    /**
     * Display a listing of processed payouts.
     */
    public function history(Request $request)
    {
        if (!has_permissions('read', 'property_payouts')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }

        $query = PaymobPayoutTransaction::with(['customer', 'property'])
            ->where('is_processed', true);

        // Apply filters if provided
        if ($request->has('month') && !empty($request->month)) {
            $query->where('payout_month', $request->month);
        }

        if ($request->has('year') && !empty($request->year)) {
            $query->where('payout_year', $request->year);
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('disbursement_status', $request->status);
        }

        $processedPayouts = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('payouts.history', compact('processedPayouts'));
    }

    /**
     * Process a payout for a specific property.
     */
    public function processPayout(Request $request, $id)
    {
        if (!has_permissions('create', 'property_payouts')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }

        try {
            // Get month and year from request or use current month/year as default
            $month = $request->get('month', Carbon::now()->format('m'));
            $year = $request->get('year', Carbon::now()->format('Y'));

            // Get pending payout data
            $pendingPayout = $this->getPendingPayouts($month, $year)->where('property_id', $id)->first();

            if (!$pendingPayout) {
                return redirect()->back()->with('error', 'Payout data not found.');
            }

            // Create payout service
            $payoutService = new PaymobPayoutService();

            // Get property and customer
            $property = Property::findOrFail($pendingPayout->property_id);

            if (!$property->customer) {
                return redirect()->back()->with('error', 'Property owner not found.');
            }

            // Calculate commission
            $commissionData = PaymobPayoutTransaction::calculateCommission(
                $property,
                $pendingPayout->original_amount
            );

            // Prepare payout data
            $payoutData = [
                'customer_id' => $property->customer->id,
                'issuer' => $request->issuer,
                'amount' => $commissionData['amount_after_commission'],
                'msisdn' => $request->msisdn ?? $property->customer->mobile,
                'first_name' => $property->customer->first_name ?? '',
                'last_name' => $property->customer->last_name ?? '',
                'email' => $property->customer->email ?? '',
                'bank_card_number' => $request->bank_card_number ?? '',
                'bank_transaction_type' => $request->bank_transaction_type ?? 'cash_transfer',
                'bank_code' => $request->bank_code ?? '',
                'full_name' => $property->customer->name ?? '',
                'client_reference_id' => $request->client_reference_id ?? null,
                'notes' => "Payout for " . $pendingPayout->payout_month . "/" . $pendingPayout->payout_year,
            ];

            // Process the payout
            $result = $payoutService->processInstantCashin($payoutData);

            // Store the transaction in database
            $payoutTransaction = PaymobPayoutTransaction::create([
                'customer_id' => $payoutData['customer_id'],
                'property_id' => $property->id,
                'transaction_id' => $result['transaction_id'],
                'issuer' => $result['issuer'],
                'amount' => $result['amount'],
                'original_amount' => $pendingPayout->original_amount,
                'commission_percentage' => $commissionData['commission_percentage'],
                'msisdn' => $payoutData['msisdn'] ?? null,
                'full_name' => $payoutData['full_name'] ?? null,
                'first_name' => $payoutData['first_name'] ?? null,
                'last_name' => $payoutData['last_name'] ?? null,
                'email' => $payoutData['email'] ?? null,
                'bank_card_number' => $payoutData['bank_card_number'] ?? null,
                'bank_transaction_type' => $payoutData['bank_transaction_type'] ?? null,
                'bank_code' => $payoutData['bank_code'] ?? null,
                'client_reference_id' => $payoutData['client_reference_id'] ?? null,
                'disbursement_status' => $result['disbursement_status'],
                'status_code' => $result['status_code'],
                'status_description' => $result['status_description'],
                'reference_number' => $result['reference_number'],
                'paid' => $result['paid'],
                'aman_cashing_details' => $result['aman_cashing_details'],
                'transaction_data' => $result,
                'notes' => $payoutData['notes'] ?? null,
                'payout_month' => $pendingPayout->payout_month,
                'payout_year' => $pendingPayout->payout_year,
                'is_processed' => true,
            ]);

            Log::info('Property payout processed', [
                'transaction_id' => $payoutTransaction->transaction_id,
                'property_id' => $property->id,
                'amount' => $payoutTransaction->amount,
                'status' => $payoutTransaction->disbursement_status
            ]);

            return redirect()->back()->with('success', 'Payout processed successfully.');
        } catch (\Exception $e) {
            Log::error('Payout processing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Failed to process payout: ' . $e->getMessage());
        }
    }

    /**
     * Get pending payouts data.
     *
     * @param string $month The month to get payouts for (format: 01-12)
     * @param string $year The year to get payouts for (format: YYYY)
     * @return \Illuminate\Support\Collection
     */
    private function getPendingPayouts($month = null, $year = null)
    {
        $currentMonth = $month ?: Carbon::now()->format('m');
        $currentYear = $year ?: Carbon::now()->format('Y');

        // Get properties with classifications 4 or 5
        $properties = Property::whereIn('property_classification', [4, 5])
            ->with('customer')
            ->get();

        $pendingPayouts = collect();

        // Process properties
        foreach ($properties as $property) {
            // Skip if no customer associated
            if (!$property->customer) {
                continue;
            }

            // Get total reservation amount for this property in the specified month
            // Using created_at as requested
            $totalAmount = Reservation::where(function ($query) use ($property) {
                // Check for direct property reservations
                $query->where('reservable_id', $property->id)
                    ->where('reservable_type', Property::class);
            })
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->where('payment_status', 'paid')
                ->sum('total_price');

            // Add hotel room reservations for this property
            $hotelRoomIds = DB::table('hotel_rooms')
                ->where('property_id', $property->id)
                ->pluck('id')
                ->toArray();

            if (!empty($hotelRoomIds)) {
                $hotelRoomAmount = Reservation::whereIn('reservable_id', $hotelRoomIds)
                    ->where('reservable_type', 'App\\Models\\HotelRoom')
                    ->whereMonth('created_at', $currentMonth)
                    ->whereYear('created_at', $currentYear)
                    ->where('payment_status', 'paid')
                    ->sum('total_price');

                $totalAmount += $hotelRoomAmount;
            }

            // Skip if no paid reservations
            if ($totalAmount <= 0) {
                continue;
            }

            // Set default rent package if not set
            $rentPackage = $property->getRawOriginal('rent_package') ?: 'basic';

            // Get raw property classification
            $propertyClassification = $property->getRawOriginal('property_classification');

            // Calculate commission
            $commissionData = PaymobPayoutTransaction::calculateCommission($property, $totalAmount);

            // Check if payout already processed for this property this month
            $payoutExists = PaymobPayoutTransaction::where('property_id', $property->id)
                ->where('payout_month', $currentMonth)
                ->where('payout_year', $currentYear)
                ->where('is_processed', true)
                ->exists();

            if (!$payoutExists) {
                // Map classification number to text
                $classificationText = 'Unknown';
                if ($propertyClassification == 4) {
                    $classificationText = 'Vacation Home';
                } elseif ($propertyClassification == 5) {
                    $classificationText = 'Hotel Booking';
                }

                $pendingPayouts->push((object)[
                    'property_id' => $property->id,
                    'property_title' => $property->title,
                    'customer_name' => $property->customer->name,
                    'customer_id' => $property->customer->id,
                    'original_amount' => $totalAmount,
                    'commission_percentage' => $commissionData['commission_percentage'],
                    'amount_after_commission' => $commissionData['amount_after_commission'],
                    'rent_package' => $rentPackage,
                    'property_classification' => $classificationText,
                    'payout_month' => $currentMonth,
                    'payout_year' => $currentYear
                ]);
            }
        }

        return $pendingPayouts;
    }
}
