<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Property;
use App\Models\HotelRoom;
use App\Models\Customer;
use App\Services\BootstrapTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationsAdminController extends Controller
{
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
            'status' => 'required|in:pending,confirmed,cancelled,completed',
            'payment_status' => 'nullable|in:paid,unpaid,partial'
        ]);

        $reservation = Reservation::findOrFail($id);
        $reservation->status = $request->status;

        if ($request->has('payment_status')) {
            $reservation->payment_status = $request->payment_status;
        }

        $reservation->save();

        return response()->json([
            'success' => true,
            'message' => 'Reservation status updated successfully'
        ]);
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
}
