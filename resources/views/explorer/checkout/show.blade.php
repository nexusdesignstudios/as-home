@extends('layouts.checkout')

@php
    $isHotel        = $isHotel  ?? false;
    $isPending      = $isPending ?? false;
    $step           = $step ?? 'review';
    $reservation    = $reservation ?? null;
    $nights         = $nights ?? 0;
    $total          = $total ?? 0;
    $reservationData = $reservationData ?? [];

    $propertyId  = $reservationData['property_id'] ?? (isset($property) ? $property->id : null);
    $checkIn     = $reservationData['check_in']  ?? '';
    $checkOut    = $reservationData['check_out'] ?? '';
    $guests      = $reservationData['guests']    ?? 2;
    $basePrice   = isset($property) ? ($property->price ?? 0) : 0;
    $nonRefundPrice = round($basePrice * 0.88);

    // Formatted dates for display
    $checkInFormatted  = $checkIn  ? \Carbon\Carbon::parse($checkIn)->format('D, M j')  : 'Add date';
    $checkOutFormatted = $checkOut ? \Carbon\Carbon::parse($checkOut)->format('D, M j') : 'Add date';

    // Cancellation deadline (7 days before check-in)
    $cancelDeadline = $checkIn
        ? \Carbon\Carbon::parse($checkIn)->subDays(7)->format('D, M j')
        : 'TBD';

    // Hero image with fallback per vertical
    $heroFallback = $isHotel
        ? 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=600&q=70&auto=format'
        : 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=600&q=70&auto=format';

    $img = isset($property) ? ($property->title_image ?? '') : '';
    if (empty($img) || str_starts_with($img, 'data:image/svg')) {
        $img = $heroFallback;
    }

    $typeLabel = $isHotel ? 'Hotel' : 'Vacation Home';

    // Confirmation data (step='done')
    $confirmNum  = $reservation ? 'AH-' . str_pad($reservation->id, 5, '0', STR_PAD_LEFT) : 'AH-00000';
    $guestFirst  = $reservation ? ($reservation->first_name  ?? explode(' ', $reservation->customer_name ?? '')[0]) : '';
    $guestEmail  = $reservation ? ($reservation->customer_email ?? '') : '';
    $propCity    = isset($property) ? ($property->city ?? $property->address ?? 'your destination') : 'your destination';

    // Check-in/out display for ticket
    $ticketCheckIn  = $checkIn  ? \Carbon\Carbon::parse($checkIn)->format('D, M j')  : '—';
    $ticketCheckOut = $checkOut ? \Carbon\Carbon::parse($checkOut)->format('D, M j') : '—';
    $propCheckInTime  = isset($property) ? ($property->check_in  ?? 'After 3:00 PM')  : 'After 3:00 PM';
    $propCheckOutTime = isset($property) ? ($property->check_out ?? 'By 11:00 AM') : 'By 11:00 AM';
@endphp

@section('title', 'Checkout' . (isset($property) ? ' — ' . $property->title : ''))

@section('content')

