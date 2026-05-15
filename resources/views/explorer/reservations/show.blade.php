@section('explorer_nav_active', 'rsv')
@extends('layouts.explorer')

@section('title', 'Booking Detail — Ashome')

@php
    $reservation = $reservation ?? null;
    $property    = optional($reservation)->property;
    $customer    = optional($reservation)->customer;

    $checkIn     = $reservation ? \Carbon\Carbon::parse($reservation->check_in_date)  : null;
    $checkOut    = $reservation ? \Carbon\Carbon::parse($reservation->check_out_date) : null;
    $nights      = ($checkIn && $checkOut) ? $checkIn->diffInDays($checkOut) : 0;

    $statusColor = match(optional($reservation)->status) {
        'confirmed'  => '#3E8C8A',
        'pending'    => '#C8A557',
        'cancelled'  => '#C26A6A',
        default      => '#9AA0A6',
    };
    $statusLabel = match(optional($reservation)->status) {
        'confirmed'  => 'Confirmed',
        'pending'    => 'Awaiting host approval',
        'cancelled'  => 'Cancelled',
        default      => ucfirst(optional($reservation)->status ?? ''),
    };

    $baseRate      = optional($property)->price ?? 0;
    $baseTotal     = $baseRate * $nights;
    $cleaningFee   = $reservation->cleaning_fee   ?? 0;  // column doesn't exist yet; ?? 0
    $serviceFee    = $reservation->service_fee    ?? 0;  // column doesn't exist yet; ?? 0
    $totalAmount   = $reservation->total_price    ?? ($baseTotal + $cleaningFee + $serviceFee);

    // Guest information fields
    $guestName    = optional($customer)->name ?? $reservation->customer_name ?? '—';
    $guestNation  = $reservation->nationality ?? '—';               // nationality lives on Reservation
    $guestCountry = optional($customer)->country ?? '—';
    $guestPhone   = optional($customer)->mobile ?? '—';             // Customer uses ->mobile not ->phone
    $guestEmail   = optional($customer)->email ?? $reservation->customer_email ?? '—';
    $guestPassport = optional($customer)->passport_number ?? '—';  // not yet on Customer model; ?? '—'

    // Property image — title_image accessor already returns a full URL
    $heroImg = optional($property)->title_image ?: null;

    // Loyalty
    $loyaltyTier      = optional($customer)->loyalty_tier ?? null;
    $loyaltyStays     = optional($customer)->hotel_stays_count ?? 0;
    $loyaltyTarget    = 20;
    $loyaltyProgress  = $loyaltyTarget > 0 ? min(100, round(($loyaltyStays / $loyaltyTarget) * 100)) : 0;
@endphp

@section('css')
<style>
/* ── Booking detail page ────────────────────────────────────── */
.bkd-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 64px;
    align-items: start;
}

/* ── Left column ────────────────────────────────────────────── */
.bkd-back {
    font-size: 12px;
    color: #5A5C63;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 24px;
}
.bkd-back:hover { color: #1A1B1F; }

.bkd-status-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 6px;
}
.bkd-status-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.bkd-status-label { font-size: 11px; font-weight: 600; }

.bkd-conf {
    font-size: 11px;
    color: #9AA0A6;
    letter-spacing: .15em;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 6px;
}
.bkd-title {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 48px;
    font-weight: 500;
    color: #1A1B1F;
    line-height: 1.05;
    letter-spacing: -.01em;
}
.bkd-subtitle {
    font-size: 15px;
    color: #5A5C63;
    margin-top: 8px;
}
.bkd-hero {
    margin-top: 32px;
    height: 280px;
    border-radius: 18px;
    overflow: hidden;
    background: #E4DDD0;
}
.bkd-hero img { width: 100%; height: 100%; object-fit: cover; }

