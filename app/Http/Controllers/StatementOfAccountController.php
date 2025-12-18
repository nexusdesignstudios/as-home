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
            // Get all confirmed, paid or cash hotel reservations with proper eager loading
            $query = Reservation::where('reservable_type', 'App\\Models\\HotelRoom')
                ->where('status', 'confirmed')
                ->whereIn('payment_status', ['paid', 'cash'])
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
     * Get tax invoice data showing commissions for AS Home and Hotel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getTaxInvoice(Request $request)
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
                        'commissions' => []
                    ]);
                }

                // Use raw value for classification to avoid casting issues
                $classification = $property->getRawOriginal('property_classification');
                if ($classification != 5) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Selected property is not a hotel (class 5).',
                        'owner' => null,
                        'commissions' => []
                    ]);
                }
                $owner = $property->customer;
                if (!$owner) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Property has no owner.',
                        'owner' => null,
                        'commissions' => []
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
                        'commissions' => []
                    ]);
                }
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Please select a property or owner.',
                    'owner' => null,
                    'commissions' => []
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
                    'commissions' => []
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

            $allReservations = $query->orderBy('check_in_date', 'ASC')->get();

            // Filter for cash/flexible reservations only (exclude online payments)
            // Tax invoices should only include cash/offline payments, not online/paymob payments
            $flexibleReservations = $allReservations->filter(function ($reservation) {
                $paymentMethod = strtolower($reservation->payment_method ?? 'cash');
                
                // If payment_method is explicitly 'cash', treat as flexible
                if ($paymentMethod === 'cash') {
                    return true;
                }
                
                // Otherwise, check if it's online payment
                $isOnlinePayment = (
                    $paymentMethod === 'paymob' || 
                    $paymentMethod === 'online' || 
                    ($reservation->payment !== null && $paymentMethod !== 'cash')
                );
                
                // Only include cash/offline payments (exclude online/paymob)
                return !$isOnlinePayment;
            });

            // Use filtered reservations (cash/flexible only)
            $reservations = $flexibleReservations;

            // Log if any reservations were filtered out
            $totalCount = $allReservations->count();
            $flexibleCount = $flexibleReservations->count();
            if ($totalCount > $flexibleCount) {
                Log::info('Tax invoice: Filtered out online payments', [
                    'total_reservations' => $totalCount,
                    'flexible_reservations' => $flexibleCount,
                    'filtered_out' => $totalCount - $flexibleCount
                ]);
            }

            // Get tax rates
            $serviceChargeRate = (float)(system_setting('hotel_service_charge_rate') ?? 10);
            $salesTaxRate = (float)(system_setting('hotel_sales_tax_rate') ?? 14);
            $cityTaxRate = (float)(system_setting('hotel_city_tax_rate') ?? 5);
            $currencySymbol = system_setting('currency_symbol') ?? 'EGP';

            $commissions = [];

            foreach ($reservations as $reservation) {
                $hotelRoom = $reservation->reservable ?? null;
                if (!$hotelRoom) continue;
                
                $property = $hotelRoom->property ?? null;
                if (!$property) continue;

                // Determine payment method
                $paymentMethod = $reservation->payment_method ?? 'cash';
                $isOnlinePayment = ($paymentMethod === 'paymob' || $paymentMethod === 'online' || $reservation->payment);
                $paymentMethodBadge = $isOnlinePayment ? 'online' : 'cash';

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
                $revenueBeforeTax = (float)$reservation->total_price;
                
                // Calculate taxes from gross revenue
                $serviceCharge = $revenueBeforeTax * ($serviceChargeRate / 100);
                $salesTax = $revenueBeforeTax * ($salesTaxRate / 100);
                $cityTax = $revenueBeforeTax * ($cityTaxRate / 100);
                $totalTaxAmount = $serviceCharge + $salesTax + $cityTax;
                
                // Calculate net revenue (pure reservation amount without taxes)
                $netRevenue = $revenueBeforeTax - $totalTaxAmount;

                // Calculate commission from net revenue
                // AS Home: 15% of net revenue
                // Hotel: 85% of net revenue
                $asHomeCommission = $netRevenue * 0.15;  // 15% commission for AS Home
                $hotelCommission = $netRevenue * 0.85;   // 85% for hotel

                // Use property-level edit if exists, otherwise reservation-level edit
                if ($propertyEdit && $propertyEdit->credit_amount !== null) {
                    $hotelCommission = (float)$propertyEdit->credit_amount;
                    $asHomeCommission = $netRevenue - $hotelCommission;
                } else if ($reservationEdit && $reservationEdit->credit_amount !== null) {
                    $hotelCommission = (float)$reservationEdit->credit_amount;
                    $asHomeCommission = $netRevenue - $hotelCommission;
                }

                // Add commission entry
                $commissions[] = [
                    'date' => $reservation->check_in_date->format('d-M-y'),
                    'reference' => 'Invoice',
                    'description' => 'Reservation Commission',
                    'as_home_commission' => $asHomeCommission,
                    'hotel_commission' => $hotelCommission,
                    'comments' => $propertyEdit->description ?? $reservationEdit->description ?? 'Commission breakdown',
                    'reservation_id' => $reservation->id,
                    'property_id' => $property->id,
                    'payment_method' => $paymentMethodBadge,
                    'room_number' => (!empty($hotelRoom->room_number) && $hotelRoom->room_number != '0') ? $hotelRoom->room_number : null,
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
                'commissions' => $commissions,
                'currency_symbol' => $currencySymbol,
                'statement_date' => now()->format('d-M-y'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getTaxInvoice: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Failed to load tax invoice: ' . $e->getMessage(),
                'owner' => null,
                'commissions' => []
            ], 500);
        }
    }

    /**
     * Export tax invoice as PDF (same as email PDF)
     * Uses the same calculations and PDF template as MonthlyTaxInvoiceService
     */
    public function exportTaxInvoice(Request $request)
    {
        $propertyId = $request->property_id ?? null;
        $ownerId = $request->owner_id ?? null;
        $dateFrom = $request->date_from ?? null;
        $dateTo = $request->date_to ?? null;

        try {
            // Get owner and property (same logic as getTaxInvoice)
            if ($propertyId) {
                $property = Property::with('customer')->find($propertyId);
                if (!$property) {
                    return redirect()->back()->with('error', 'Invalid property selected.');
                }

                $classification = $property->getRawOriginal('property_classification');
                if ($classification != 5) {
                    return redirect()->back()->with('error', 'Selected property is not a hotel.');
                }
                $owner = $property->customer;
                if (!$owner) {
                    return redirect()->back()->with('error', 'Property has no owner.');
                }
                $ownerId = $owner->id;
            } else if ($ownerId) {
                $owner = Customer::find($ownerId);
                if (!$owner) {
                    return redirect()->back()->with('error', 'Owner not found.');
                }
            } else {
                return redirect()->back()->with('error', 'Please select a property or owner.');
            }

            // Get all properties for this owner (hotel properties only)
            $properties = Property::where('property_classification', 5)
                ->where('added_by', $ownerId)
                ->get();

            if ($properties->isEmpty()) {
                return redirect()->back()->with('error', 'Owner has no hotel properties.');
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

            $allReservations = $query->orderBy('check_in_date', 'ASC')->get();

            // Filter for cash/flexible reservations only (exclude online payments)
            $flexibleReservations = $allReservations->filter(function ($reservation) {
                $paymentMethod = strtolower($reservation->payment_method ?? 'cash');
                
                if ($paymentMethod === 'cash') {
                    return true;
                }
                
                $isOnlinePayment = (
                    $paymentMethod === 'paymob' || 
                    $paymentMethod === 'online' || 
                    ($reservation->payment !== null && $paymentMethod !== 'cash')
                );
                
                return !$isOnlinePayment;
            });

            if ($flexibleReservations->isEmpty()) {
                return redirect()->back()->with('error', 'No flexible reservations found for the selected period.');
            }

            // Calculate totals using SAME logic as MonthlyTaxInvoiceService
            $totalRevenue = $flexibleReservations->sum('total_price'); // Room Sales (gross revenue)
            
            // Calculate total taxes as 22.36% of total revenue
            $totalTaxRate = 22.36; // Total taxes percentage
            $totalTaxesAmount = (float)$totalRevenue * ($totalTaxRate / 100.0);
            
            // Calculate revenue after taxes
            $revenueAfterTaxes = $totalRevenue - $totalTaxesAmount;
            
            // Commission is 15% of REVENUE AFTER TAXES (not gross revenue)
            $commissionRate = 15; // 15% commission for As-home from revenue after taxes
            $commissionAmount = (float)$revenueAfterTaxes * ($commissionRate / 100.0);
            
            // Total amount due is the commission (15% of revenue after taxes)
            $totalAmountDue = $commissionAmount;
            
            // For display purposes
            $hotelRate = 85; // 85% for hotel (this is calculated on revenue after taxes for display)
            $hotelAmount = $revenueAfterTaxes * ($hotelRate / 100);
            $netAmount = $hotelAmount;
            
            // Individual tax breakdowns for display
            $serviceChargeRate = 10.0;
            $salesTaxRate = 14.0;
            $cityTaxRate = 5.0;
            $serviceChargeAmount = (float)$totalRevenue * ($serviceChargeRate / 100.0);
            $salesTaxAmount = (float)$totalRevenue * ($salesTaxRate / 100.0);
            $cityTaxAmount = (float)$totalRevenue * ($cityTaxRate / 100.0);

            // Get currency symbol
            $currencySymbol = system_setting('currency_symbol') ?? 'EGP';

            // Determine month year from date range or use current month
            $monthYear = null;
            if ($dateFrom) {
                $monthYear = \Carbon\Carbon::parse($dateFrom)->format('Y-m');
            } else {
                $monthYear = now()->format('Y-m');
            }
            $monthYearDisplay = \Carbon\Carbon::parse($monthYear . '-01')->format('F Y');

            // Get property from first reservation
            $property = null;
            $firstReservation = $flexibleReservations->first();
            if ($firstReservation) {
                if ($firstReservation->reservable_type === 'App\\Models\\Property') {
                    $property = $firstReservation->reservable;
                } elseif ($firstReservation->reservable_type === 'App\\Models\\HotelRoom' && $firstReservation->reservable) {
                    $property = $firstReservation->reservable->property;
                }
            }

            // Generate reservation details HTML (same as email)
            $reservationDetails = $this->generateReservationDetailsHtmlForPDF($flexibleReservations, $currencySymbol);
            $propertySummary = $this->generatePropertySummaryHtmlForPDF($flexibleReservations, $currencySymbol);

            // Build variables array (SAME as MonthlyTaxInvoiceService)
            $appName = env("APP_NAME") ?? "eBroker";
            $propertyName = $property ? ($property->title ?? 'Hotel') : 'Hotel';
            
            $variables = [
                'app_name' => $appName,
                'owner_name' => $owner->name,
                'property_name' => $propertyName,
                'month_year' => $monthYearDisplay,
                'total_reservations' => $flexibleReservations->count(),
                // Formatted values for email templates
                'total_revenue' => number_format($totalRevenue, 2),
                'room_sales' => number_format($totalRevenue, 2),
                'currency_symbol' => $currencySymbol,
                'service_charge_rate' => $serviceChargeRate,
                'service_charge_amount' => number_format($serviceChargeAmount, 2),
                'sales_tax_rate' => $salesTaxRate,
                'sales_tax_amount' => number_format($salesTaxAmount, 2),
                'city_tax_rate' => $cityTaxRate,
                'city_tax_amount' => number_format($cityTaxAmount, 2),
                'total_taxes_rate' => $totalTaxRate,
                'total_taxes_amount' => number_format($totalTaxesAmount, 2),
                'revenue_after_taxes' => number_format($revenueAfterTaxes, 2),
                'commission_rate' => $commissionRate,
                'commission_amount' => number_format($commissionAmount, 2),
                'total_amount_due' => number_format($totalAmountDue, 2),
                'hotel_rate' => $hotelRate,
                'hotel_amount' => number_format($hotelAmount, 2),
                'net_amount' => number_format($netAmount, 2),
                // Raw numeric values for PDF template
                'room_sales_raw' => $totalRevenue,
                'commission_amount_raw' => $commissionAmount,
                'total_amount_due_raw' => $totalAmountDue,
                'hotel_amount_raw' => $hotelAmount,
                'reservation_details' => $reservationDetails,
                'property_summary' => $propertySummary,
            ];

            // Generate invoice number
            $invoiceNumber = $owner->id . '-' . str_replace('-', '', $monthYear) . '-F';
            $accommodationNumber = $owner->id ?? 'N/A';
            $vatNumber = system_setting('company_vat_number') ?? system_setting('vat_number') ?? null;

            $variables['invoice_number'] = $invoiceNumber;
            $variables['accommodation_number'] = $accommodationNumber;
            $variables['vat_number'] = $vatNumber;
            
            // Add property information
            if ($property) {
                $variables['property_name'] = $property->title ?? 'Hotel';
                $propertyAddress = $property->address ?? ($property->client_address ?? '');
                $variables['property_address'] = $propertyAddress;
                $variables['property_vat'] = $property->hotel_vat ?? '';
            } else {
                $variables['property_name'] = 'Hotel';
                $variables['property_address'] = '';
                $variables['property_vat'] = '';
            }
            
            $variables['payment_method_type'] = 'Flexible (Manual/Cash)';
            $variables['invoice_type_label'] = 'Flexible Rate Reservations';
            $variables['hotel_percentage'] = $hotelRate;
            $variables['commission_percentage'] = $commissionRate;

            // Generate bank account details HTML
            $variables['bank_account_details'] = $this->generateBankAccountDetailsHtmlForPDF();

            // Use the SAME PDF template as email (hotel_booking_tax_invoice_flexible)
            $templateType = 'hotel_booking_tax_invoice_flexible';

            // Generate PDF using TaxInvoiceService (same as email)
            $taxInvoiceService = new \App\Services\PDF\TaxInvoiceService();
            $pdf = $taxInvoiceService->generatePDF($owner, $variables, $monthYear, $templateType);

            // Generate filename
            $monthYearDisplayForFile = \Carbon\Carbon::parse($monthYear . '-01')->format('Y-m');
            $filename = 'tax_invoice_' . $owner->id . '_' . $monthYearDisplayForFile . '_Flexible.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Error exporting tax invoice PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return redirect()->back()->with('error', 'Failed to export tax invoice: ' . $e->getMessage());
        }
    }

    /**
     * Generate HTML for reservation details (same format as email)
     */
    private function generateReservationDetailsHtmlForPDF($reservations, $currencySymbol = 'EGP')
    {
        $html = '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
        $html .= '<thead><tr style="background-color: #f8f9fa;">';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Reservation ID</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Property</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Check-in</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Check-out</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Guests</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Amount</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($reservations as $reservation) {
            $propertyName = $this->getPropertyNameForPDF($reservation);

            $html .= '<tr>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">#' . $reservation->id . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $propertyName . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $reservation->check_in_date->format('d M Y') . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $reservation->check_out_date->format('d M Y') . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $reservation->number_of_guests . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $currencySymbol . ' ' . number_format($reservation->total_price, 2) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Generate HTML for property summary (same format as email)
     */
    private function generatePropertySummaryHtmlForPDF($reservations, $currencySymbol = 'EGP')
    {
        $propertySummary = $reservations->groupBy(function($reservation) {
            if ($reservation->reservable_type === 'App\\Models\\Property') {
                return $reservation->reservable_id;
            } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom' && $reservation->reservable) {
                return $reservation->reservable->property_id;
            }
            return null;
        })->map(function ($reservations, $propertyId) {
            $firstReservation = $reservations->first();
            $propertyName = $this->getPropertyNameForPDF($firstReservation);
            $totalRevenue = $reservations->sum('total_price');
            $reservationCount = $reservations->count();

            return [
                'property_name' => $propertyName,
                'reservations' => $reservationCount,
                'revenue' => $totalRevenue
            ];
        })->filter();

        $html = '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">';
        $html .= '<thead><tr style="background-color: #f8f9fa;">';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Property</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Reservations</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Revenue</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($propertySummary as $summary) {
            $html .= '<tr>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $summary['property_name'] . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $summary['reservations'] . '</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">' . $currencySymbol . ' ' . number_format($summary['revenue'], 2) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Get property name from reservation
     */
    private function getPropertyNameForPDF($reservation)
    {
        if ($reservation->reservable_type === 'App\Models\Property') {
            return $reservation->reservable->title ?? 'Property';
        } elseif ($reservation->reservable_type === 'App\Models\HotelRoom') {
            return ($reservation->reservable->property->title ?? 'Hotel') . ' - Room';
        }

        return 'Unknown Property';
    }

    /**
     * Generate HTML for bank account details (same as email)
     */
    private function generateBankAccountDetailsHtmlForPDF()
    {
        $bankName = system_setting('bank_name') ?? 'National Bank of Egypt';
        $accountNumber = system_setting('bank_account_number') ?? '3413131856116201017';
        $routingNumber = system_setting('bank_routing_number') ?? '987654321';
        $swiftCode = system_setting('bank_swift_code') ?? 'NBEGEGCX341';
        $accountHolder = 'As Home for Asset Management';
        $iban = system_setting('bank_iban') ?? 'EG100003034131318561162010170';
        $branch = system_setting('bank_branch') ?? 'Hurghada Branch';
        $bankAddress = system_setting('bank_address') ?? 'EL Kawthar Hurghada Branch';

        $html = '<div style="margin-top: 30px; padding: 20px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">';
        $html .= '<h3 style="color: #495057; margin-bottom: 15px;">Bank Account Details for Commission Payment</h3>';
        $html .= '<table style="width: 100%; border-collapse: collapse;">';
        $html .= '<tr><td style="padding: 8px; font-weight: bold; width: 30%;">Bank Name:</td><td style="padding: 8px;">' . $bankName . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; font-weight: bold;">Branch:</td><td style="padding: 8px;">' . $branch . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; font-weight: bold;">Bank Address:</td><td style="padding: 8px;">' . $bankAddress . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; font-weight: bold;">Account Holder:</td><td style="padding: 8px;">' . $accountHolder . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; font-weight: bold;">Account Number:</td><td style="padding: 8px;">' . $accountNumber . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; font-weight: bold;">IBAN:</td><td style="padding: 8px;">' . $iban . '</td></tr>';
        $html .= '<tr><td style="padding: 8px; font-weight: bold;">SWIFT Code:</td><td style="padding: 8px;">' . $swiftCode . '</td></tr>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
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