{{-- ================================================================ --}}
{{-- STEP: DONE — Confirmation                                         --}}
{{-- ================================================================ --}}
@if($step === 'done')

    @if($isPending)
    <div style="max-width:780px; margin:20px auto 0; padding:14px 18px; background:#FFE9B8; border-radius:12px; display:flex; gap:14px; align-items:center;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#7A5500" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l3 2"/></svg>
        <div>
            <div style="font-size:13.5px; font-weight:700; color:#7A5500;">Awaiting host confirmation · payment link coming via email</div>
            <div style="font-size:12px; color:#7A5500; opacity:.85; margin-top:2px;">Most hosts respond within 4 hours. You won't be charged until you complete payment from the link we send.</div>
        </div>
    </div>
    @endif

    <div class="confirm-view">

        {{-- Confetti burst --}}
        <div class="confirm-burst">
            <svg viewBox="0 0 800 240" style="width:100%; height:240px; position:absolute; inset:0; pointer-events:none;">
                @php
                    $confettiColors = ['#D4A843','#0B1628','#0C447C','#E66B5C','#3FA459'];
                    for ($ci = 0; $ci < 55; $ci++) {
                        $cx   = ($ci * 53) % 800;
                        $cy   = (($ci * 73) % 200) + 20;
                        $col  = $confettiColors[$ci % count($confettiColors)];
                        $rot  = ($ci * 23) % 360;
                        $w    = $ci % 3 === 0 ? 12 : 7;
                        $h    = $ci % 3 === 0 ? 5 : 7;
                        echo "<rect x=\"{$cx}\" y=\"{$cy}\" width=\"{$w}\" height=\"{$h}\" fill=\"{$col}\" transform=\"rotate({$rot} ".($cx+3)." ".($cy+3).")\" rx=\"1\"/>";
                    }
                @endphp
            </svg>
            <div class="confirm-check">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12l5 5L20 7"/>
                </svg>
            </div>
        </div>

        <div class="confirm-content">
            <div class="confirm-id">
                {{ $isPending ? 'Request sent' : 'Confirmation' }} · {{ $confirmNum }}
            </div>
            <h1>
                @if($isPending)
                    Your request is on its way.
                @else
                    {{ $guestFirst ? "You're going to {$propCity}, {$guestFirst}." : "Booking confirmed." }}
                @endif
            </h1>
            <p class="confirm-sub">
                @if($isPending)
                    The host will confirm your request (usually within 4 hours).
                    @if($guestEmail)
                        Once confirmed, we'll send a secure payment link to <strong>{{ $guestEmail }}</strong> — you won't be charged until then.
                    @endif
                @else
                    @if($guestEmail)
                        We've sent your itinerary to <strong>{{ $guestEmail }}</strong>.
                    @endif
                    Your host has been notified.
                @endif
            </p>

            <div class="confirm-grid">

                {{-- Ticket --}}
                <div class="ticket">
                    <div class="ticket-img">
                        <img src="{{ $img }}" alt="{{ isset($property) ? $property->title : '' }}">
                        <div class="ticket-overlay">
                            <div class="t-cat">{{ $typeLabel }}</div>
                            <div class="t-name">{{ isset($property) ? $property->title : '' }}</div>
                        </div>
                    </div>
                    <div class="ticket-perf"><span></span><span></span></div>
                    <div class="ticket-body">
                        <div>
                            <div class="k">Check-in</div>
                            <div class="v">{{ $ticketCheckIn }}</div>
                            <div class="t">{{ $propCheckInTime }}</div>
                        </div>
                        <div class="div"></div>
                        <div>
                            <div class="k">Check-out</div>
                            <div class="v">{{ $ticketCheckOut }}</div>
                            <div class="t">{{ $propCheckOutTime }}</div>
                        </div>
                        <div class="div"></div>
                        <div>
                            <div class="k">{{ $isPending ? 'Estimated' : 'Total paid' }}</div>
                            <div class="v">EGP {{ $total ? number_format($total) : '—' }}</div>
                            <div class="t">{{ $reservation && $reservation->payment_method ? ucfirst($reservation->payment_method) : 'Pending' }}</div>
                        </div>
                    </div>
                </div>

                {{-- Next up --}}
                <div class="next-up">
                    <div class="nu-title">Next up</div>
                    @php
                        $nuItems = [
                            ['ico' => '📍', 'l' => 'Get directions to your stay',    's' => isset($property) ? ($property->address ?? $property->city ?? '') : ''],
                            ['ico' => '💬', 'l' => 'Message your host',               's' => 'Reach out with any questions'],
                            ['ico' => '📅', 'l' => 'Add to calendar',                 's' => 'Apple Calendar · Google · Outlook'],
                            ['ico' => '📥', 'l' => 'Download receipt',                's' => 'PDF — for records or reimbursement'],
                        ];
                    @endphp
                    @foreach($nuItems as $nu)
                    <div class="nu-row">
                        <span class="nu-ico">{{ $nu['ico'] }}</span>
                        <div class="nu-meta">
                            <strong>{{ $nu['l'] }}</strong>
                            <span>{{ $nu['s'] }}</span>
                        </div>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="confirm-actions">
                <a href="{{ route('explorer.home') }}" class="ghost">Plan another trip</a>
                <a href="{{ route('explorer.reservations.index') }}" class="primary-cta">View my bookings →</a>
            </div>
        </div>

    </div>

