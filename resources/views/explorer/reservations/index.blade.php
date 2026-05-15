@section('explorer_nav_active', 'rsv')
@extends('layouts.explorer')

@section('title', 'Your Reservations — Ashome')
@section('meta_description', 'View and manage all your Ashome bookings in one place.')

@php
    $tab          = request()->query('tab', 'upcoming');
    $reservations = $reservations ?? collect();

    // Controller should pass these counts; fall back to collection counts
    $upcomingCount   = $upcomingCount   ?? $reservations->where('status', 'confirmed')->count();
    $pastCount       = $pastCount       ?? $reservations->whereIn('status', ['completed', 'checked_out'])->count();
    $cancelledCount  = $cancelledCount  ?? $reservations->where('status', 'cancelled')->count();
    $savedCount      = $savedCount      ?? 0;

    $statusColor = fn($s) => match($s) {
        'confirmed'  => '#3E8C8A',
        'pending'    => '#C8A557',
        'cancelled'  => '#C26A6A',
        default      => '#9AA0A6',
    };
    $statusLabel = fn($s) => match($s) {
        'confirmed'  => 'Confirmed',
        'pending'    => 'Awaiting host approval',
        'cancelled'  => 'Cancelled',
        'completed'  => 'Completed',
        default      => ucfirst($s),
    };

    $tabs = [
        ['key' => 'upcoming',   'label' => 'Upcoming',   'count' => $upcomingCount],
        ['key' => 'past',       'label' => 'Past',        'count' => $pastCount],
        ['key' => 'cancelled',  'label' => 'Cancelled',   'count' => $cancelledCount],
        ['key' => 'saved',      'label' => 'Saved',       'count' => $savedCount],
    ];
@endphp

@section('css')
<style>
/* ── Reservations page ──────────────────────────────────────── */
.rsv-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 48px;
}
.rsv-display {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 56px;
    font-weight: 500;
    line-height: 1.05;
    color: #1A1B1F;
    letter-spacing: -.01em;
    margin: 6px 0 14px;
}
.rsv-display em {
    font-style: italic;
    color: #5A5C63;
}
.rsv-subtext {
    font-size: 15px;
    color: #5A5C63;
    max-width: 480px;
    line-height: 1.55;
}

/* ── Tabs ───────────────────────────────────────────────────── */
.rsv-tabs {
    display: flex;
    gap: 32px;
    border-bottom: 1px solid #EAE7E0;
    margin-bottom: 8px;
}
.rsv-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 0;
    font-size: 13px;
    border-bottom: 1.5px solid transparent;
    margin-bottom: -1px;
    text-decoration: none;
    cursor: pointer;
}
.rsv-tab.active {
    font-weight: 600;
    color: #1A1B1F;
    border-bottom-color: #1A1B1F;
}
.rsv-tab:not(.active) {
    font-weight: 500;
    color: #5A5C63;
}
.rsv-tab-count {
    font-size: 11px;
    color: #9AA0A6;
    font-weight: 400;
}

/* ── Reservation cards ──────────────────────────────────────── */
.rsv-list { padding-top: 8px; }
.rsv-card {
    display: grid;
    grid-template-columns: 160px 1fr auto;
    gap: 28px;
    padding: 24px 0;
    border-bottom: 1px solid #EAE7E0;
    align-items: center;
}
.rsv-thumb {
    width: 160px;
    height: 110px;
    border-radius: 14px;
    overflow: hidden;
    background: #E4DDD0;
    flex-shrink: 0;
}
.rsv-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.rsv-thumb.past-thumb { filter: saturate(0.85); }

.rsv-status-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 4px;
}
.rsv-status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}
.rsv-status-label {
    font-size: 11px;
    color: #5A5C63;
    font-weight: 500;
}
.rsv-conf {
    font-size: 11px;
    color: #9AA0A6;
}
.rsv-title {
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 26px;
    font-weight: 500;
    color: #1A1B1F;
    line-height: 1.1;
    margin: 4px 0;
    letter-spacing: -.01em;
}
.rsv-meta {
    font-size: 13px;
    color: #5A5C63;
    margin-top: 6px;
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
}
.rsv-price {
    font-size: 14px;
    color: #1A1B1F;
    margin-top: 10px;
    font-weight: 500;
    font-variant-numeric: tabular-nums;
}
.rsv-rating {
    font-size: 12px;
    color: #5A5C63;
    margin-top: 10px;
}
.rsv-review-prompt {
    font-size: 12px;
    color: #C8A557;
    margin-top: 10px;
    font-weight: 500;
}

