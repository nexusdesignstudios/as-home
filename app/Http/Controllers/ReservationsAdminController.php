<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Property;
use App\Models\HotelRoom;
use App\Models\Customer;
use App\Services\BootstrapTableService;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ReservationsAdminController extends Controller
{
    protected $apiResponseService;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->apiResponseService = app(\App\Services\ApiResponseService::class);
    }
    /**
     * Display a listing of reservations.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!has_permissions('read', 'reservations')) {
            return redirect()->back()->with('error', PERMISSION_ERROR_MSG);
        }

        return view('reservations.index');
    }

    /**
     * Get reservations for bootstrap table.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getReservationsList(Request $request)
    {
        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'DESC';
        $search = $request->search ?? '';
        $type = $request->type ?? 'all'; // 'vacation_homes', 'hotels', 'all'
        $dateFrom = $request->date_from ?? null;
        $dateTo = $request->date_to ?? null;

        $query = Reservation::with(['customer', 'reservable']);

        // Filter by type
        if ($type === 'vacation_homes') {
            $query->where('reservable_type', 'App\\Models\\Property');
        } elseif ($type === 'hotels') {
            $query->where('reservable_type', 'App\\Models\\HotelRoom');
        }

        // Filter by date range
        if ($dateFrom) {
            $query->where('check_in_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('check_out_date', '<=', $dateTo);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', function ($customerQuery) use ($search) {
                    $customerQuery->where('name', 'LIKE', "%$search%")
                        ->orWhere('email', 'LIKE', "%$search%");
                })
                    ->orWhereHas('reservable', function ($reservableQuery) use ($search) {
                        $reservableQuery->where('title', 'LIKE', "%$search%")
                            ->orWhere('address', 'LIKE', "%$search%");
                    })
                    ->orWhere('transaction_id', 'LIKE', "%$search%");
            });
        }

        $total = $query->count();
        $reservations = $query->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];

        foreach ($reservations as $reservation) {
            $customer = $reservation->customer;
            $reservable = $reservation->reservable;

            // Get property name and type
            $propertyName = '';
            $propertyType = '';

            if ($reservation->reservable_type === 'App\\Models\\Property') {
                $propertyName = $reservable->title ?? 'N/A';
                $propertyType = 'Vacation Home';
            } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                $propertyName = $reservable->property->title ?? 'N/A';
                $propertyType = 'Hotel Room';
                if ($reservable->roomType) {
                    $propertyName .= ' - ' . $reservable->roomType->name;
                }
            }

            // Status badge
            $statusBadge = $this->getStatusBadge($reservation->status);
            $paymentBadge = $this->getPaymentStatusBadge($reservation->payment_status);

            $rows[] = [
                'id' => $reservation->id,
                'customer_name' => $customer->name ?? 'N/A',
                'customer_email' => $customer->email ?? 'N/A',
                'property_name' => $propertyName,
                'property_type' => $propertyType,
                'check_in_date' => $reservation->check_in_date->format('Y-m-d'),
                'check_out_date' => $reservation->check_out_date->format('Y-m-d'),
                'number_of_guests' => $reservation->number_of_guests,
                'total_price' => '$' . number_format($reservation->total_price, 2),
                'status' => $statusBadge,
                'payment_status' => $paymentBadge,
                'created_at' => $reservation->created_at->format('Y-m-d H:i:s'),
                'actions' => $this->getActionButtons($reservation)
            ];
        }

        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    /**
     * Get status badge HTML.
     *
     * @param string $status
     * @return string
     */
    private function getStatusBadge($status)
    {
        $badges = [
            'pending' => '<span class="badge bg-warning">Pending</span>',
            'confirmed' => '<span class="badge bg-success">Confirmed</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
            'completed' => '<span class="badge bg-info">Completed</span>'
        ];

        return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    /**
     * Get payment status badge HTML.
     *
     * @param string $paymentStatus
     * @return string
     */
    private function getPaymentStatusBadge($paymentStatus)
    {
        $badges = [
            'paid' => '<span class="badge bg-success">Paid</span>',
            'unpaid' => '<span class="badge bg-danger">Unpaid</span>',
            'partial' => '<span class="badge bg-warning">Partial</span>'
        ];

        return $badges[$paymentStatus] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    /**
     * Get action buttons HTML.
     *
     * @param Reservation $reservation
     * @return string
     */
    private function getActionButtons($reservation)
    {
        $buttons = '<div class="btn-group" role="group">';

        // View button
        $buttons .= '<button type="button" class="btn btn-sm btn-outline-primary" onclick="viewReservation(' . $reservation->id . ')" title="View Details">
                        <i class="bi bi-eye"></i>
                    </button>';

        // Status update buttons
        if ($reservation->status === 'pending') {
            $buttons .= '<button type="button" class="btn btn-sm btn-outline-success" onclick="updateStatus(' . $reservation->id . ', \'confirmed\')" title="Confirm">
                            <i class="bi bi-check-circle"></i>
                        </button>';
        }

        if (in_array($reservation->status, ['pending', 'confirmed'])) {
            $buttons .= '<button type="button" class="btn btn-sm btn-outline-danger" onclick="updateStatus(' . $reservation->id . ', \'cancelled\')" title="Cancel">
                            <i class="bi bi-x-circle"></i>
                        </button>';
        }

        $buttons .= '</div>';

        return $buttons;
    }

    /**
     * Update reservation status.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        if (!has_permissions('update', 'reservations')) {
            return response()->json(['error' => PERMISSION_ERROR_MSG], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,approved,confirmed,cancelled,completed',
            'payment_status' => 'nullable|in:paid,unpaid,partial'
        ]);

        $reservation = Reservation::findOrFail($id);
        $oldStatus = $reservation->status;
        $newStatus = $request->status;

        try {
            // If changing from pending to confirmed, use the service method to handle the full confirmation logic
            if ($oldStatus === 'pending' && $newStatus === 'confirmed') {
                $reservationService = app(\App\Services\ReservationService::class);
                $paymentStatus = $request->payment_status ?? 'paid';
                $reservationService->handleReservationConfirmation($reservation, $paymentStatus);

                return response()->json([
                    'success' => true,
                    'message' => 'Reservation confirmed successfully. Available dates updated and confirmation email sent.'
                ]);
            } elseif ($newStatus === 'approved') {
                // Handle approved status - send approval email
                $reservation->status = $newStatus;

                if ($request->has('payment_status')) {
                    $reservation->payment_status = $request->payment_status;
                }

                $reservation->save();

                // Send approval email
                $reservationService = app(\App\Services\ReservationService::class);
                $reservationService->sendReservationApprovalEmail($reservation);

                return response()->json([
                    'success' => true,
                    'message' => 'Reservation approved successfully. Approval email sent to customer.'
                ]);
            } else {
                // For other status changes, use the existing logic
                $reservation->status = $newStatus;

                if ($request->has('payment_status')) {
                    $reservation->payment_status = $request->payment_status;
                }

                $reservation->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Reservation status updated successfully'
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to update reservation status', [
                'reservation_id' => $id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update reservation status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reservation details.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReservationDetails($id)
    {
        $reservation = Reservation::with(['customer', 'reservable'])->findOrFail($id);

        $reservable = $reservation->reservable;
        $propertyName = '';
        $propertyType = '';

        if ($reservation->reservable_type === 'App\\Models\\Property') {
            $propertyName = $reservable->title ?? 'N/A';
            $propertyType = 'Vacation Home';
        } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
            $propertyName = $reservable->property->title ?? 'N/A';
            $propertyType = 'Hotel Room';
            if ($reservable->roomType) {
                $propertyName .= ' - ' . $reservable->roomType->name;
            }
        }

        return response()->json([
            'reservation' => [
                'id' => $reservation->id,
                'customer_name' => $reservation->customer->name ?? 'N/A',
                'customer_email' => $reservation->customer->email ?? 'N/A',
                'customer_phone' => $reservation->customer->mobile ?? 'N/A',
                'property_name' => $propertyName,
                'property_type' => $propertyType,
                'check_in_date' => $reservation->check_in_date->format('Y-m-d'),
                'check_out_date' => $reservation->check_out_date->format('Y-m-d'),
                'number_of_guests' => $reservation->number_of_guests,
                'total_price' => '$' . number_format($reservation->total_price, 2),
                'status' => $reservation->status,
                'payment_status' => $reservation->payment_status,
                'special_requests' => $reservation->special_requests,
                'transaction_id' => $reservation->transaction_id,
                'created_at' => $reservation->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $reservation->updated_at->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Get reservation statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics(Request $request)
    {
        $dateFrom = $request->date_from ?? null;
        $dateTo = $request->date_to ?? null;

        $query = Reservation::query();

        // Apply date filters if provided
        if ($dateFrom) {
            $query->where('check_in_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('check_out_date', '<=', $dateTo);
        }

        $totalReservations = (clone $query)->count();
        $pendingReservations = (clone $query)->where('status', 'pending')->count();
        $confirmedReservations = (clone $query)->where('status', 'confirmed')->count();
        $cancelledReservations = (clone $query)->where('status', 'cancelled')->count();
        $completedReservations = (clone $query)->where('status', 'completed')->count();

        $totalRevenue = (clone $query)->where('payment_status', 'paid')->sum('total_price');
        $unpaidAmount = (clone $query)->where('payment_status', 'unpaid')->sum('total_price');

        $vacationHomeReservations = (clone $query)->where('reservable_type', 'App\\Models\\Property')->count();

        $hotelReservations = (clone $query)->where('reservable_type', 'App\\Models\\HotelRoom')->count();

        return response()->json([
            'total_reservations' => $totalReservations,
            'pending_reservations' => $pendingReservations,
            'confirmed_reservations' => $confirmedReservations,
            'cancelled_reservations' => $cancelledReservations,
            'completed_reservations' => $completedReservations,
            'total_revenue' => '$' . number_format($totalRevenue, 2),
            'unpaid_amount' => '$' . number_format($unpaidAmount, 2),
            'vacation_home_reservations' => $vacationHomeReservations,
            'hotel_reservations' => $hotelReservations
        ]);
    }

    /**
     * Update reservation status via API.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatusApi(Request $request, $id)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,confirmed,cancelled,completed,rejected',
            'payment_status' => 'nullable|in:paid,unpaid,partial',
            'rejection_reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return $this->apiResponseService->errorResponse('Validation failed', $validator->errors());
        }

        $reservation = Reservation::findOrFail($id);

        // // Check if user has permission to update this reservation
        // $user = auth('sanctum')->user();

        // // For regular users, check if they own the property or are the customer
        // $hasPermission = false;

        // if ($user) {
        //     // If user is the customer who made the reservation
        //     if ($reservation->customer_id == $user->id) {
        //         $hasPermission = true;
        //     }
        //     // If user is the property owner (for property reservations)
        //     elseif ($reservation->reservable_type === 'App\\Models\\Property') {
        //         $property = Property::find($reservation->reservable_id);
        //         if ($property && $property->added_by == $user->id) {
        //             $hasPermission = true;
        //         }
        //     }
        //     // If user is the property owner (for hotel room reservations)
        //     elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
        //         $room = HotelRoom::find($reservation->reservable_id);
        //         if ($room && $room->property) {
        //             $property = $room->property;
        //             if ($property->added_by == $user->id) {
        //                 $hasPermission = true;
        //             }
        //         }
        //     }
        // }

        // if (!$hasPermission) {
        //     return $this->apiResponseService->errorResponse('You do not have permission to update this reservation', [], 403);
        // }

        $oldStatus = $reservation->status;
        $newStatus = $request->status;

        try {
            // If changing from pending to confirmed, use the service method to handle the full confirmation logic
            if ($oldStatus === 'pending' && $newStatus === 'confirmed') {
                $reservationService = app(\App\Services\ReservationService::class);
                $paymentStatus = $request->payment_status ?? 'paid';
                $reservationService->handleReservationConfirmation($reservation, $paymentStatus);

                return $this->apiResponseService->successResponse('Reservation confirmed successfully. Available dates updated and confirmation email sent.', [
                    'reservation' => $reservation->fresh()
                ]);
            } elseif ($newStatus === 'approved') {
                // Handle approved status - send approval email with payment link
                $reservation->status = $newStatus;

                if ($request->has('payment_status')) {
                    $reservation->payment_status = $request->payment_status;
                }

                $reservation->save();

                // Generate payment link and send approval email
                $paymentLink = $this->generatePaymentLinkForReservation($reservation);

                if ($paymentLink) {
                    $reservationService = app(\App\Services\ReservationService::class);
                    $reservationService->sendReservationApprovalWithPaymentEmail($reservation, $paymentLink);
                } else {
                    // Fallback to regular approval email if payment link generation fails
                    $reservationService = app(\App\Services\ReservationService::class);
                    $reservationService->sendReservationApprovalEmail($reservation);
                }

                return $this->apiResponseService->successResponse('Reservation approved successfully. Payment link sent to customer. Reservation will be confirmed after payment.', [
                    'reservation' => $reservation->fresh(),
                    'payment_link' => $paymentLink
                ]);
            } elseif ($newStatus === 'cancelled') {
                // Handle cancelled status - send cancellation email
                $reservation->status = $newStatus;

                if ($request->has('payment_status')) {
                    $reservation->payment_status = $request->payment_status;
                }

                $reservation->save();

                // Send cancellation email to the customer
                $this->sendReservationCancellationEmail($reservation);

                return $this->apiResponseService->successResponse('Reservation cancelled successfully. Cancellation email sent to customer.', [
                    'reservation' => $reservation->fresh()
                ]);
            } elseif ($newStatus === 'rejected') {
                // Handle rejected status - send rejection email
                $reservation->status = $newStatus;

                if ($request->has('payment_status')) {
                    $reservation->payment_status = $request->payment_status;
                }

                $reservation->save();

                // Get rejection reason if provided
                $rejectionReason = $request->rejection_reason ?? 'The property is not available for the requested dates.';

                // Send rejection email to the customer
                $this->sendReservationRejectionEmail($reservation, $rejectionReason);

                return $this->apiResponseService->successResponse('Reservation rejected successfully. Rejection email sent to customer.', [
                    'reservation' => $reservation->fresh()
                ]);
            } else {
                // For other status changes, use the existing logic
                $reservation->status = $newStatus;

                if ($request->has('payment_status')) {
                    $reservation->payment_status = $request->payment_status;
                }

                $reservation->save();

                return $this->apiResponseService->successResponse('Reservation status updated successfully', [
                    'reservation' => $reservation->fresh()
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to update reservation status via API', [
                'reservation_id' => $id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->apiResponseService->errorResponse('Failed to update reservation status: ' . $e->getMessage());
        }
    }

    /**
     * Generate payment link for a reservation.
     *
     * @param \App\Models\Reservation $reservation
     * @return string|null
     */
    private function generatePaymentLinkForReservation($reservation)
    {
        try {
            // Get customer information
            $customer = $reservation->customer;
            if (!$customer || !$customer->email) {
                return null;
            }

            // Generate a unique transaction ID
            $transactionId = 'RES_' . time() . '_' . $customer->id . '_' . rand(1000, 9999);

            // Calculate discount
            $discountInfo = $this->calculateCustomerDiscount(
                $customer->id,
                $reservation->reservable_type,
                $reservation->total_price
            );

            // Create payment data
            $paymentData = [
                'payment_method' => 'paymob',
                'paymob_api_key' => config('paymob.api_key'),
                'paymob_integration_id' => config('paymob.integration_id'),
                'paymob_iframe_id' => config('paymob.iframe_id'),
                'paymob_currency' => config('paymob.currency'),
            ];

            $metadata = [
                'email' => $customer->email,
                'first_name' => $customer->name,
                'last_name' => $customer->name, // Use the same name for last_name to avoid blank field
                'phone' => $customer->mobile ?? '1234567890', // Provide default phone if not available
                'payment_transaction_id' => $transactionId,
            ];

            // Create payment service
            $paymentService = \App\Services\Payment\PaymentService::create($paymentData);

            // Create payment intent
            $paymentIntent = $paymentService->createAndFormatPaymentIntent($discountInfo['final_amount'], $metadata);

            // Create or update payment record with the new transaction ID
            $payment = \App\Models\PaymobPayment::updateOrCreate(
                ['reservation_id' => $reservation->id],
                [
                    'customer_id' => $customer->id,
                    'transaction_id' => $transactionId,
                    'amount' => $discountInfo['final_amount'],
                    'currency' => config('paymob.currency', 'EGP'),
                    'status' => 'pending',
                    'payment_method' => 'paymob',
                    'reservable_id' => $reservation->reservable_id,
                    'reservable_type' => $reservation->reservable_type,
                ]
            );

            // Update the payment record with Paymob order ID if available
            if (isset($paymentIntent['id'])) {
                $payment->paymob_order_id = $paymentIntent['id'];
                $payment->save();

                \Illuminate\Support\Facades\Log::info('Payment record created/updated with Paymob order ID', [
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'paymob_order_id' => $payment->paymob_order_id,
                    'reservation_id' => $payment->reservation_id
                ]);
            }

            // Update the reservation with the new transaction ID
            $reservation->transaction_id = $transactionId;
            $reservation->save();

            // Log the payment intent for debugging
            \Illuminate\Support\Facades\Log::info('Payment intent created for reservation', [
                'reservation_id' => $reservation->id,
                'payment_intent' => $paymentIntent,
                'iframe_url' => $paymentIntent['iframe_url'] ?? 'not_found',
                'payment_intent_keys' => array_keys($paymentIntent),
                'full_response' => json_encode($paymentIntent, JSON_PRETTY_PRINT)
            ]);

            // Return the iframe URL from the payment intent
            return $paymentIntent['iframe_url'] ?? null;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to generate payment link for reservation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Calculate customer discount.
     *
     * @param int $customerId
     * @param string $reservableType
     * @param float $originalAmount
     * @return array
     */
    private function calculateCustomerDiscount($customerId, $reservableType, $originalAmount)
    {
        $completedBookings = \App\Models\Reservation::where('customer_id', $customerId)
            ->where('reservable_type', $reservableType)
            ->where('status', 'confirmed')
            ->count();

        $discountPercentage = 0;

        if ($reservableType === 'App\\Models\\Property') {
            if ($completedBookings == 15) {
                $discountPercentage = 10;
            } elseif ($completedBookings == 10) {
                $discountPercentage = 7;
            } elseif ($completedBookings == 5) {
                $discountPercentage = 3;
            }
        } elseif ($reservableType === 'App\\Models\\HotelRoom') {
            if ($completedBookings == 20) {
                $discountPercentage = 5;
            } elseif ($completedBookings == 15) {
                $discountPercentage = 4;
            } elseif ($completedBookings == 10) {
                $discountPercentage = 2;
            }
        }

        $discountAmount = ($originalAmount * $discountPercentage) / 100;
        $finalAmount = $originalAmount - $discountAmount;

        return [
            'original_amount' => $originalAmount,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'completed_bookings' => $completedBookings
        ];
    }

    /**
     * Send reservation rejection email to customer
     *
     * @param \App\Models\Reservation $reservation
     * @param string $rejectionReason
     * @return void
     */
    private function sendReservationRejectionEmail($reservation, $rejectionReason)
    {
        try {
            // Get customer information
            $customer = $reservation->customer;

            if (!$customer || !$customer->email) {
                \Illuminate\Support\Facades\Log::warning('Cannot send reservation rejection email: customer or email not found', [
                    'reservation_id' => $reservation->id,
                    'customer_id' => $reservation->customer_id
                ]);
                return;
            }

            // Get property information
            $propertyName = 'Unknown Property';
            if ($reservation->reservable_type === 'App\\Models\\Property') {
                $property = \App\Models\Property::find($reservation->reservable_id);
                if ($property) {
                    $propertyName = $property->title;
                }
            } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                $hotelRoom = \App\Models\HotelRoom::find($reservation->reservable_id);
                if ($hotelRoom && $hotelRoom->property) {
                    $propertyName = $hotelRoom->property->title;
                }
            }

            // Format dates
            $checkInDate = $reservation->check_in_date ? $reservation->check_in_date->format('Y-m-d') : 'N/A';
            $checkOutDate = $reservation->check_out_date ? $reservation->check_out_date->format('Y-m-d') : 'N/A';

            // Get currency symbol
            $currencySymbol = system_setting('currency_symbol') ?? '$';

            // Prepare email variables
            $variables = [
                'app_name' => env("APP_NAME") ?? "eBroker",
                'customer_name' => $customer->name,
                'reservation_id' => $reservation->id,
                'property_name' => $propertyName,
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'number_of_guests' => $reservation->number_of_guests,
                'total_price' => number_format($reservation->total_price, 2),
                'currency_symbol' => $currencySymbol,
                'rejection_reason' => $rejectionReason,
            ];

            // Get email template
            $emailTemplateData = system_setting('reservation_rejection_mail_template');

            if (empty($emailTemplateData)) {
                \Illuminate\Support\Facades\Log::warning('Reservation rejection email template not found, using default template');
                $emailTemplateData = '<p>Dear {customer_name},</p>

<p>We regret to inform you that your reservation request has been declined.</p>

<p><strong>Reservation Details:</strong></p>
<ul>
<li><strong>Reservation ID:</strong> {reservation_id}</li>
<li><strong>Property:</strong> {property_name}</li>
<li><strong>Check-in Date:</strong> {check_in_date}</li>
<li><strong>Check-out Date:</strong> {check_out_date}</li>
<li><strong>Number of Guests:</strong> {number_of_guests}</li>
<li><strong>Total Amount:</strong> {currency_symbol}{total_price}</li>
</ul>

<p><strong>Reason for Rejection:</strong><br>
{rejection_reason}</p>

<p>We understand this may be disappointing, and we apologize for any inconvenience this may cause. Our team has carefully reviewed your reservation request and unfortunately, we are unable to accommodate it at this time.</p>

<p>If you have any questions or would like to discuss alternative options, please do not hesitate to contact our customer support team.</p>

<p>We value your interest in our properties and hope to have the opportunity to serve you in the future.</p>

<p>Best regards,<br>
The {app_name} Team</p>';
            }

            // Replace variables in template
            $emailContent = \App\Services\HelperService::replaceEmailVariables($emailTemplateData, $variables);

            // Send email
            $data = [
                'email' => $customer->email,
                'title' => 'Your Booking Request has been Declined',
                'email_template' => $emailContent
            ];

            \App\Services\HelperService::sendMail($data);

            \Illuminate\Support\Facades\Log::info('Reservation rejection email sent to customer', [
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
                'reservation_id' => $reservation->id
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send reservation rejection email: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'reservation_id' => $reservation->id
            ]);
        }
    }

    /**
     * Send reservation cancellation email to customer
     *
     * @param \App\Models\Reservation $reservation
     * @return void
     */
    private function sendReservationCancellationEmail($reservation)
    {
        try {
            // Get customer information
            $customer = $reservation->customer;

            if (!$customer || !$customer->email) {
                \Illuminate\Support\Facades\Log::warning('Cannot send reservation cancellation email: customer or email not found', [
                    'reservation_id' => $reservation->id,
                    'customer_id' => $reservation->customer_id
                ]);
                return;
            }

            // Get property information
            $propertyName = 'Unknown Property';
            if ($reservation->reservable_type === 'App\\Models\\Property') {
                $property = \App\Models\Property::find($reservation->reservable_id);
                if ($property) {
                    $propertyName = $property->title;
                }
            } elseif ($reservation->reservable_type === 'App\\Models\\HotelRoom') {
                $hotelRoom = \App\Models\HotelRoom::find($reservation->reservable_id);
                if ($hotelRoom && $hotelRoom->property) {
                    $propertyName = $hotelRoom->property->title;
                }
            }

            // Format dates
            $checkInDate = $reservation->check_in_date ? $reservation->check_in_date->format('Y-m-d') : 'N/A';
            $checkOutDate = $reservation->check_out_date ? $reservation->check_out_date->format('Y-m-d') : 'N/A';

            // Get currency symbol
            $currencySymbol = system_setting('currency_symbol') ?? '$';

            // Prepare email variables
            $variables = [
                'app_name' => env("APP_NAME") ?? "eBroker",
                'customer_name' => $customer->name,
                'reservation_id' => $reservation->id,
                'property_name' => $propertyName,
                'check_in_date' => $checkInDate,
                'check_out_date' => $checkOutDate,
                'total_price' => number_format($reservation->total_price, 2),
                'currency_symbol' => $currencySymbol,
            ];

            // Get email template
            $emailTemplateData = system_setting('reservation_cancellation_mail_template');

            if (empty($emailTemplateData)) {
                \Illuminate\Support\Facades\Log::warning('Reservation cancellation email template not found, using default template');
                $emailTemplateData = 'Dear {customer_name},

We are writing to confirm that your reservation has been cancelled.

Reservation Details:
- Reservation ID: {reservation_id}
- Property: {property_name}
- Check-in Date: {check_in_date}
- Check-out Date: {check_out_date}
- Total Amount: {currency_symbol}{total_price}

If you requested a refund, please note that it will be processed according to our refund policy. Depending on your payment method, it may take 3-5 business days for the refund to appear in your account.

If you did not request this cancellation or have any questions, please contact our customer support team immediately.

Thank you for your understanding.

Best regards,
The {app_name} Team';
            }

            // Replace variables in template
            $emailContent = \App\Services\HelperService::replaceEmailVariables($emailTemplateData, $variables);

            // Send email
            $data = [
                'email' => $customer->email,
                'title' => 'Your Reservation Has Been Cancelled',
                'email_template' => $emailContent
            ];

            \App\Services\HelperService::sendMail($data);

            \Illuminate\Support\Facades\Log::info('Reservation cancellation email sent to customer', [
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
                'reservation_id' => $reservation->id
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send reservation cancellation email: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'reservation_id' => $reservation->id
            ]);
        }
    }
}
