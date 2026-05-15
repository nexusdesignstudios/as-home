<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExplorerReservationController extends Controller
{
    /**
     * Guest reservations list — Upcoming / Past / Cancelled tabs.
     *
     * NOTE: This controller uses auth()->id() as customer_id.
     * The app currently has no customer web-session guard; only the admin
     * web guard (User model) exists in config/auth.php.  When a customer
     * web guard is added, replace auth()->id() with auth('customer')->id()
     * and update the route middleware to ['auth:customer'] accordingly.
     */
    public function index(Request $request)
    {
        $customerId = auth()->id();
        $tab        = $request->query('tab', 'upcoming');
        $today      = Carbon::today();

        // Single query; filter in memory to avoid three DB round-trips
        $all = Reservation::where('customer_id', $customerId)
            ->with(['property'])
            ->orderBy('check_in_date', 'desc')
            ->get();

        $upcomingCount  = $all->filter(fn($r) => $r->status !== 'cancelled'
            && optional($r->check_out_date)->gte($today))->count();

        $pastCount = $all->filter(fn($r) => $r->status !== 'cancelled'
            && optional($r->check_out_date)->lt($today))->count();

        $cancelledCount = $all->where('status', 'cancelled')->count();

        $reservations = match ($tab) {
            'past'      => $all->filter(fn($r) => $r->status !== 'cancelled'
                                && optional($r->check_out_date)->lt($today))->values(),
            'cancelled' => $all->where('status', 'cancelled')->values(),
            default     => $all->filter(fn($r) => $r->status !== 'cancelled'
                                && optional($r->check_out_date)->gte($today))->values(),
        };

        return view('explorer.reservations.index', [
            'reservations'   => $reservations,
            'upcomingCount'  => $upcomingCount,
            'pastCount'      => $pastCount,
            'cancelledCount' => $cancelledCount,
            'savedCount'     => 0,
        ]);
    }

    /**
     * Single booking detail page.
     */
    public function show($id)
    {
        $reservation = Reservation::where('customer_id', auth()->id())
            ->with(['property', 'customer'])
            ->findOrFail($id);

        // Belt-and-suspenders ownership check
        if ((int) $reservation->customer_id !== (int) auth()->id()) {
            abort(403);
        }

        return view('explorer.reservations.show', compact('reservation'));
    }
}