/* ── Card action buttons ────────────────────────────────────── */
.rsv-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 160px;
}
.rsv-btn-primary {
    background: #0B1628;
    color: #FFFEFA;
    border: none;
    padding: 10px 16px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    font-family: inherit;
    text-align: center;
    text-decoration: none;
    display: block;
}
.rsv-btn-ghost {
    background: transparent;
    color: #1A1B1F;
    border: 1px solid #EAE7E0;
    padding: 10px 16px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    font-family: inherit;
    text-align: center;
    text-decoration: none;
    display: block;
}
.rsv-btn-gold {
    background: #C8A557;
    color: #0B1628;
    border: none;
    padding: 10px 16px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    text-align: center;
    text-decoration: none;
    display: block;
}
.rsv-btn-export {
    background: transparent;
    border: 1px solid #EAE7E0;
    color: #1A1B1F;
    padding: 10px 18px;
    border-radius: 999px;
    font-size: 12px;
    cursor: pointer;
    font-family: inherit;
}

/* ── Empty state ────────────────────────────────────────────── */
.rsv-empty {
    text-align: center;
    padding: 80px 0;
    color: #9AA0A6;
    font-size: 14px;
}
</style>
@endsection

@section('content')

{{-- ── Page header ──────────────────────────────────────────── --}}
<div class="rsv-page-header">
    <div>
        <div class="exp-label">Your bookings</div>
        <div class="rsv-display">
            Your stays<em>, in one place.</em>
        </div>
        <p class="rsv-subtext">
            {{ $upcomingCount }} upcoming and {{ $pastCount }} completed trips.
            Manage check-in details, message your host, or browse for your next stay.
        </p>
    </div>
    <div>
        <button class="rsv-btn-export" type="button">Export PDF</button>
    </div>
</div>

{{-- ── Tabs ──────────────────────────────────────────────────── --}}
<div class="rsv-tabs">
    @foreach($tabs as $t)
        <a href="{{ request()->fullUrlWithQuery(['tab' => $t['key']]) }}"
           class="rsv-tab {{ $tab === $t['key'] ? 'active' : '' }}">
            {{ $t['label'] }}
            <span class="rsv-tab-count">{{ $t['count'] }}</span>
        </a>
    @endforeach
</div>

{{-- ── Upcoming tab ────────────────────────────────────────────── --}}
@if($tab === 'upcoming')
    <div class="rsv-list">
        @forelse($reservations as $r)
        @php
            $sc    = $statusColor($r->status ?? 'pending');
            $sl    = $statusLabel($r->status ?? 'pending');
            $img   = optional($r->property)->images->first()->image ?? null;
            $nights = \Carbon\Carbon::parse($r->check_in)->diffInDays(\Carbon\Carbon::parse($r->check_out));
        @endphp
        <div class="rsv-card">
            {{-- Thumbnail --}}
            <div class="rsv-thumb">
                @if($img)
                    <img src="{{ asset('assets/images/property/' . $img) }}" alt="{{ optional($r->property)->name }}">
                @else
                    <div style="width:100%;height:100%;background:#E4DDD0;"></div>
                @endif
            </div>

            {{-- Details --}}
            <div>
                <div class="rsv-status-row">
                    <span class="rsv-status-dot" style="background:{{ $sc }};"></span>
                    <span class="rsv-status-label">{{ $sl }}</span>
                    <span class="rsv-conf">· Confirmation {{ $r->id }}</span>
                </div>
                <div class="rsv-title">{{ optional($r->property)->name ?? 'Property' }}</div>
                <div class="rsv-meta">
                    <span>📍 {{ optional($r->property)->address ?? optional($r->property)->city ?? '—' }}</span>
                    <span>
                        {{ \Carbon\Carbon::parse($r->check_in)->format('M j') }} –
                        {{ \Carbon\Carbon::parse($r->check_out)->format('M j, Y') }}
                    </span>
                    <span>{{ $nights }} night{{ $nights != 1 ? 's' : '' }}</span>
                </div>
                <div class="rsv-price">
                    EGP {{ number_format($r->total_amount ?? 0) }}
                </div>
            </div>

            {{-- Actions --}}
            <div class="rsv-actions">
                <a href="#" class="rsv-btn-primary">View booking</a>
                <a href="#" class="rsv-btn-ghost">Message host</a>
                <a href="#" class="rsv-btn-ghost">Modify stay</a>
            </div>
        </div>
        @empty
        <div class="rsv-empty">
            <p>No upcoming reservations.</p>
        </div>
        @endforelse
    </div>