{{-- ================================================================ --}}
{{-- STEPS: REVIEW + PAYMENT — Two-column layout                      --}}
{{-- ================================================================ --}}
@else

<div class="checkout-body">

    {{-- ── LEFT: main form ──────────────────────────────────────── --}}
    <main class="checkout-main">

        {{-- ============================================================ --}}
        {{-- STEP: REVIEW                                                  --}}
        {{-- ============================================================ --}}
        @if($step === 'review')

        <h1>Confirm your trip</h1>
        <p class="lead">A few details and you're set. We'll hold the dates for 15 minutes.</p>

        <form action="{{ route('checkout.store') }}" method="POST" novalidate>
            @csrf
            <input type="hidden" name="step"        value="review">
            <input type="hidden" name="property_id" value="{{ $propertyId }}">
            <input type="hidden" name="check_in"    value="{{ $checkIn }}">
            <input type="hidden" name="check_out"   value="{{ $checkOut }}">
            <input type="hidden" name="guests"      value="{{ $guests }}">

            @if($errors->any())
            <div style="background:#FEE2E2; border:1px solid #FCA5A5; border-radius:10px; padding:14px 18px; margin-bottom:16px; font-size:13px; color:#991B1B;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
            @endif

            {{-- Section 1 — Your trip --}}
            <section class="step-section">
                <div class="sec-head">
                    <h3><span class="step-num">1</span>Your trip</h3>
                    <a href="{{ route('listing.show', $propertyId) }}" class="edit-btn">Edit</a>
                </div>
                <div class="trip-grid">
                    <div class="trip-cell">
                        <div class="k">Check-in</div>
                        <div class="v">{{ $checkInFormatted }}</div>
                    </div>
                    <div class="trip-cell">
                        <div class="k">Check-out</div>
                        <div class="v">{{ $checkOutFormatted }}</div>
                    </div>
                    <div class="trip-cell">
                        <div class="k">Guests</div>
                        <div class="v">{{ $guests }} {{ $guests == 1 ? 'adult' : 'adults' }}</div>
                    </div>
                    <div class="trip-cell">
                        <div class="k">Nights</div>
                        <div class="v">
                            @if($nights > 0){{ $nights }} {{ $nights == 1 ? 'night' : 'nights' }}@else TBD @endif
                        </div>
                    </div>
                </div>
            </section>

            @if($isHotel)
            {{-- Section 2 — Choose your rate (hotels only) --}}
            <section class="step-section">
                <div class="sec-head">
                    <h3><span class="step-num">2</span>Choose your rate</h3>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:4px;">

                    {{-- Flexible --}}
                    <label style="padding:16px 18px; border:2px solid var(--ash-navy-900); border-radius:14px; background:#FFF8E6; cursor:pointer; position:relative; display:block;">
                        <input type="radio" name="rate_type" value="flexible" checked style="position:absolute; opacity:0;">
                        <div style="position:absolute; top:14px; right:14px; width:22px; height:22px; border-radius:50%; background:var(--ash-navy-900); color:#D4A843; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700;">✓</div>
                        <div style="font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#8A6520;">Flexible</div>
                        <div style="font-size:22px; font-weight:700; color:var(--ash-navy-900); margin-top:6px; font-family:var(--ash-font-serif);">
                            EGP {{ $basePrice ? number_format($basePrice) : '—' }}
                            <span style="font-size:12px; font-weight:500; opacity:.65;">/ night</span>
                        </div>
                        <div style="font-size:12px; color:rgba(77,84,84,.7); margin-top:6px; line-height:1.5;">
                            Free cancellation until {{ $cancelDeadline }}. Partial refund after.
                        </div>
                    </label>

                    {{-- Non-refundable --}}
                    <label style="padding:16px 18px; border:1px solid #E6E2D8; border-radius:14px; background:#fff; cursor:pointer; position:relative; display:block;">
                        <input type="radio" name="rate_type" value="non_refundable" style="position:absolute; opacity:0;">
                        <div style="position:absolute; top:14px; right:14px; padding:3px 8px; border-radius:99px; background:#DEEDD8; font-size:9.5px; font-weight:700; color:#2C7841; letter-spacing:.06em;">SAVE 12%</div>
                        <div style="font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:rgba(11,22,40,.55);">Non-refundable</div>
                        <div style="display:flex; align-items:baseline; gap:8px; margin-top:6px;">
                            <span style="font-size:22px; font-weight:700; color:var(--ash-navy-900); font-family:var(--ash-font-serif);">EGP {{ $nonRefundPrice ? number_format($nonRefundPrice) : '—' }}</span>
                            @if($basePrice)
                            <span style="font-size:12px; color:rgba(77,84,84,.55); text-decoration:line-through;">{{ number_format($basePrice) }}</span>
                            @endif
                        </div>
                        <div style="font-size:12px; color:rgba(77,84,84,.7); margin-top:6px; line-height:1.5;">No refunds after booking · payment captured immediately.</div>
                    </label>

                </div>
            </section>
            @endif

            {{-- Section — Guest details --}}
            <section class="step-section">
                <div class="sec-head">
                    <h3><span class="step-num">{{ $isHotel ? 3 : 2 }}</span>Guest details</h3>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>First name</label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name">
                    </div>
                    <div class="field">
                        <label>Last name</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name">
                    </div>
                    <div class="field full">
                        <label>Email</label>
                        <input type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" required autocomplete="email">
                    </div>
                    <div class="field full">
                        <label>Phone</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="+20 1XX XXX XXXX" autocomplete="tel">
                    </div>
                    <div class="field">
                        <label>Nationality</label>
                        <select name="nationality">
                            <option value="">Select…</option>
                            @foreach([
                                'Egyptian'       => '🇪🇬 Egyptian',
                                'Saudi Arabian'  => '🇸🇦 Saudi Arabian',
                                'Emirati'        => '🇦🇪 Emirati',
                                'Kuwaiti'        => '🇰🇼 Kuwaiti',
                                'Qatari'         => '🇶🇦 Qatari',
                                'British'        => '🇬🇧 British',
                                'American'       => '🇺🇸 American',
                                'German'         => '🇩🇪 German',
                                'French'         => '🇫🇷 French',
                                'Italian'        => '🇮🇹 Italian',
                                'Other'          => 'Other',
                            ] as $val => $lbl)
                                <option value="{{ $val }}" {{ old('nationality') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Booking from <span class="opt">· country</span></label>
                        <select name="booking_from">
                            <option value="">Select…</option>
                            @foreach([
                                'Egypt'         => '🇪🇬 Egypt',
                                'Saudi Arabia'  => '🇸🇦 Saudi Arabia',
                                'UAE'           => '🇦🇪 United Arab Emirates',
                                'Kuwait'        => '🇰🇼 Kuwait',
                                'Qatar'         => '🇶🇦 Qatar',
                                'UK'            => '🇬🇧 United Kingdom',
                                'USA'           => '🇺🇸 United States',
                                'Germany'       => '🇩🇪 Germany',
                                'France'        => '🇫🇷 France',
                                'Italy'         => '🇮🇹 Italy',
                                'Other'         => 'Other',
                            ] as $val => $lbl)
                                <option value="{{ $val }}" {{ old('booking_from') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field full">
                        <label>Special requests <span class="opt">· optional</span></label>
                        <textarea name="special_requests" placeholder="Late check-in, dietary requirements, anything we should know…">{{ old('special_requests') }}</textarea>
                    </div>
                </div>
            </section>

            {{-- Section — Cancellation --}}
            <section class="step-section">
                <div class="sec-head">
                    <h3><span class="step-num">{{ $isHotel ? 4 : 3 }}</span>Cancellation</h3>
                </div>
                <div class="cancel-card">
                    <div class="cancel-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1E5028" stroke-width="2.5"><path d="M5 12l5 5L20 7"/></svg>
                    </div>
                    <div>
                        @if(isset($property) && $property->cancellation_policy)
                            <strong>{{ $property->cancellation_policy }}</strong>
                        @else
                            <strong>Free cancellation until {{ $cancelDeadline }}.</strong>
                            <p>Full refund if you cancel before then. Partial refund after.</p>
                        @endif
                    </div>
                </div>
            </section>

            <div class="step-footer">
                <a href="{{ route('listing.show', $propertyId) }}" class="back">← Back to listing</a>
                <button type="submit" class="primary-cta">
                    {{ $isPending ? 'Send request to host' : 'Continue to payment' }}
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </div>
        </form>

        {{-- ============================================================ --}}
        {{-- STEP: PAYMENT                                                 --}}
        {{-- ============================================================ --}}
        @elseif($step === 'payment')

        <h1>Payment</h1>
        <p class="lead">Encrypted end-to-end. We never store your card details.</p>

        <form action="{{ route('checkout.store') }}" method="POST" novalidate>
            @csrf
            <input type="hidden" name="step"        value="payment">
            <input type="hidden" name="property_id" value="{{ $propertyId }}">

            {{-- Section 1 — Payment method --}}
            <section class="step-section">
                <div class="sec-head">
                    <h3><span class="step-num">1</span>How would you like to pay?</h3>
                </div>
                <div class="pay-methods">
                    @php
                        $payMethods = [
                            ['id' => 'card',     'label' => 'Credit / Debit card', 'sub' => 'Visa · Mastercard · Meeza',          'icon' => 'CARD', 'cls' => 'card'],
                            ['id' => 'instapay', 'label' => 'InstaPay',            'sub' => 'Direct bank transfer',               'icon' => '≈',    'cls' => 'instapay'],
                            ['id' => 'valu',     'label' => 'Valu — Pay in 4',     'sub' => '4 × instalments · 0% interest',      'icon' => 'V',    'cls' => 'valu'],
                            ['id' => 'vodafone', 'label' => 'Vodafone Cash',       'sub' => 'Pay with your Vodafone wallet',       'icon' => 'V',    'cls' => 'vodafone'],
                        ];
                    @endphp
                    @foreach($payMethods as $pm)
                    <label class="pay-method{{ $loop->first ? ' on' : '' }}">
                        <input type="radio" name="payment_method" value="{{ $pm['id'] }}"{{ $loop->first ? ' checked' : '' }}>
                        <div class="pm-icon {{ $pm['cls'] }}">{{ $pm['icon'] }}</div>
                        <div class="pm-meta">
                            <strong>{{ $pm['label'] }}</strong>
                            <span>{{ $pm['sub'] }}</span>
                        </div>
                        <div class="radio-dot">@if($loop->first)<span></span>@endif</div>
                    </label>
                    @endforeach
                </div>
            </section>

            {{-- Section 2 — Card details --}}
            <section class="step-section">
                <div class="sec-head">
                    <h3><span class="step-num">2</span>Card details</h3>
                </div>
                <div class="form-grid">
                    <div class="field full">
                        <label>Card number</label>
                        <div class="card-input">
                            <input type="text" name="card_number" placeholder="1234 5678 9012 3456" autocomplete="cc-number" inputmode="numeric">
                            <div class="brands"><span>Visa</span><span>MC</span></div>
                        </div>
                    </div>
                    <div class="field">
                        <label>Expires</label>
                        <input type="text" name="card_expiry" placeholder="MM / YY" autocomplete="cc-exp">
                    </div>
                    <div class="field">
                        <label>CVC</label>
                        <input type="text" name="card_cvc" placeholder="•••" autocomplete="cc-csc" inputmode="numeric">
                    </div>
                    <div class="field full">
                        <label>Name on card</label>
                        <input type="text" name="card_name" autocomplete="cc-name">
                    </div>
                </div>
            </section>

            {{-- Section 3 — Paymob / legal --}}
            <section class="step-section">
                <div class="sec-head">
                    <h3><span class="step-num">3</span>Secure payment</h3>
                </div>
                <div class="legal">
                    By selecting <strong>Pay now</strong>, you agree to Ashome's House Rules, Cancellation Policy, and
                    authorize a charge of <strong>EGP {{ $total ? number_format($total) : '—' }}</strong> to your selected payment method.
                    <br><br>
                    You will be redirected to Paymob's secure payment page to complete your transaction.
                </div>
            </section>

            <div class="step-footer">
                <a href="{{ route('checkout.show', ['property_id' => $propertyId, 'check_in' => $checkIn, 'check_out' => $checkOut, 'guests' => $guests, 'step' => 'review']) }}" class="back">← Back</a>
                <button type="submit" class="primary-cta">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Pay EGP {{ $total ? number_format($total) : '—' }} securely
                </button>
            </div>
        </form>

        @endif
    </main>

    {{-- ── RIGHT: sticky summary card ──────────────────────────────── --}}
    <aside class="checkout-summary">
        <div class="summary-card">
            <div class="sum-hero">
                <img src="{{ $img }}" alt="{{ isset($property) ? $property->title : '' }}">
            </div>
            <div class="sum-body">
                @if(isset($property) && $property->is_premium)
                <div class="sup">★ Featured</div>
                @endif
                <div class="sum-title">{{ isset($property) ? $property->title : '' }}</div>
                <div class="sum-loc">
                    {{ isset($property) ? ($property->city ?? $property->address ?? '') : '' }}{{ (isset($property) && $property->country) ? ' · ' . $property->country : '' }}
                </div>

                <div class="sum-divider"></div>

                <div class="sum-section">
                    <div class="sum-section-title">Your trip</div>
                    <div class="sum-row"><span>Check-in</span><span>{{ $checkInFormatted }}</span></div>
                    <div class="sum-row"><span>Check-out</span><span>{{ $checkOutFormatted }}</span></div>
                    <div class="sum-row"><span>Guests</span><span>{{ $guests }} {{ $guests == 1 ? 'adult' : 'adults' }}</span></div>
                </div>

                <div class="sum-divider"></div>

                <div class="sum-section">
                    <div class="sum-section-title">Price details</div>
                    @if($basePrice && $nights > 0)
                    <div class="sum-row">
                        <span>EGP {{ number_format($basePrice) }} × {{ $nights }} {{ $nights == 1 ? 'night' : 'nights' }}</span>
                        <span>{{ number_format($basePrice * $nights) }}</span>
                    </div>
                    @elseif($basePrice)
                    <div class="sum-row">
                        <span>EGP {{ number_format($basePrice) }} / night</span>
                        <span>—</span>
                    </div>
                    @endif
                    <div class="sum-row"><span>Cleaning fee</span><span>—</span></div>
                    <div class="sum-row"><span>As-home service</span><span>—</span></div>
                </div>

                <div class="sum-total">
                    <span>{{ $step === 'payment' ? 'Due today · EGP' : 'Total · EGP' }}</span>
                    <strong>{{ $total ? number_format($total) : '—' }}</strong>
                </div>

                <div class="sum-note">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1E5028" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Ashome protects your payment
                </div>
            </div>
        </div>
    </aside>

</div>
@endif

@endsection
