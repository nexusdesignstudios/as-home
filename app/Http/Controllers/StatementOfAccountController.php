<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Reservation;
use App\Models\Customer;
use App\Models\StatementOfAccountEdit;
use App\Models\StatementOfAccountManualEntry;
use App\Services\BootstrapTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class StatementOfAccountController extends Controller
{
    /**
     * Display the Statement of Account page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Admin only access
        if (!has_permissions('read', 'property')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }

        return view('statement_of_account.index');
    }

    /**
     * Get list of hotel properties with their owners.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getHotelProperties(Request $request)
    {
        $search = $request->search ?? '';

        $query = Property::where('property_classification', 5) // Hotel properties only
            ->with(['customer:id,name,email,mobile'])
            ->select('id', 'title', 'added_by');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%$search%")
                    ->orWhereHas('customer', function ($ownerQuery) use ($search) {
                        $ownerQuery->where('name', 'LIKE', "%$search%")
                            ->orWhere('email', 'LIKE', "%$search%");
                    });
            });
        }

        $properties = $query->orderBy('title', 'ASC')->get();

        $result = [];
        foreach ($properties as $property) {
            $owner = $property->customer;
            $result[] = [
                'id' => $property->id,
                'title' => $property->title,
                'owner_id' => $owner->id ?? null,
                'owner_name' => $owner->name ?? 'N/A',
                'owner_email' => $owner->email ?? 'N/A',
            ];
        }

        return response()->json($result);
    }

    /**
     * Get revenue collector data grouped by users/owners.
     * Uses reservations as the parent data source (same as reservations tab).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getRevenueCollectorData(Request $request)
    {
        try {
            $dateFrom = $request->date_from ?? null;
            $dateTo = $request->date_to ?? null;

            // Get tax rates from system settings (ensure they are numeric)
            $serviceChargeRate = (float)(system_setting('hotel_service_charge_rate') ?? 10);
            $salesTaxRate = (float)(system_setting('hotel_sales_tax_rate') ?? 14);
            $cityTaxRate = (float)(system_setting('hotel_city_tax_rate') ?? 5);
            $currencySymbol = system_setting('currency_symbol') ?? 'EGP';

            // Use reservations as parent data source (same as reservations tab)
            // Get all confirmed, paid hotel reservations with proper eager loading
            $query = Reservation::where('reservable_type', 'App\\Models\\HotelRoom')
                ->where('status', 'confirmed')
                ->where('payment_status', 'paid')
                ->with([
                    'customer:id,name,email,mobile',
                    'reservable:id,property_id,room_type_id',
                    'reservable.roomType:id,name',
                    'reservable.property:id,title,added_by,non_refundable,property_classification',
                    'reservable.property.customer:id,name,email,mobile'
                ]);

            // Filter by date range (same as reservations tab)
            if ($dateFrom) {
                $query->where('check_in_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->where('check_out_date', '<=', $dateTo);
            }

            $reservations = $query->get();

            if ($reservations->isEmpty()) {
                return response()->json([
                    'users' => [],
                    'grand_totals' => [
                        'total_revenue' => number_format(0, 2),
                        'total_taxes' => number_format(0, 2),
                        'total_net_revenue' => number_format(0, 2),
                        'total_debit' => number_format(0, 2),
                        'total_credit' => number_format(0, 2),
                        'currency_symbol' => $currencySymbol,
                    ],
                    'total_users' => 0,
                ]);
            }

            // Group reservations by property owner
            $ownersMap = [];
            foreach ($reservations as $reservation) {
                $hotelRoom = $reservation->reservable;
                
                // Skip if hotel room doesn't exist
                if (!$hotelRoom) {
                    Log::warning('Reservation has no hotel room', ['reservation_id' => $reservation->id]);
                    continue;
                }

                // Ensure property is loaded
                if (!$hotelRoom->relationLoaded('property')) {
                    $hotelRoom->load('property.customer');
                }

                $property = $hotelRoom->property;
                
                if (!$property) {
                    // Try to load property manually
                    $property = Property::find($hotelRoom->property_id);
                    if (!$property) {
                        Log::warning('Hotel room has no property', [
                            'reservation_id' => $reservation->id,
                            'hotel_room_id' => $hotelRoom->id,
                            'property_id' => $hotelRoom->property_id
                        ]);
                        continue;
                    }
                    // Load customer relationship
                    if (!$property->relationLoaded('customer')) {
                        $property->load('customer');
                    }
                }
                
                // Ensure property is classification 5 (hotel)
                $propertyClassification = $property->getRawOriginal('property_classification');
                if ($propertyClassification != 5) {
                    continue;
                }
                
                // Include both flexible and non-refundable reservations
                // No need to filter by property non_refundable setting

                $owner = $property->customer;
                if (!$owner) {
                    continue;
                }

                $ownerId = $owner->id;
                $propertyId = $property->id;

                // Initialize owner data structure
                if (!isset($ownersMap[$ownerId])) {
                    $ownersMap[$ownerId] = [
                        'user_id' => $ownerId,
                        'user_name' => $owner->name ?? 'N/A',
                        'user_email' => $owner->email ?? '',
                        'user_phone' => $owner->mobile ?? '',
                        'properties' => [],
                    ];
                }

                // Initialize property data structure
                $propertyKey = $propertyId;
                if (!isset($ownersMap[$ownerId]['properties'][$propertyKey])) {
                    $ownersMap[$ownerId]['properties'][$propertyKey] = [
                        'property_id' => $propertyId,
                        'property_title' => $property->title ?? 'N/A',
                        'reservations' => [],
                        'total_revenue' => 0,
                        'total_taxes' => 0,
                        'total_net_revenue' => 0,
                        'total_debit' => 0,
                        'total_credit' => 0,
                    ];
                }

                // Add reservation to property
                $ownersMap[$ownerId]['properties'][$propertyKey]['reservations'][] = $reservation;
            }

            // Calculate financial data for each property
            $usersData = [];
            $grandTotalRevenue = 0;
            $grandTotalTaxes = 0;
            $grandTotalNetRevenue = 0;
            $grandTotalDebit = 0;
            $grandTotalCredit = 0;

            foreach ($ownersMap as $ownerId => $ownerData) {
                $userTotalRevenue = 0;
                $userTotalTaxes = 0;
                $userTotalNetRevenue = 0;
                $userTotalDebit = 0;
                $userTotalCredit = 0;

                $propertiesList = [];

                foreach ($ownerData['properties'] as $propertyData) {
                    $propertyTotalRevenue = 0;
                    $propertyTotalTaxes = 0;
                    $propertyTotalNetRevenue = 0;
                    $propertyTotalDebit = 0;
                    $propertyTotalCredit = 0;

                    // Calculate totals for all reservations in this property
                    foreach ($propertyData['reservations'] as $reservation) {
                        // Get edited values if they exist (only if table exists)
                        $edit = null;
                        try {
                            if (Schema::hasTable('statement_of_account_edits')) {
                                $edit = StatementOfAccountEdit::where('reservation_id', $reservation->id)->first();
                            }
                        } catch (\Exception $e) {
                            // Table doesn't exist, skip edit lookup
                            Log::warning('StatementOfAccountEdit table not found, skipping edit lookup', [
                                'reservation_id' => $reservation->id
                            ]);
                        }

                        // Calculate financials (same logic as before)
                        $revenueBeforeTax = (float)$reservation->total_price;
                        $serviceCharge = $revenueBeforeTax * ($serviceChargeRate / 100);
                        $salesTax = $revenueBeforeTax * ($salesTaxRate / 100);
                        $cityTax = $revenueBeforeTax * ($cityTaxRate / 100);
                        $totalTaxAmount = $serviceCharge + $salesTax + $cityTax;
                        $netRevenue = $revenueBeforeTax - $totalTaxAmount;

                        // Commission: 15% to Ashome, 85% to Owner
                        $debitAmount = $netRevenue * 0.15; // Ashome 15%
                        $creditAmount = $netRevenue * 0.85; // Owner 85%

                        // Use edited credit amount if exists (reservation-level)
                        if ($edit && $edit->credit_amount !== null) {
                            $creditAmount = $edit->credit_amount;
                            $debitAmount = $netRevenue - $creditAmount;
                        }

                        $propertyTotalRevenue += $revenueBeforeTax;
                        $propertyTotalTaxes += $totalTaxAmount;
                        $propertyTotalNetRevenue += $netRevenue;
                        $propertyTotalDebit += $debitAmount;
                        $propertyTotalCredit += $creditAmount;
                    }

                    // Check for property-level credit edit (applies to all reservations for this property)
                    $propertyEdit = null;
                    try {
                        if (Schema::hasTable('statement_of_account_edits')) {
                            $propertyEdit = StatementOfAccountEdit::where('property_id', $propertyData['property_id'])
                                ->whereNull('reservation_id')
                                ->first();
                            
                            // If property-level edit exists, override the calculated credit
                            if ($propertyEdit && $propertyEdit->credit_amount !== null) {
                                $propertyTotalCredit = (float)$propertyEdit->credit_amount;
                                // Recalculate debit to maintain balance
                                $propertyTotalDebit = $propertyTotalNetRevenue - $propertyTotalCredit;
                            }
                        }
                    } catch (\Exception $e) {
                        // Table doesn't exist, skip property edit lookup
                    }

                    // Calculate balance (Debit - Credit)
                    $balance = $propertyTotalDebit - $propertyTotalCredit;

                    if ($propertyTotalRevenue > 0) {
                        $propertiesList[] = [
                            'property_id' => $propertyData['property_id'],
                            'property_title' => $propertyData['property_title'],
                            'reservation_count' => count($propertyData['reservations']),
                            'total_revenue' => number_format($propertyTotalRevenue, 2),
                            'total_taxes' => number_format($propertyTotalTaxes, 2),
                            'total_net_revenue' => number_format($propertyTotalNetRevenue, 2),
                            'total_debit' => number_format($propertyTotalDebit, 2),
                            'total_credit' => number_format($propertyTotalCredit, 2),
                            'balance' => number_format($balance, 2),
                            // Raw values for calculations
                            '_revenue' => $propertyTotalRevenue,
                            '_taxes' => $propertyTotalTaxes,
                            '_net_revenue' => $propertyTotalNetRevenue,
                            '_debit' => $propertyTotalDebit,
                            '_credit' => $propertyTotalCredit,
                            '_balance' => $balance,
                        ];

                        // Accumulate user totals
                        $userTotalRevenue += $propertyTotalRevenue;
                        $userTotalTaxes += $propertyTotalTaxes;
                        $userTotalNetRevenue += $propertyTotalNetRevenue;
                        $userTotalDebit += $propertyTotalDebit;
                        $userTotalCredit += $propertyTotalCredit;
                    }
                }

                // Calculate user totals balance
                $userBalance = $userTotalDebit - $userTotalCredit;

                // Only add user if they have properties with revenue
                if (!empty($propertiesList) && $userTotalRevenue > 0) {
                    $usersData[] = [
                        'user_id' => $ownerData['user_id'],
                        'user_name' => $ownerData['user_name'],
                        'user_email' => $ownerData['user_email'],
                        'user_phone' => $ownerData['user_phone'],
                        'properties' => $propertiesList,
                        'totals' => [
                            'total_revenue' => number_format($userTotalRevenue, 2),
                            'total_taxes' => number_format($userTotalTaxes, 2),
                            'total_net_revenue' => number_format($userTotalNetRevenue, 2),
                            'total_debit' => number_format($userTotalDebit, 2),
                            'total_credit' => number_format($userTotalCredit, 2),
                            'balance' => number_format($userBalance, 2),
                        ],
                    ];

                    // Accumulate grand totals
                    $grandTotalRevenue += $userTotalRevenue;
                    $grandTotalTaxes += $userTotalTaxes;
                    $grandTotalNetRevenue += $userTotalNetRevenue;
                    $grandTotalDebit += $userTotalDebit;
                    $grandTotalCredit += $userTotalCredit;
                }
            }

            // Calculate grand totals balance
            $grandBalance = $grandTotalDebit - $grandTotalCredit;

            return response()->json([
                'users' => $usersData,
                'grand_totals' => [
                    'total_revenue' => number_format($grandTotalRevenue, 2),
                    'total_taxes' => number_format($grandTotalTaxes, 2),
                    'total_net_revenue' => number_format($grandTotalNetRevenue, 2),
                    'total_debit' => number_format($grandTotalDebit, 2),
                    'total_credit' => number_format($grandTotalCredit, 2),
                    'balance' => number_format($grandBalance, 2),
                    'currency_symbol' => $currencySymbol,
                ],
                'total_users' => count($usersData),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getRevenueCollectorData: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to load revenue collector data: ' . $e->getMessage(),
                'users' => [],
                'grand_totals' => [
                    'total_revenue' => '0.00',
                    'total_taxes' => '0.00',
                    'total_net_revenue' => '0.00',
                    'total_debit' => '0.00',
                    'total_credit' => '0.00',
                    'currency_symbol' => system_setting('currency_symbol') ?? 'EGP',
                ],
                'total_users' => 0,
            ], 500);
        }
    }

    /**
     * Get statement of account data for a specific property.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getStatementData(Request $request)
    {
        $propertyId = $request->property_id;
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 20;
        $sort = $request->sort ?? 'check_in_date';
        $order = $request->order ?? 'DESC';
        $dateFrom = $request->date_from ?? null;
        $dateTo = $request->date_to ?? null;
        $reservationId = $request->reservation_id ?? null;

        if (!$propertyId) {
            return response()->json([
                'total' => 0,
                'rows' => [],
                'totals' => [
                    'total_revenue' => 0,
                    'total_taxes' => 0,
                    'total_net_revenue' => 0,
                    'total_debit' => 0,
                    'total_credit' => 0,
                ]
            ]);
        }

        // Get property details
        $property = Property::with('customer')->find($propertyId);
        if (!$property || $property->property_classification != 5) {
            return response()->json([
                'total' => 0,
                'rows' => [],
                'totals' => [
                    'total_revenue' => 0,
                    'total_taxes' => 0,
                    'total_net_revenue' => 0,
                    'total_debit' => 0,
                    'total_credit' => 0,
                ]
            ]);
        }

        // Get hotel room IDs for this property
        $hotelRoomIds = DB::table('hotel_rooms')->where('property_id', $propertyId)->pluck('id');

        // Query reservations
        $query = Reservation::where('reservable_type', 'App\\Models\\HotelRoom')
            ->whereIn('reservable_id', $hotelRoomIds)
            ->where('status', 'confirmed')
            ->where('payment_status', 'paid')
            ->with(['customer', 'reservable', 'reservable.roomType']);

        // Include both flexible and non-refundable reservations
        // No need to filter by property non_refundable setting

        // Filter by reservation ID
        if ($reservationId) {
            $query->where('id', $reservationId);
        }

        // Filter by date range
        if ($dateFrom) {
            $query->where('check_in_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('check_out_date', '<=', $dateTo);
        }

        $total = $query->count();
        $reservations = $query->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        // Get tax rates from system settings
        $serviceChargeRate = system_setting('hotel_service_charge_rate') ?? 10;
        $salesTaxRate = system_setting('hotel_sales_tax_rate') ?? 14;
        $cityTaxRate = system_setting('hotel_city_tax_rate') ?? 5;
        $currencySymbol = system_setting('currency_symbol') ?? 'EGP';

        $rows = [];
        $totalRevenue = 0;
        $totalTaxes = 0;
        $totalNetRevenue = 0;
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($reservations as $reservation) {
            // Get edited values if they exist (only if table exists)
            $edit = null;
            try {
                if (Schema::hasTable('statement_of_account_edits')) {
                    $edit = StatementOfAccountEdit::where('reservation_id', $reservation->id)->first();
                }
            } catch (\Exception $e) {
                // Table doesn't exist, skip edit lookup
            }

            // Calculate financials
            $revenueBeforeTax = (float)$reservation->total_price;
            $serviceCharge = $revenueBeforeTax * ($serviceChargeRate / 100);
            $salesTax = $revenueBeforeTax * ($salesTaxRate / 100);
            $cityTax = $revenueBeforeTax * ($cityTaxRate / 100);
            $totalTaxAmount = $serviceCharge + $salesTax + $cityTax;
            $netRevenue = $revenueBeforeTax - $totalTaxAmount;

            // Commission: 15% to Ashome, 85% to Owner
            $debitAmount = $netRevenue * 0.15; // Ashome 15%
            $creditAmount = $netRevenue * 0.85; // Owner 85%

            // Use edited credit amount if exists
            if ($edit && $edit->credit_amount !== null) {
                $creditAmount = $edit->credit_amount;
                // Recalculate debit to maintain balance
                $debitAmount = $netRevenue - $creditAmount;
            }

            // Get guest name
            $guestName = $reservation->customer_name ?? ($reservation->customer->name ?? 'N/A');

            // Get property owner
            $propertyOwner = $property->customer ? $property->customer->name : 'N/A';

            $rows[] = [
                'id' => $reservation->id,
                'reservation_id' => $reservation->id,
                'property_name' => $property->title,
                'property_owner' => $propertyOwner,
                'guest_name' => $guestName,
                'check_in_date' => $reservation->check_in_date->format('Y-m-d'),
                'check_out_date' => $reservation->check_out_date->format('Y-m-d'),
                'total_revenue' => number_format($revenueBeforeTax, 2),
                'hotel_taxes' => number_format($totalTaxAmount, 2),
                'net_revenue' => number_format($netRevenue, 2),
                'debit' => number_format($debitAmount, 2),
                'credit' => number_format($creditAmount, 2),
                'description' => $edit->description ?? '',
                'is_editable' => true,
                // Raw values for calculations
                '_revenue' => $revenueBeforeTax,
                '_taxes' => $totalTaxAmount,
                '_net_revenue' => $netRevenue,
                '_debit' => $debitAmount,
                '_credit' => $creditAmount,
            ];

            // Accumulate totals
            $totalRevenue += $revenueBeforeTax;
            $totalTaxes += $totalTaxAmount;
            $totalNetRevenue += $netRevenue;
            $totalDebit += $debitAmount;
            $totalCredit += $creditAmount;
        }

        return response()->json([
            'total' => $total,
            'rows' => $rows,
            'totals' => [
                'total_revenue' => number_format($totalRevenue, 2),
                'total_taxes' => number_format($totalTaxes, 2),
                'total_net_revenue' => number_format($totalNetRevenue, 2),
                'total_debit' => number_format($totalDebit, 2),
                'total_credit' => number_format($totalCredit, 2),
                'currency_symbol' => $currencySymbol,
            ],
            'property' => [
                'id' => $property->id,
                'title' => $property->title,
                'owner_name' => $propertyOwner,
            ]
        ]);
    }

    /**
     * Update description or credit amount for a reservation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $reservationId
     * @return \Illuminate\Http\Response
     */
    public function updateField(Request $request, $reservationId)
    {
        $request->validate([
            'field' => 'required|in:description,credit',
            'value' => 'required',
        ]);

        try {
            $reservation = Reservation::findOrFail($reservationId);

        // Verify it's a hotel reservation
        if ($reservation->reservable_type !== 'App\\Models\\HotelRoom') {
            return response()->json([
                'success' => false,
                'message' => 'Only hotel reservations can be edited.'
            ], 400);
        }

        // Check if table exists before trying to use it
        if (!Schema::hasTable('statement_of_account_edits')) {
            return response()->json([
                'success' => false,
                'message' => 'Statement of Account edits table does not exist. Please run migrations.'
            ], 500);
        }

        $edit = StatementOfAccountEdit::updateOrCreate(
            ['reservation_id' => $reservationId],
            [
                'edited_by' => Auth::id(),
            ]
        );

        if ($request->field === 'description') {
            $edit->description = $request->value;
        } elseif ($request->field === 'credit') {
            $edit->credit_amount = (float) $request->value;
        }

        $edit->save();

        return response()->json([
            'success' => true,
            'message' => 'Field updated successfully.'
        ]);
        } catch (\Exception $e) {
            Log::error('Error updating statement field: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update field: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update credit amount for a property.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $propertyId
     * @return \Illuminate\Http\Response
     */
    public function updatePropertyCredit(Request $request, $propertyId)
    {
        $request->validate([
            'credit_amount' => 'required|numeric|min:0',
        ]);

        try {
            $property = Property::findOrFail($propertyId);

            // Verify it's a hotel property (classification 5)
            // Use getRawOriginal to get the actual database value, avoiding casting issues
            // The Property model has an accessor that returns "hotel_booking" instead of 5
            $propertyClassification = $property->getRawOriginal('property_classification');
            
            // Handle both integer and string comparisons (database might return string)
            $isHotel = ($propertyClassification == 5 || $propertyClassification === '5' || $propertyClassification === 5);
            
            if (!$isHotel) {
                Log::warning('Credit edit attempted on non-hotel property', [
                    'property_id' => $propertyId,
                    'property_classification' => $propertyClassification,
                    'raw_value' => $property->getRawOriginal('property_classification'),
                    'accessor_value' => $property->property_classification
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Only hotel properties can be edited. Property classification: ' . ($propertyClassification ?? 'null')
                ], 400);
            }

            // Check if table exists before trying to use it
            if (!Schema::hasTable('statement_of_account_edits')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Statement of Account edits table does not exist. Please run migrations.'
                ], 500);
            }

            // Get reservation ID from request to check if it's a flexible rate reservation
            $reservationId = $request->reservation_id ?? null;
            $isFlexibleRate = false;
            
            if ($reservationId) {
                $reservation = Reservation::find($reservationId);
                if ($reservation && $reservation->reservable_type === 'App\\Models\\HotelRoom') {
                    $hotelRoom = $reservation->reservable;
                    if ($hotelRoom) {
                        $refundPolicy = $hotelRoom->getRawOriginal('refund_policy');
                        $isFlexibleRate = ($refundPolicy === 'flexible');
                    }
                }
            } else {
                // If no reservation ID, check if property has any flexible rate rooms
                // For property-level edits, we'll allow if property has at least one flexible rate room
                $flexibleRoomCount = DB::table('hotel_rooms')
                    ->where('property_id', $propertyId)
                    ->where('refund_policy', 'flexible')
                    ->count();
                $isFlexibleRate = ($flexibleRoomCount > 0);
            }

            // Allow editing but warn for non-flexible rates (or skip validation entirely if user wants all editable)
            // The frontend will still show all credit fields as editable
            // You can add a warning in the response if needed
            $warning = '';
            if (!$isFlexibleRate && $reservationId) {
                $warning = 'Note: This is a non-refundable reservation.';
            }

            // Check if this is a reservation-level edit or property-level edit
            if ($reservationId) {
                // Reservation-level edit - update or create for this specific reservation
                $edit = StatementOfAccountEdit::updateOrCreate(
                    ['reservation_id' => $reservationId],
                    [
                        'property_id' => $propertyId,
                        'credit_amount' => (float)$request->credit_amount,
                        'edited_by' => Auth::id(),
                    ]
                );
            } else {
                // Property-level edit - first remove any existing property-level edit
                StatementOfAccountEdit::where('property_id', $propertyId)
                    ->whereNull('reservation_id')
                    ->delete();
                
                // Create new property-level edit
                $edit = StatementOfAccountEdit::create([
                    'property_id' => $propertyId,
                    'reservation_id' => null, // Property-level edit
                    'credit_amount' => (float)$request->credit_amount,
                    'edited_by' => Auth::id(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Property credit updated successfully.' . ($warning ? ' ' . $warning : ''),
                'credit_amount' => number_format($edit->credit_amount, 2),
                'warning' => $warning
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating property credit: ' . $e->getMessage(), [
                'property_id' => $propertyId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update property credit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create or update a manual statement entry for a property.
     */
    public function saveManualEntry(Request $request, $propertyId)
    {
        $request->validate([
            'id' => 'nullable|exists:statement_of_account_manual_entries,id',
            'date' => 'nullable|date',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'debit_amount' => 'nullable|numeric|min:0',
            'credit_amount' => 'nullable|numeric|min:0',
            'comments' => 'nullable|string',
        ]);

        try {
            $entry = StatementOfAccountManualEntry::updateOrCreate(
                [ 'id' => $request->id ],
                [
                    'property_id' => $propertyId,
                    'date' => $request->date,
                    'reference' => $request->reference,
                    'description' => $request->description,
                    'debit_amount' => (float)($request->debit_amount ?? 0),
                    'credit_amount' => (float)($request->credit_amount ?? 0),
                    'comments' => $request->comments,
                    'created_by' => Auth::id(),
                ]
            );

            return response()->json([
                'success' => true,
                'entry' => $entry,
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving manual entry: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save manual entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a manual statement entry.
     */
    public function deleteManualEntry(Request $request, $entryId)
    {
        try {
            $entry = StatementOfAccountManualEntry::findOrFail($entryId);
            $entry->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get owner statement of account with individual transactions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getOwnerStatement(Request $request)
    {
        $propertyId = $request->property_id ?? null;
        $ownerId = $request->owner_id ?? null;
        $dateFrom = $request->date_from ?? null;
        $dateTo = $request->date_to ?? null;

        try {
            // If property ID is provided, get owner from property
            if ($propertyId) {
                $property = Property::with('customer')->find($propertyId);
                if (!$property) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Invalid property selected.',
                        'owner' => null,
                        'transactions' => []
                    ]);
                }

                // Use raw value for classification to avoid casting issues (string vs int)
                $classification = $property->getRawOriginal('property_classification');
                if ($classification != 5) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Selected property is not a hotel (class 5).',
                        'owner' => null,
                        'transactions' => []
                    ]);
                }
                $owner = $property->customer;
                if (!$owner) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Property has no owner.',
                        'owner' => null,
                        'transactions' => []
                    ]);
                }
                $ownerId = $owner->id;
            } else if ($ownerId) {
                // Get owner directly
                $owner = Customer::find($ownerId);
                if (!$owner) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Owner not found.',
                        'owner' => null,
                        'transactions' => []
                    ]);
                }
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Please select a property or owner.',
                    'owner' => null,
                    'transactions' => []
                ]);
            }

            // Get all properties for this owner (hotel properties only)
            $properties = Property::where('property_classification', 5)
                ->where('added_by', $ownerId)
                ->get();

            if ($properties->isEmpty()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Owner has no hotel properties.',
                    'owner' => [
                        'id' => $owner->id,
                        'name' => $owner->name ?? 'N/A',
                        'email' => $owner->email ?? 'N/A',
                        'mobile' => $owner->mobile ?? 'N/A',
                        'address' => $owner->address ?? 'N/A',
                    ],
                    'transactions' => []
                ]);
            }

            // Get hotel room IDs for owner's properties
            $propertyIds = $properties->pluck('id');
            $hotelRoomIds = DB::table('hotel_rooms')->whereIn('property_id', $propertyIds)->pluck('id');

            // Get all confirmed, paid or cash reservations
            $query = Reservation::where('reservable_type', 'App\\Models\\HotelRoom')
                ->whereIn('reservable_id', $hotelRoomIds)
                ->whereIn('status', ['confirmed', 'approved'])
                ->whereIn('payment_status', ['paid', 'cash'])
                ->with(['customer', 'reservable.roomType', 'reservable.property', 'payment:id,reservation_id,status']);

            // Filter by date range
            if ($dateFrom) {
                $query->where('check_in_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->where('check_in_date', '<=', $dateTo);
            }

            $reservations = $query->orderBy('check_in_date', 'ASC')->get();

            // Get tax rates
            $serviceChargeRate = (float)(system_setting('hotel_service_charge_rate') ?? 10);
            $salesTaxRate = (float)(system_setting('hotel_sales_tax_rate') ?? 14);
            $cityTaxRate = (float)(system_setting('hotel_city_tax_rate') ?? 5);
            $currencySymbol = system_setting('currency_symbol') ?? 'EGP';

            $transactions = [];
            $runningBalance = 0;

            foreach ($reservations as $reservation) {
                $hotelRoom = $reservation->reservable ?? null;
                if (!$hotelRoom) continue;
                
                $property = $hotelRoom->property ?? null;
                if (!$property) continue;

                // Include both flexible and non-refundable reservations
                // No need to filter by property non_refundable setting

                // Determine refund policy based on payment method
                // Cash/Manual payment = Flexible
                // Online/Paymob payment = Non-Refundable
                $paymentMethod = $reservation->payment_method ?? 'cash';
                $isOnlinePayment = ($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment);
                $isFlexibleRate = !$isOnlinePayment; // Flexible = Manual/Cash, Non-Refundable = Online
                
                // Store refund policy for reference (for backward compatibility)
                $refundPolicy = $isFlexibleRate ? 'flexible' : 'non-refundable';

                // Get property-level credit edit
                $propertyEdit = null;
                try {
                    if (Schema::hasTable('statement_of_account_edits')) {
                        $propertyEdit = StatementOfAccountEdit::where('property_id', $property->id)
                            ->whereNull('reservation_id')
                            ->first();
                    }
                } catch (\Exception $e) {
                    // Skip if table doesn't exist
                }

                // Get reservation-level edit
                $reservationEdit = null;
                try {
                    if (Schema::hasTable('statement_of_account_edits')) {
                        $reservationEdit = StatementOfAccountEdit::where('reservation_id', $reservation->id)->first();
                    }
                } catch (\Exception $e) {
                    // Skip if table doesn't exist
                }

                // Calculate financials
                // Step 1: Get gross reservation amount (before taxes)
                $revenueBeforeTax = (float)$reservation->total_price;
                
                // Step 2: Calculate taxes from gross revenue
                $serviceCharge = $revenueBeforeTax * ($serviceChargeRate / 100);
                $salesTax = $revenueBeforeTax * ($salesTaxRate / 100);
                $cityTax = $revenueBeforeTax * ($cityTaxRate / 100);
                $totalTaxAmount = $serviceCharge + $salesTax + $cityTax;
                
                // Step 3: Calculate net revenue (pure reservation amount without taxes)
                $netRevenue = $revenueBeforeTax - $totalTaxAmount;

                // Step 4: Calculate commission from net revenue (pure amount after taxes)
                // Debit: 15% of net revenue (pure reservation amount without taxes) - Ashome commission
                // Credit: 85% of net revenue (pure reservation amount without taxes) - Owner portion
                $debitAmount = $netRevenue * 0.15;  // 15% commission of pure reservation (after taxes)
                $creditAmount = $netRevenue * 0.85; // 85% to owner of pure reservation (after taxes)

                // Use property-level edit if exists, otherwise reservation-level edit
                if ($propertyEdit && $propertyEdit->credit_amount !== null) {
                    $creditAmount = (float)$propertyEdit->credit_amount;
                    $debitAmount = $netRevenue - $creditAmount;
                } else if ($reservationEdit && $reservationEdit->credit_amount !== null) {
                    $creditAmount = (float)$reservationEdit->credit_amount;
                    $debitAmount = $netRevenue - $creditAmount;
                }

                // Payment method badge (already determined above)
                $paymentMethodBadge = $isOnlinePayment ? 'online' : 'cash';

                // Transaction 1: Debit (Ashome Commission)
                $runningBalance += $debitAmount;
                $transactions[] = [
                    'date' => $reservation->check_in_date->format('d-M-y'),
                    'reference' => 'Invoice',
                    'description' => 'As-home Commission',
                    'debit' => $debitAmount,
                    'credit' => 0,
                    'balance' => $runningBalance,
                    'comments' => $propertyEdit->description ?? $reservationEdit->description ?? 'As-home commission',
                    'reservation_id' => $reservation->id,
                    'property_id' => $property->id,
                    'type' => 'debit',
                    'is_flexible_rate' => false, // Debit transactions are not editable
                    'refund_policy' => null, // Don't show refund policy on debit transactions
                    'room_number' => (!empty($hotelRoom->room_number) && $hotelRoom->room_number != '0') ? $hotelRoom->room_number : null,
                    'payment_method' => $paymentMethodBadge, // Add payment method indicator
                    'payment_method_raw' => $paymentMethod // Store original payment method
                ];

                // Transaction 2: Credit (Owner Payment)
                $runningBalance -= $creditAmount;
                $transactions[] = [
                    'date' => $reservation->check_in_date->format('d-M-y'),
                    'reference' => '',
                    'description' => $isFlexibleRate ? 'Flexible Rate' : 'Non-Refundable',
                    'debit' => 0,
                    'credit' => $creditAmount,
                    'balance' => $runningBalance,
                    'comments' => $isFlexibleRate ? '85% due to the hotel (Flexible)' : '85% due to the hotel',
                    'reservation_id' => $reservation->id,
                    'property_id' => $property->id,
                    'type' => 'credit',
                    'is_flexible_rate' => $isFlexibleRate, // Flag to indicate if credit is editable
                    'refund_policy' => $refundPolicy, // Show refund policy for reference
                    'room_number' => (!empty($hotelRoom->room_number) && $hotelRoom->room_number != '0') ? $hotelRoom->room_number : null,
                    'payment_method' => $paymentMethodBadge, // Add payment method indicator
                    'payment_method_raw' => $paymentMethod // Store original payment method
                ];
            }

            // Append manual entries for this property (if selected) or for all owner's properties
            $manualEntriesQuery = StatementOfAccountManualEntry::query();
            if ($propertyId) {
                $manualEntriesQuery->where('property_id', $propertyId);
            } else {
                $manualEntriesQuery->whereIn('property_id', $propertyIds);
            }
            if ($dateFrom) {
                $manualEntriesQuery->whereDate('date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $manualEntriesQuery->whereDate('date', '<=', $dateTo);
            }
            $manualEntries = $manualEntriesQuery->orderBy('date', 'ASC')->get();

            foreach ($manualEntries as $entry) {
                // Running balance effect: +debit -credit
                $runningBalance += (float)$entry->debit_amount;
                $runningBalance -= (float)$entry->credit_amount;
                $transactions[] = [
                    'date' => $entry->date ? $entry->date->format('d-M-y') : '',
                    'reference' => $entry->reference ?? '',
                    'description' => $entry->description ?? '',
                    'debit' => (float)$entry->debit_amount,
                    'credit' => (float)$entry->credit_amount,
                    'balance' => $runningBalance,
                    'comments' => $entry->comments ?? '',
                    'reservation_id' => null,
                    'property_id' => $entry->property_id,
                    'type' => 'manual',
                    'manual_entry_id' => $entry->id, // Include entry ID for updates
                    'is_flexible_rate' => false // Manual entries are always editable
                ];
            }

            // Get property ID if property was selected
            $selectedPropertyId = $propertyId ?? ($properties->first()->id ?? null);

            return response()->json([
                'error' => false,
                'owner' => [
                    'id' => $owner->id,
                    'name' => $owner->name ?? 'N/A',
                    'email' => $owner->email ?? 'N/A',
                    'mobile' => $owner->mobile ?? 'N/A',
                    'address' => $owner->address ?? 'N/A',
                ],
                'property_id' => $selectedPropertyId,
                'transactions' => $transactions,
                'total_balance' => $runningBalance,
                'currency_symbol' => $currencySymbol,
                'statement_date' => now()->format('d-M-y'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getOwnerStatement: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to load statement: ' . $e->getMessage(),
                'owner' => null,
                'transactions' => []
            ], 500);
        }
    }

    /**
     * Export statement data to CSV/Excel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $propertyId = $request->property_id;
        if (!$propertyId) {
            return redirect()->back()->with('error', 'Please select a property first.');
        }

        // Get all data (no pagination)
        $request->merge(['limit' => 10000, 'offset' => 0]);
        $response = $this->getStatementData($request);
        $data = json_decode($response->getContent(), true);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="statement_of_account_' . $propertyId . '_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'Reservation ID',
                'Property Name',
                'Property Owner',
                'Guest Name',
                'Check-in Date',
                'Check-out Date',
                'Total Revenue (before tax)',
                'Hotel Taxes',
                'Net Revenue (after tax)',
                'Debit (Ashome 15%)',
                'Credit (Owner 85%)',
                'Description'
            ]);

            // Data rows
            foreach ($data['rows'] ?? [] as $row) {
                fputcsv($file, [
                    $row['reservation_id'],
                    $row['property_name'],
                    $row['property_owner'],
                    $row['guest_name'],
                    $row['check_in_date'],
                    $row['check_out_date'],
                    $row['total_revenue'],
                    $row['hotel_taxes'],
                    $row['net_revenue'],
                    $row['debit'],
                    $row['credit'],
                    $row['description']
                ]);
            }

            // Totals row
            if (isset($data['totals'])) {
                fputcsv($file, [
                    'TOTAL',
                    '',
                    '',
                    '',
                    '',
                    '',
                    $data['totals']['total_revenue'],
                    $data['totals']['total_taxes'],
                    $data['totals']['total_net_revenue'],
                    $data['totals']['total_debit'],
                    $data['totals']['total_credit'],
                    ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

