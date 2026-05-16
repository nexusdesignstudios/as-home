{{-- Listing pages sit outside the main nav tabs — no explorer_nav_active section --}}
@extends('layouts.explorer')

@php
    $c        = $property->property_classification;
    $isHotel  = ($c == 5);
    $isVac    = ($c == 4);
    $isRE     = !$isHotel && !$isVac;

    // Per-vertical labels and colours
    $typeLabel = $isHotel ? 'Hotel' : ($isVac ? 'Vacation Home' : 'Real Estate');
    $catColor  = $isHotel ? '#8A6520' : ($isVac ? '#3E8C8A' : '#C8A557');
    $vertical  = $isHotel ? 'hotel' : ($isVac ? 'vacation' : 're');

    // Fallback hero image per vertical (used when title_image is missing)
    $heroFallback = match(true) {
        $isHotel => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&q=75&auto=format',
        $isVac   => 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=1200&q=75&auto=format',
        default  => 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=1200&q=75&auto=format',
    };

    // Gallery: use title_image as main, propertyImages collection for the grid
    $mainImg  = $property->title_image;
    if (empty($mainImg) || str_starts_with($mainImg, 'data:image/svg')) {
        $mainImg = $heroFallback;
    }

    // Gallery thumbnails from the eager-loaded relationship
    $galleryImages = $property->propertyImages ?? collect();
    $totalPhotos   = $galleryImages->count() + 1; // +1 for title image

    // Build gallery image URLs: pattern from Property::getGalleryAttribute()
    // url('') . config('global.IMG_PATH') . config('global.PROPERTY_GALLERY_IMG_PATH') . $id . "/" . $filename
    // We resolve this at render time via the gallery accessor
    $galleryWithUrls = $property->gallery ?? collect();   // has image_url already

    // Location
    $city    = $property->city    ?? $property->address ?? '';
    $country = $property->country ?? 'Egypt';
    $address = $property->address ?? $city;

    // Pricing
    $price     = $property->price;
    $priceType = ($property->propery_type === 'rent') ? 'FOR RENT' : 'FOR SALE';

    // Host info — Customer model via added_by FK
    $host        = $property->customer;
    $hostName    = optional($host)->name ?? ($property->revenue_user_name ?? 'Host');
    $hostInitial = strtoupper(substr($hostName, 0, 1) ?: 'H');

    // Amenities from assignfacilities (eager-loaded with outdoorfacilities)
    $facilities = [];
    foreach ($property->assignfacilities ?? [] as $af) {
        if ($af->outdoorfacilities) {
            $facilities[] = [
                'name'     => $af->outdoorfacilities->name ?? '',
                'distance' => $af->distance ?? null,
            ];
        }
    }

    // Placeholder amenities when none are assigned
    $placeholderAmenities = $isHotel
        ? ['Swimming pool', 'Free Wi-Fi', 'Breakfast included', 'Air conditioning',
           'Spa & wellness', 'Private beach', '24-hr reception', 'Free parking']
        : ($isVac
            ? ['Beach access', 'Private pool', 'Sea view', 'Air conditioning',
               'Free Wi-Fi', 'Parking', 'Kitchen', 'Pet-friendly']
            : ['Security 24/7', 'Covered parking', 'Elevators', 'Central A/C',
               'Storage room', 'Gym access', 'Concierge service', 'Maintenance']);

    // Hotel rooms (loaded only when classification=5)
    $hotelRooms = $isHotel ? ($property->hotelRooms ?? collect()) : collect();

    // Hotel packages (addons_packages accessor — hotel only)
    $packages = $isHotel ? ($property->addons_packages ?? collect()) : collect();

    // Description (truncate trigger)
    $desc        = $property->description ?? '';
    $descLong    = mb_strlen($desc) > 320;
    $descShort   = $descLong ? mb_substr($desc, 0, 320) . '…' : $desc;

    // Review placeholder data (no rating/review columns on Property)
    $reviewCategories = ['Cleanliness', 'Location', 'Value', 'Accuracy'];
@endphp