@endif

{{-- ── Past tab ─────────────────────────────────────────────────── --}}
@if($tab === 'past')
    <div class="rsv-list">
        @forelse($reservations as $r)
        @php
            $checkIn  = \Carbon\Carbon::parse($r->check_in);
            $checkOut = \Carbon\Carbon::parse($r->check_out);
            $dateRange = $checkIn->format('M j') . ' – ' . $checkOut->format('M j, Y');
            $img = optional($r->property)->images->first()->image ?? null;
            $reviewed = $r->reviewed ?? false;
            $rating   = $r->rating ?? 0;
        @endphp
        <div class="rsv-card">
            {{-- Thumbnail (desaturated for past) --}}
            <div class="rsv-thumb past-thumb">
                @if($img)
                    <img src="{{ asset('assets/images/property/' . $img) }}" alt="{{ optional($r->property)->name }}">
                @else
                    <div style="width:100%;height:100%;background:#E4DDD0;"></div>
                @endif
            </div>

            {{-- Details --}}
            <div>
                <div class="exp-label">Completed · {{ $r->id }}</div>
                <div class="rsv-title">{{ optional($r->property)->name ?? 'Property' }}</div>
                <div class="rsv-meta">
                    <span>📍 {{ optional($r->property)->address ?? optional($r->property)->city ?? '—' }}</span>
                    <span>{{ $dateRange }}</span>
                </div>
                @if($reviewed)
                    <div class="rsv-rating">
                        You rated this stay
                        @for($i = 0; $i < intval($rating); $i++) ★ @endfor
                    </div>
                @else
                    <div class="rsv-review-prompt">★ Review your stay — 14 days left</div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="rsv-actions">
                <a href="#" class="rsv-btn-ghost">Book again</a>
                @if(!$reviewed)
                    <a href="#" class="rsv-btn-gold">Leave a review</a>
                @endif
            </div>
        </div>
        @empty
        <div class="rsv-empty">
            <p>No past reservations.</p>
        </div>
        @endforelse
    </div>
@endif

{{-- ── Cancelled tab ───────────────────────────────────────────── --}}
@if($tab === 'cancelled')
    <div class="rsv-list">
        @forelse($reservations as $r)
        @php
            $img = optional($r->property)->images->first()->image ?? null;
        @endphp
        <div class="rsv-card">
            <div class="rsv-thumb" style="filter:saturate(0.6) opacity(0.7);">
                @if($img)
                    <img src="{{ asset('assets/images/property/' . $img) }}" alt="{{ optional($r->property)->name }}">
                @else
                    <div style="width:100%;height:100%;background:#E4DDD0;"></div>
                @endif
            </div>
            <div>
                <div class="rsv-status-row">
                    <span class="rsv-status-dot" style="background:#C26A6A;"></span>
                    <span class="rsv-status-label" style="color:#C26A6A;">Cancelled</span>
                    <span class="rsv-conf">· Confirmation {{ $r->id }}</span>
                </div>
                <div class="rsv-title">{{ optional($r->property)->name ?? 'Property' }}</div>
                <div class="rsv-meta">
                    <span>📍 {{ optional($r->property)->address ?? optional($r->property)->city ?? '—' }}</span>
                    <span>
                        {{ \Carbon\Carbon::parse($r->check_in)->format('M j') }} –
                        {{ \Carbon\Carbon::parse($r->check_out)->format('M j, Y') }}
                    </span>
                </div>
            </div>
            <div class="rsv-actions">
                <a href="#" class="rsv-btn-ghost">Book again</a>
            </div>
        </div>
        @empty
        <div class="rsv-empty">
            <p>No cancelled reservations.</p>
        </div>
        @endforelse
    </div>
@endif

{{-- ── Saved tab ───────────────────────────────────────────────── --}}
@if($tab === 'saved')
    <div class="rsv-empty">
        <p>Saved properties will appear here.</p>
    </div>
@endif

@endsection
