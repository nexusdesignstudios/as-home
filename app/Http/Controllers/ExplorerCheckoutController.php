<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExplorerCheckoutController extends Controller
{
    /**
     * Show the checkout page.
     *
     * GET /checkout?property_id=X&check_in=Y&check_out=Z&guests=N[&step=review]
     *
     * Passing ?step=review forces the session back to review stage
     * (used by the "← Back" link on the payment step).
     */
    public function show(Request $request)
    {
        // Allow the "back" link to reset the step
        if ($request->query('step') === 'review') {
            session()->forget('checkout_step');
        }

        $propertyId = $request->query('property_id');
        if (!$propertyId) {
            return redirect()->route('explorer.home');
        }

        $property = Property::findOrFail($propertyId);

        $checkIn  = $request->query('check_in',  '');
        $checkOut = $request->query('check_out', '');
        $guests   = max(1, (int) $request->query('guests', 2));

        $nights = 0;
        if ($checkIn && $checkOut) {
            try {
                $nights = max(0, Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut)));
            } catch (\Exception $e) {
                $nights = 0;
            }
        }

        $isHotel  = $property->property_classification == 5;
        // Hotels require approval unless instant_booking is enabled
        $isPending = $isHotel && !$property->instant_booking;
        $total     = ($property->price ?? 0) * max($nights, 1);

        $reservationData = [
            'property_id' => $propertyId,
            'check_in'    => $checkIn,
            'check_out'   => $checkOut,
            'guests'      => $guests,
        ];

        $step        = session('checkout_step', 'review');
        $reservation = null;

        return view('explorer.checkout.show', compact(
            'property', 'reservationData', 'step', 'nights', 'total',
            'isHotel', 'isPending', 'reservation'
        ));
    }

    /**
     * Handle form submission for review and payment steps.
     *
     * POST /checkout
     */
    public function store(Request $request)
    {
        $step = $request->input('step', 'review');

        // ── Step 1: Review — validate guest info, store in session ────────
        if ($step === 'review') {
            $request->validate([
                'first_name'  => 'required|string|max:100',
                'last_name'   => 'required|string|max:100',
                'email'       => 'required|email|max:200',
                'phone'       => 'required|string|max:30',
                'nationality' => 'required|string|max:100',
            ]);

            session([
                'checkout_guest' => $request->only(
                    'first_name', 'last_name', 'email', 'phone',
                    'nationality', 'booking_from', 'special_requests'
                ),
                'checkout_trip' => $request->only(
                    'property_id', 'check_in', 'check_out', 'guests', 'rate_type'
                ),
                'checkout_step' => 'payment',
            ]);

            return redirect()->route('checkout.show', [
                'property_id' => $request->input('property_id'),
                'check_in'    => $request->input('check_in'),
                'check_out'   => $request->input('check_out'),
                'guests'      => $request->input('guests'),
            ]);
        }

        // ── Step 2: Payment — create Reservation, redirect to confirmation ─
        if ($step === 'payment') {
            $request->validate([
                'payment_method' => 'required|string|in:card,instapay,valu,vodafone',
            ]);

            $guest = session('checkout_guest', []);
            $trip  = session('checkout_trip',  []);

            $propertyId = $trip['property_id'] ?? $request->input('property_id');
            $property   = Property::findOrFail($propertyId);

            $checkIn  = $trip['check_in']  ?? '';
            $checkOut = $trip['check_out'] ?? '';
            $guests   = max(1, (int) ($trip['guests'] ?? 1));

            $nights = 1;
            if ($checkIn && $checkOut) {
                try {
                    $nights = max(1, Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut)));
                } catch (\Exception $e) {
                    $nights = 1;
                }
            }

            $isHotel  = $property->property_classification == 5;
            $isPending = $isHotel && !$property->instant_booking;
            $total    = ($property->price ?? 0) * $nights;

            // TODO: trigger Paymob payment flow before creating the reservation
            $reservation = Reservation::create([
                'customer_id'      => auth()->id() ?? null,
                'customer_name'    => trim(($guest['first_name'] ?? '') . ' ' . ($guest['last_name'] ?? '')),
                'first_name'       => $guest['first_name']  ?? '',
                'last_name'        => $guest['last_name']   ?? '',
                'nationality'      => $guest['nationality'] ?? '',
                'customer_phone'   => $guest['phone']       ?? '',
                'customer_email'   => $guest['email']       ?? '',
                'check_in_date'    => $checkIn  ?: null,
                'check_out_date'   => $checkOut ?: null,
                'number_of_guests' => $guests,
                'total_price'      => $total,
                'status'           => 'pending',
                'payment_status'   => 'pending',
                'payment_method'   => $request->input('payment_method'),
                'property_id'      => $property->id,
                'reservable_id'    => $property->id,
                'reservable_type'  => 'App\\Models\\Property',
                'requires_approval' => $isPending,
                'booking_date'     => now(),
                'booking_source'   => 'web',
                'special_requests' => $guest['special_requests'] ?? null,
            ]);

            session()->forget(['checkout_guest', 'checkout_trip', 'checkout_step']);

            return redirect()->route('checkout.confirm', ['reservation_id' => $reservation->id]);
        }

        return redirect()->route('explorer.home');
    }

    /**
     * Show the booking confirmation page.
     *
     * GET /checkout/confirm?reservation_id=X
     */
    public function confirm(Request $request)
    {
        $reservation = Reservation::with('property')->findOrFail($request->query('reservation_id'));

        $property  = $reservation->property;
        $isHotel   = optional($property)->property_classification == 5;
        $isPending = (bool) $reservation->requires_approval;

        $checkIn  = $reservation->check_in_date  ? $reservation->check_in_date->format('Y-m-d')  : '';
        $checkOut = $reservation->check_out_date ? $reservation->check_out_date->format('Y-m-d') : '';
        $guests   = $reservation->number_of_guests ?? 1;

        $nights = 0;
        if ($checkIn && $checkOut) {
            try {
                $nights = max(0, Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut)));
            } catch (\Exception $e) {
                $nights = 0;
            }
        }

        $total = $reservation->total_price ?? 0;

        $reservationData = [
            'property_id' => optional($property)->id,
            'check_in'    => $checkIn,
            'check_out'   => $checkOut,
            'guests'      => $guests,
        ];

        $step = 'done';

        return view('explorer.checkout.show', compact(
            'property', 'reservation', 'reservationData', 'step',
            'nights', 'total', 'isHotel', 'isPending'
        ));
    }
}