/* ── Stay details grid ──────────────────────────────────────── */
.bkd-stay-grid {
    margin-top: 36px;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 24px;
    padding-top: 28px;
    border-top: 1px solid #EAE7E0;
}
.bkd-stay-item {}
.bkd-stay-display {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 24px;
    font-weight: 500;
    color: #1A1B1F;
    line-height: 1.1;
    letter-spacing: -.01em;
    margin-top: 4px;
}
.bkd-stay-note { font-size: 12px; color: #5A5C63; margin-top: 4px; }

/* ── Guest info ─────────────────────────────────────────────── */
.bkd-guest-section {
    margin-top: 32px;
    padding-top: 28px;
    border-top: 1px solid #EAE7E0;
}
.bkd-guest-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 14px;
}
.bkd-editable-note { font-size: 11px; color: #9AA0A6; }
.bkd-guest-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
.bkd-guest-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 12px 14px;
    border: 1px solid #EAE7E0;
    border-radius: 12px;
    background: #FFFEFA;
    cursor: text;
}
.bkd-field-label {
    font-size: 10px;
    color: #9AA0A6;
    text-transform: uppercase;
    letter-spacing: .15em;
    font-weight: 600;
}
.bkd-field-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}
.bkd-field-value { font-size: 14px; color: #1A1B1F; font-weight: 500; }
.bkd-guest-actions { display: flex; gap: 8px; margin-top: 14px; }

/* ── Buttons ────────────────────────────────────────────────── */
.bkd-btn-primary {
    background: #0B1628;
    color: #FFFEFA;
    border: none;
    padding: 10px 16px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    font-family: inherit;
    text-decoration: none;
}
.bkd-btn-ghost {
    background: transparent;
    color: #1A1B1F;
    border: 1px solid #EAE7E0;
    padding: 10px 16px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    font-family: inherit;
    text-decoration: none;
}

/* ── Right: price card ──────────────────────────────────────── */
.bkd-aside { }
.bkd-price-card {
    position: sticky;
    top: 24px;
    background: #FFFEFA;
    border: 1px solid #EAE7E0;
    border-radius: 20px;
    padding: 24px 26px;
}
.bkd-price-total {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 32px;
    font-weight: 500;
    color: #1A1B1F;
    line-height: 1.1;
    letter-spacing: -.01em;
    margin: 6px 0 4px;
}
.bkd-paid-note { font-size: 12px; color: #5A5C63; }
.bkd-price-breakdown {
    margin-top: 18px;
    padding-top: 18px;
    border-top: 1px solid #EAE7E0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.bkd-price-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #5A5C63;
}
.bkd-price-row span:last-child { font-variant-numeric: tabular-nums; }
.bkd-card-actions {
    margin-top: 24px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.bkd-btn-card-primary {
    background: #0B1628;
    color: #FFFEFA;
    border: none;
    padding: 12px 16px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    font-family: inherit;
    text-align: center;
}
.bkd-btn-card-ghost {
    background: transparent;
    color: #1A1B1F;
    border: 1px solid #EAE7E0;
    padding: 12px 16px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    font-family: inherit;
    text-align: center;
}
.bkd-btn-cancel {
    background: transparent;
    color: #C26A6A;
    border: none;
    padding: 6px 16px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    font-family: inherit;
    text-align: center;
}

/* ── Loyalty card ───────────────────────────────────────────── */
.bkd-loyalty {
    margin-top: 24px;
    padding: 22px 24px;
    background: #0B1628;
    color: #FFFEFA;
    border-radius: 20px;
}
.bkd-loyalty-tier {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: .18em;
    text-transform: uppercase;
    color: #C8A557;
}
.bkd-loyalty-display {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 30px;
    font-weight: 400;
    font-style: italic;
    color: #FFFEFA;
    margin-top: 8px;
    line-height: 1.1;
}
.bkd-loyalty-bar-track {
    height: 2px;
    background: rgba(255,255,255,.18);
    margin-top: 14px;
    border-radius: 1px;
    position: relative;
}
.bkd-loyalty-bar-fill {
    position: absolute;
    inset: 0;
    background: #C8A557;
    border-radius: 1px;
}
.bkd-loyalty-meta {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: rgba(255,255,255,.65);
    margin-top: 8px;
}
</style>
@endsection

@section('content')

@if(!$reservation)
    <div style="text-align:center;padding:80px 0;color:#9AA0A6;">
        <p>Reservation not found.</p>
        <a href="#" style="color:#4A7FA8;font-size:13px;">← Back to reservations</a>
    </div>
@else

<div class="bkd-grid">

    {{-- ── Left column ─────────────────────────────────────── --}}
    <div>
        <a href="#" class="bkd-back">← All reservations</a>

        <div class="bkd-status-row">
            <span class="bkd-status-dot" style="background:{{ $statusColor }};"></span>
            <span class="bkd-status-label" style="color:{{ $statusColor }};">{{ $statusLabel }}</span>
        </div>
        <div class="bkd-conf">Confirmation {{ $reservation->id }}</div>
        <div class="bkd-title">{{ optional($property)->name ?? 'Property' }}</div>
        <p class="bkd-subtitle">
            {{ optional($property)->type ?? '' }}
            @if(optional($property)->meal_plan) · {{ $property->meal_plan }} @endif
        </p>

        {{-- Property photo --}}
        <div class="bkd-hero">
            @if($heroImg)
                <img src="{{ $heroImg }}" alt="{{ optional($property)->name }}">
            @endif
        </div>

        {{-- Stay details --}}
        <div class="bkd-stay-grid">
            <div class="bkd-stay-item">
                <div class="exp-label">Check-in</div>
                <div class="bkd-stay-display">
                    {{ $checkIn ? $checkIn->format('D, M j') : '—' }}
                </div>
                <div class="bkd-stay-note">
                    @if(optional($property)->check_in_time)
                        After {{ $property->check_in_time }}
                    @else
                        After 3:00 pm
                    @endif
                </div>
            </div>
            <div class="bkd-stay-item">
                <div class="exp-label">Check-out</div>
                <div class="bkd-stay-display">
                    {{ $checkOut ? $checkOut->format('D, M j') : '—' }}
                </div>
                <div class="bkd-stay-note">
                    @if(optional($property)->check_out_time)
                        Before {{ $property->check_out_time }}
                    @else
                        Before 12:00 pm
                    @endif
                </div>
            </div>
            <div class="bkd-stay-item">
                <div class="exp-label">Guests</div>
                <div class="bkd-stay-display">
                    {{ $reservation->number_of_guests ?? 1 }}
                    {{ ($reservation->number_of_guests ?? 1) === 1 ? 'adult' : 'adults' }}
                </div>
                <div class="bkd-stay-note">{{ $guestName }}</div>
            </div>
        </div>

        {{-- Guest information --}}
        <div class="bkd-guest-section">
            <div class="bkd-guest-header">
                <div class="exp-label">Guest information</div>
                <span class="bkd-editable-note">Editable until 24 h before check-in</span>
            </div>

            <div class="bkd-guest-grid">
                @php
                    $fields = [
                        ['label' => 'Lead guest',        'value' => $guestName,     'type' => 'text'],
                        ['label' => 'Nationality',       'value' => $guestNation,   'type' => 'select'],
                        ['label' => 'Country of origin', 'value' => $guestCountry,  'type' => 'select'],
                        ['label' => 'Phone',             'value' => $guestPhone,    'type' => 'text'],
                        ['label' => 'Email',             'value' => $guestEmail,    'type' => 'text'],
                        ['label' => 'Passport / ID',     'value' => $guestPassport, 'type' => 'text'],
                    ];
                @endphp
                @foreach($fields as $field)
                <label class="bkd-guest-field">
                    <span class="bkd-field-label">{{ $field['label'] }}</span>
                    <span class="bkd-field-row">
                        <span class="bkd-field-value">{{ $field['value'] }}</span>
                        @if($field['type'] === 'select')
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9AA0A6" stroke-width="1.6" style="flex-shrink:0;">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        @else
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9AA0A6" stroke-width="1.6" style="flex-shrink:0;">
                                <path d="M12 20h9M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4z"/>
                            </svg>
                        @endif
                    </span>
                </label>
                @endforeach
            </div>

            <div class="bkd-guest-actions">
                <button class="bkd-btn-primary" type="button">Save changes</button>
                <button class="bkd-btn-ghost" type="button">Add another guest</button>
            </div>
        </div>
    </div>

    {{-- ── Right column ─────────────────────────────────────── --}}
    <aside class="bkd-aside">

        {{-- Price breakdown card --}}
        <div class="bkd-price-card">
            <div class="exp-label">Price breakdown</div>
            <div class="bkd-price-total">EGP {{ number_format($totalAmount) }}</div>
            @if($reservation->paid_at ?? false)
                <div class="bkd-paid-note">
                    Paid in full on {{ \Carbon\Carbon::parse($reservation->paid_at)->format('M j, Y') }}
                </div>
            @else
                <div class="bkd-paid-note">Payment pending</div>
            @endif

            <div class="bkd-price-breakdown">
                @if($baseRate && $nights)
                <div class="bkd-price-row">
                    <span>EGP {{ number_format($baseRate) }} × {{ $nights }} night{{ $nights != 1 ? 's' : '' }}</span>
                    <span>EGP {{ number_format($baseTotal) }}</span>
                </div>
                @endif
                @if($cleaningFee)
                <div class="bkd-price-row">
                    <span>Cleaning fee</span>
                    <span>EGP {{ number_format($cleaningFee) }}</span>
                </div>
                @endif
                @if($serviceFee)
                <div class="bkd-price-row">
                    <span>Service fee</span>
                    <span>EGP {{ number_format($serviceFee) }}</span>
                </div>
                @endif
            </div>

            <div class="bkd-card-actions">
                <button class="bkd-btn-card-primary" type="button">Message host</button>
                <button class="bkd-btn-card-ghost" type="button">Modify stay</button>
                <button class="bkd-btn-cancel" type="button">Cancel reservation</button>
            </div>
        </div>

        {{-- Loyalty teaser --}}
        <div class="bkd-loyalty">
            <div class="bkd-loyalty-tier">
                <span>☾</span>
                {{ $loyaltyTier ? ucfirst($loyaltyTier) . ' tier' : 'Moon tier' }} · Hotels
            </div>
            <div class="bkd-loyalty-display">You're at 5% off</div>
            <div class="bkd-loyalty-bar-track">
                <div class="bkd-loyalty-bar-fill" style="width:{{ $loyaltyProgress }}%;"></div>
            </div>
            <div class="bkd-loyalty-meta">
                <span>{{ $loyaltyStays }} / {{ $loyaltyTarget }} hotel stays</span>
                <span>{{ max(0, $loyaltyTarget - $loyaltyStays) }} more for 10% off</span>
            </div>
        </div>

    </aside>

</div>

@endif

@endsection