@section('title', $property->title . ' — Ashome')
@section('meta_description', mb_substr($property->description ?? $typeLabel . ' in ' . $city, 0, 160))

@section('css')
<style>
/* Override exp-main constraints for the listing page */
.exp-main { padding: 0; max-width: none; margin: 0; }
</style>
@endsection

@section('content')
<div class="listing-page">

    {{-- ── Breadcrumbs ──────────────────────────────────────── --}}
    <div class="listing-crumbs">
        <a href="{{ route('explorer.home') }}">Home</a>
        <span>/</span>
        <a href="{{ route('explorer.home', ['v' => $vertical]) }}">{{ $typeLabel }}s</a>
        <span>/</span>
        @if($city)
            <a href="{{ route('explorer.home', ['v' => $vertical]) }}">{{ $city }}</a>
            <span>/</span>
        @endif
        <span class="curr">{{ $property->title }}</span>

        @if($isHotel)
        {{-- Currency + language toggle (matches hotel design) --}}
        <div style="margin-left:auto; display:flex; gap:8px;">
            <div style="display:flex; border:1px solid #E6E2D8; border-radius:99px; padding:3px; background:#fff;">
                <button style="padding:4px 12px; border:0; border-radius:99px; background:#0B1628; color:#fff; font-size:11.5px; font-weight:700; cursor:pointer; font-family:inherit;">EGP</button>
                <button style="padding:4px 12px; border:0; border-radius:99px; background:transparent; color:#0B1628; font-size:11.5px; font-weight:700; cursor:pointer; font-family:inherit;">USD</button>
            </div>
        </div>
        @endif
    </div>

    {{-- ── Page header ──────────────────────────────────────── --}}
    <div class="listing-head">
        <div>
            <div class="list-cat" style="color: {{ $catColor }};">
                {{ strtoupper($typeLabel) }}@if($isHotel) &nbsp;·&nbsp; ★★★★★ @endif
            </div>
            <h1>{{ $property->title }}</h1>
            <div class="list-sub">
                <span class="rating">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="#D4A843">
                        <path d="M12 2l3 7h7l-6 4 2 8-6-4-6 4 2-8-6-4h7z"/>
                    </svg>
                    <strong>—</strong>
                    &nbsp;· No reviews yet
                </span>
                <span class="dot">·</span>
                <span class="loc-link">
                    📍 {{ implode(', ', array_filter([$city, $country])) }}
                </span>
            </div>
        </div>
        <div class="list-head-actions">
            <button class="ghost-btn" type="button">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8M16 6l-4-4-4 4M12 2v13"/>
                </svg>
                Share
            </button>
            <button class="ghost-btn" type="button">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                Save
            </button>
        </div>
    </div>

    {{-- ── Photo gallery ─────────────────────────────────────── --}}
    <div class="listing-gallery">
        <div class="lg-main" style="background-image: url('{{ $mainImg }}');">
            <button class="gallery-cta" type="button">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                </svg>
                Show all {{ $totalPhotos }} photo{{ $totalPhotos != 1 ? 's' : '' }}
            </button>
        </div>
        <div class="lg-grid">
            @php $thumbsShown = 0; @endphp
            @foreach($galleryWithUrls as $gImg)
                @if($thumbsShown < 4)
                    <div class="lg-thumb" style="background-image: url('{{ $gImg->image_url ?? '' }}');"></div>
                    @php $thumbsShown++; @endphp
                @endif
            @endforeach
            @for($g = $thumbsShown; $g < 4; $g++)
                <div class="lg-thumb"></div>
            @endfor
        </div>
    </div>

    {{-- ── Two-column body ──────────────────────────────────── --}}
    <div class="listing-body">

        {{-- ── LEFT: scrollable content ───────────────────── --}}
        <main class="listing-main">

            {{-- Host intro --}}
            <section class="lb-host">
                <div>
                    @if($isHotel)
                        <h2>{{ $property->getRawOriginal('property_classification') == 5 ? '5-star resort' : 'Hotel' }} on the Red Sea</h2>
                        <p class="lb-meta">
                            @if($property->hotel_available_rooms)
                                {{ $property->hotel_available_rooms }} rooms ·
                            @endif
                            {{ $city }}
                        </p>
                    @elseif($isVac)
                        <h2>Entire property hosted by {{ $hostName }}</h2>
                        <p class="lb-meta">
                            {{ $city }}
                            @if($property->area_description) · {{ $property->area_description }} @endif
                        </p>
                    @else
                        <h2>Property listed by {{ $hostName }}</h2>
                        <p class="lb-meta">
                            {{ $address }}@if($country) , {{ $country }}@endif
                        </p>
                    @endif
                </div>
                <div class="host-avatar" style="{{ $isHotel ? 'background:#0C447C;' : '' }}">
                    <span>{{ $hostInitial }}</span>
                    @if($isVac)
                        <em>★</em>
                    @endif
                </div>
            </section>

            {{-- About --}}
            <section class="lb-section">
                @if($isHotel)     <h3>About this hotel</h3>
                @elseif($isVac)   <h3>About this place</h3>
                @else             <h3>About this property</h3>
                @endif

                @if($descLong)
                    <p id="desc-short">{{ $descShort }}
                        <button class="show-more" type="button"
                                onclick="document.getElementById('desc-short').style.display='none';
                                         document.getElementById('desc-full').style.display='block';">
                            Show more →
                        </button>
                    </p>
                    <p id="desc-full" style="display:none;">{{ $desc }}</p>
                @else
                    <p>{{ $desc ?: 'No description available.' }}</p>
                @endif
            </section>

            {{-- Amenities / Details / What it offers --}}
            <section class="lb-section">
                @if($isRE)
                    <h3>Property details</h3>
                    @php $params = collect($property->parameters ?? []); @endphp
                    @if($params->isNotEmpty())
                        <div class="amen-grid">
                            @foreach($params->take(8) as $param)
                            <div class="amen-row">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B1628" stroke-width="1.6" stroke-linecap="round">
                                    <rect x="3" y="3" width="18" height="18" rx="3"/>
                                </svg>
                                <span>
                                    <strong>{{ $param['name'] ?? '' }}:</strong>
                                    {{ is_array($param['value'] ?? null) ? implode(', ', $param['value']) : ($param['value'] ?? '—') }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="amen-grid">
                            <div class="amen-row">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B1628" stroke-width="1.6"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                                <span>Type: {{ ucfirst($property->propery_type ?? 'Property') }}</span>
                            </div>
                            <div class="amen-row">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B1628" stroke-width="1.6"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span>Location: {{ $city }}</span>
                            </div>
                            @foreach(array_slice($placeholderAmenities, 2, 4) as $a)
                            <div class="amen-row">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B1628" stroke-width="1.6" stroke-linecap="round">
                                    <path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                </svg>
                                <span>{{ $a }}</span>
                            </div>
                            @endforeach
                        </div>
                    @endif

                @else
                    <h3>What this place offers</h3>
                    @if(count($facilities) > 0)
                        <div class="amen-grid">
                            @foreach($facilities as $fac)
                            <div class="amen-row">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B1628" stroke-width="1.6" stroke-linecap="round">
                                    <path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                </svg>
                                <span>{{ $fac['name'] }}</span>
                            </div>
                            @endforeach
                        </div>
                    @else
                        {{-- Placeholder amenities --}}
                        <div class="amen-grid">
                            @foreach($placeholderAmenities as $a)
                            <div class="amen-row">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B1628" stroke-width="1.6" stroke-linecap="round">
                                    <path d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                </svg>
                                <span>{{ $a }}</span>
                            </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </section>

            {{-- Hotel packages strip --}}
            @if($isHotel && $packages->isNotEmpty())
            <section class="lb-section">
                <h3>Packages &amp; offers</h3>
                <div class="pkg-grid">
                    @foreach($packages->take(3) as $pkg)
                    <div class="pkg-card">
                        @php
                            $pkgImg = $pkg['addons'][0]['value'] ?? null;
                            if (empty($pkgImg) || !str_starts_with($pkgImg, 'http')) {
                                $pkgImg = 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=400&q=70&auto=format';
                            }
                        @endphp
                        <div class="pkg-card-img" style="background-image:url('{{ $pkgImg }}');"></div>
                        <div class="pkg-card-body">
                            <div class="pkg-card-name">{{ $pkg['name'] ?? '' }}</div>
                            <div class="pkg-card-desc">{{ $pkg['description'] ?? '' }}</div>
                            @if(!empty($pkg['price']))
                            <div class="pkg-card-price">+EGP {{ number_format($pkg['price']) }} / stay</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>
            @endif

            {{-- Hotel room selection --}}
            @if($isHotel && $hotelRooms->isNotEmpty())
            <section class="lb-section" id="rooms">
                <h3>Choose your room</h3>
                <p class="lb-meta" style="margin-bottom:14px;">Select dates above to see availability</p>
                <div style="display:flex; flex-direction:column; gap:14px;">
                    @foreach($hotelRooms as $room)
                    @php
                        $rName  = optional($room->roomType)->name ?? ('Room ' . $room->id);
                        $rPrice = $room->price ?? $room->flex_price ?? null;
                        $rInstant = $room->instant_booking ?? false;
                    @endphp
                    <div class="room-card">
                        <div class="room-card-img"
                             style="background-image:url('{{ $room->image ?? 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&q=70&auto=format' }}');"></div>
                        <div class="room-card-body">
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <div class="room-card-name">{{ $rName }}</div>
                                @if($rInstant)
                                    <span style="padding:2px 8px; border-radius:99px; background:#FFE9B8; font-size:10px; font-weight:700; color:#7A5500; letter-spacing:.05em;">⚡ INSTANT</span>
                                @endif
                            </div>
                            <div class="room-card-meta">
                                {{ $room->size ?? '' }}
                                @if($room->bed_type) · {{ $room->bed_type }} @endif
                            </div>
                            @if($rPrice)
                            <div class="room-rate-grid">
                                <div class="rate-option active">
                                    <div class="rate-label">Flexible</div>
                                    <div class="rate-price">EGP {{ number_format($rPrice) }} <span style="font-size:10.5px; font-weight:400; opacity:.6;">/ night</span></div>
                                    <div class="rate-note">Free cancel</div>
                                </div>
                                <div class="rate-option">
                                    <div class="rate-label" style="color:rgba(11,22,40,.55);">Non-refundable</div>
                                    <div class="rate-price">EGP {{ number_format($rPrice * 0.88) }}</div>
                                    <div class="rate-note">Paid at booking &nbsp; <span style="background:#DEEDD8; color:#2C7841; padding:1px 5px; border-radius:4px; font-size:9px; font-weight:700;">-12%</span></div>
                                </div>
                            </div>
                            @endif
                            <button type="button"
                                    style="margin-top:14px; padding:10px 18px; border-radius:99px; border:1.5px solid #0B1628; background:#fff; color:#0B1628; font-weight:700; font-size:13px; font-family:inherit; cursor:pointer;">
                                Select this room
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>
            @endif

            {{-- Availability calendar (Vacation / Hotel) --}}
            @if(!$isRE)
            <section class="lb-section">
                <h3>Availability</h3>
                <p class="lb-meta">
                    Select check-in and check-out dates to see availability.
                    {{-- TODO: wire to availability API / blocked dates from reservations --}}
                </p>
                <div style="padding:24px; border:1px solid #EAE7E0; border-radius:14px; text-align:center; color:#9AA0A6; font-size:13px;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#9AA0A6" stroke-width="1.5" style="display:block; margin:0 auto 8px;">
                        <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Interactive calendar coming soon
                </div>
            </section>
            @endif

            {{-- Reviews teaser --}}
            <section class="lb-section">
                <h3>Reviews</h3>
                <div class="review-bars">
                    @foreach($reviewCategories as $cat)
                    <div class="rb-row">
                        <span class="rb-k">{{ $cat }}</span>
                        <div class="rb-bar"><div style="width:0%;"></div></div>
                        <span class="rb-v">—</span>
                    </div>
                    @endforeach
                </div>
                <p class="lb-meta">No reviews yet — be the first to book and leave a review!</p>
            </section>

            {{-- Location / map --}}
            <section class="lb-section">
                <h3>Where you'll be</h3>
                <div class="lb-map">
                    {{-- TODO: wire to Google Maps embed using $property->latitude / $property->longitude --}}
                    <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:10px; color:#5A5C63;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#9AA0A6" stroke-width="1.4">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <div style="background:#0B1628; color:#fff; padding:6px 14px; border-radius:99px; font-size:12px; font-weight:700; box-shadow:0 4px 12px rgba(0,0,0,.2);">
                            {{ $property->title }}
                        </div>
                    </div>
                </div>
                <p class="lb-meta">{{ $address }}{{ $country ? ', ' . $country : '' }}</p>
            </section>

            {{-- What's nearby (hotel only) --}}
            @if($isHotel)
            <section class="lb-section">
                <h3>What's nearby</h3>
                <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:10px;">
                    @php
                        $nearby = [
                            ['icon' => '⚓', 'name' => 'Marina & promenade',     'dist' => '0.4 km', 'desc' => 'Walking distance'],
                            ['icon' => '🏖', 'name' => 'Private beach',           'dist' => '0.2 km', 'desc' => 'Snorkelling & diving'],
                            ['icon' => '✈', 'name' => 'Hurghada Intl Airport',  'dist' => '22 km',  'desc' => '≈ 25 min by car'],
                            ['icon' => '🛒', 'name' => 'Shopping centre',          'dist' => '4 km',   'desc' => 'Shops & restaurants'],
                        ];
                    @endphp
                    @foreach($nearby as $n)
                    <div style="display:flex; gap:12px; padding:12px 14px; border:1px solid #EAE7E0; border-radius:10px; background:#fff; align-items:center;">
                        <div style="width:40px; height:40px; border-radius:10px; background:#FAFAF6; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0;">{{ $n['icon'] }}</div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; font-weight:700; color:#0B1628;">{{ $n['name'] }}</div>
                            <div style="font-size:11.5px; color:rgba(77,84,84,.7); margin-top:2px;">{{ $n['desc'] }}</div>
                        </div>
                        <div style="font-size:12px; font-weight:700; color:#8A6520; white-space:nowrap;">{{ $n['dist'] }}</div>
                    </div>
                    @endforeach
                </div>
            </section>
            @endif

            {{-- House rules (vacation only) --}}
            @if($isVac)
            <section class="lb-section">
                <h3>House rules</h3>
                <div class="amen-grid">
                    <div class="amen-row">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B1628" stroke-width="1.6">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span>Check-in: {{ $property->check_in ?? 'After 3:00 PM' }}</span>
                    </div>
                    <div class="amen-row">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B1628" stroke-width="1.6">
                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span>Check-out: {{ $property->check_out ?? 'Before 12:00 PM' }}</span>
                    </div>
                    <div class="amen-row">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#C26A6A" stroke-width="1.6">
                            <circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                        </svg>
                        <span>No smoking</span>
                    </div>
                    <div class="amen-row">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0B1628" stroke-width="1.6">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                        </svg>
                        <span>Cancellation: {{ ucfirst($property->refund_policy ?? 'standard policy') }}</span>
                    </div>
                </div>
            </section>
            @endif

        </main>

        {{-- ── RIGHT: sticky booking card ──────────────────── --}}
        <aside class="listing-reserve">
            <div class="lr-card">

                @if($isRE)
                {{-- ── Real Estate card ──────────────────── --}}
                <div style="font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#9AA0A6; margin-bottom:8px;">
                    {{ $priceType }}
                </div>
                <div class="lr-price-row" style="margin-bottom:14px;">
                    <div>
                        <strong>EGP {{ $price ? number_format($price) : '—' }}</strong>
                    </div>
                </div>
                <a href="#" class="lr-cta" style="background:#0B1628; color:#FFFEFA; margin-bottom:10px; display:block;">
                    Schedule a viewing
                </a>
                <a href="#" style="display:block; text-align:center; padding:14px; border-radius:12px; border:1.5px solid #0B1628; color:#0B1628; font-weight:700; font-size:15px; text-decoration:none; margin-bottom:18px;">
                    Contact agent
                </a>
                <div style="display:flex; align-items:center; gap:12px; padding-top:18px; border-top:1px solid #EAE7E0;">
                    <div style="width:40px; height:40px; border-radius:50%; background:#0B1628; color:#FFFEFA; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">
                        {{ $hostInitial }}
                    </div>
                    <div>
                        <div style="font-size:13px; font-weight:600; color:#1A1B1F;">{{ $hostName }}</div>
                        <div style="font-size:11px; color:#9AA0A6;">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="#3E8C8A" style="vertical-align:middle; margin-right:3px;"><path d="M9 11l3 3L22 4"/></svg>
                            Verified agent
                        </div>
                    </div>
                </div>

                @elseif($isHotel && $hotelRooms->isNotEmpty())
                {{-- ── Hotel card — no room selected state ── --}}
                <div style="padding:8px 10px; background:#FAFAF6; border-radius:8px; font-size:11px; font-weight:700; color:rgba(11,22,40,.5); letter-spacing:.04em; text-transform:uppercase; margin-bottom:12px;">From</div>
                <div class="lr-price-row">
                    <div>
                        <strong>EGP {{ $hotelRooms->first()->price ? number_format($hotelRooms->first()->price) : '—' }}</strong>
                        <span>/ night</span>
                    </div>
                    <div class="lr-rating">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="#D4A843"><path d="M12 2l3 7h7l-6 4 2 8-6-4-6 4 2-8-6-4h7z"/></svg>
                        <strong>—</strong>
                    </div>
                </div>
                <div class="lr-dates">
                    <div class="lr-cell"><div class="k">Check-in</div><div class="v">Add date</div></div>
                    <div class="lr-cell"><div class="k">Check-out</div><div class="v">Add date</div></div>
                    <div class="lr-cell full"><div class="k">Guests</div><div class="v">Add guests</div></div>
                </div>
                <a href="#rooms" class="lr-cta">Choose a room ↓</a>
                <p class="lr-note">Pick a room below to see total price</p>

                @else
                {{-- ── Vacation / generic hotel card ──────── --}}
                <div class="lr-price-row">
                    <div>
                        <strong>EGP {{ $price ? number_format($price) : '—' }}</strong>
                        <span>/ night</span>
                    </div>
                    <div class="lr-rating">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="#D4A843"><path d="M12 2l3 7h7l-6 4 2 8-6-4-6 4 2-8-6-4h7z"/></svg>
                        <strong>—</strong>
                    </div>
                </div>
                <div class="lr-dates">
                    <div class="lr-cell"><div class="k">Check-in</div><div class="v">Add date</div></div>
                    <div class="lr-cell"><div class="k">Check-out</div><div class="v">Add date</div></div>
                    <div class="lr-cell full"><div class="k">Guests</div><div class="v">Add guests</div></div>
                </div>
                <a href="#" class="lr-cta">Reserve</a>
                <p class="lr-note">You won't be charged yet</p>
                @if($price)
                <div class="lr-breakdown">
                    <div class="lr-row">
                        <span>EGP {{ number_format($price) }} × 1 night</span>
                        <span>{{ number_format($price) }}</span>
                    </div>
                    <div class="lr-row"><span>Cleaning fee</span><span>—</span></div>
                    <div class="lr-row"><span>As-home service fee</span><span>—</span></div>
                    <div class="lr-total">
                        <strong>Total · EGP</strong>
                        <strong>{{ number_format($price) }}</strong>
                    </div>
                </div>
                @endif

                @endif

                <button type="button" class="lr-report">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    Report this listing
                </button>

            </div>
        </aside>

    </div>
</div>
@endsection
