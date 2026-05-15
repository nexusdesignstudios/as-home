@section('explorer_nav_active', 'home')
@extends('layouts.explorer')

@section('title', 'Discover — Ashome')
@section('meta_description', 'Find hotels, vacation rentals, and real estate across Egypt on Ashome.')

@php
    $v = $vertical ?? 'hotel';

    // ── Per-vertical copy ────────────────────────────────────────
    $heroTitle = match($v) {
        'vacation' => 'Find your perfect vacation home',
        're'       => 'Find your perfect property in Egypt',
        default    => 'Find your perfect stay',
    };
    $heroSub = match($v) {
        'vacation' => 'From Sahel beach chalets to mountain retreats — book the one that feels like home.',
        're'       => 'Browse thousands of listings for sale and rent across Egypt\'s top cities.',
        default    => 'Thousands of hotels, resorts, and serviced apartments across Egypt and beyond.',
    };
    $sectionHeading = match($v) {
        'vacation' => 'Featured rentals',
        're'       => 'Featured listings',
        default    => 'Top-rated stays',
    };
    $destHeading = match($v) {
        'vacation' => 'Top destinations',
        're'       => 'Top areas in Egypt',
        default    => 'Popular cities',
    };
    $verticalTag = match($v) {
        'vacation' => 'Vacation',
        're'       => 'For Sale',
        default    => 'Hotel',
    };

    // ── Destination data (hardcoded; wire to DB later) ───────────
    $destinations = match($v) {
        'vacation' => [
            ['name' => 'Hacienda Bay',    'count' => '410 stays',   'img' => 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=600&h=375&fit=crop'],
            ['name' => 'El Gouna',        'count' => '320 stays',   'img' => 'https://images.unsplash.com/photo-1540202403-b7abd6747a18?w=600&h=375&fit=crop'],
            ['name' => 'Sahel',           'count' => '680 stays',   'img' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=600&h=375&fit=crop'],
            ['name' => 'Ain El Sokhna',   'count' => '240 stays',   'img' => 'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=600&h=375&fit=crop'],
        ],
        're' => [
            ['name' => 'New Cairo',       'count' => '4,820 listings', 'img' => 'https://images.unsplash.com/photo-1486325212027-8081e485255e?w=600&h=375&fit=crop'],
            ['name' => '6th of October',  'count' => '3,210 listings', 'img' => 'https://images.unsplash.com/photo-1501167786227-4cba60f6d58f?w=600&h=375&fit=crop'],
            ['name' => 'Zamalek',         'count' => '720 listings',   'img' => 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?w=600&h=375&fit=crop'],
            ['name' => 'Maadi',           'count' => '1,140 listings', 'img' => 'https://images.unsplash.com/photo-1464817739973-0128fe77aaa1?w=600&h=375&fit=crop'],
        ],
        default => [
            ['name' => 'Cairo',           'count' => '310 hotels',  'img' => 'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=600&h=375&fit=crop'],
            ['name' => 'Hurghada',        'count' => '180 hotels',  'img' => 'https://images.unsplash.com/photo-1540202403-b7abd6747a18?w=600&h=375&fit=crop'],
            ['name' => 'Sharm El Sheikh', 'count' => '220 hotels',  'img' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=600&h=375&fit=crop'],
            ['name' => 'Alexandria',      'count' => '140 hotels',  'img' => 'https://images.unsplash.com/photo-1571019613576-2b22c76fd955?w=600&h=375&fit=crop'],
        ],
    };

    // ── Start-here cards ─────────────────────────────────────────
    $startCards = [
        [
            'key'   => 'hotel',
            'label' => 'Hotel',
            'color' => '#633806',
            'bg'    => '#FAEEDA',
            'title' => 'Book a hotel',
            'desc'  => 'Search thousands of hotels and resorts. Compare rooms, rates, and amenities — then book instantly.',
            'cta'   => 'Search hotels →',
            'img'   => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=340&fit=crop',
        ],
        [
            'key'   => 'vacation',
            'label' => 'Vacation',
            'color' => '#085041',
            'bg'    => '#E1F5EE',
            'title' => 'Rent a vacation home',
            'desc'  => 'From beachfront chalets to mountain villas — browse, message your host, and book direct.',
            'cta'   => 'Browse rentals →',
            'img'   => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=600&h=340&fit=crop',
        ],
        [
            'key'   => 're',
            'label' => 'Real Estate',
            'color' => '#0C447C',
            'bg'    => '#E6F1FB',
            'title' => 'Buy or rent property',
            'desc'  => 'Find your next home, investment, or commercial space. Search, tour, and close the deal.',
            'cta'   => 'Explore listings →',
            'img'   => 'https://images.unsplash.com/photo-1486325212027-8081e485255e?w=600&h=340&fit=crop',
        ],
    ];

    // ── Fallback blog cards ──────────────────────────────────────
    $fallbackArticles = [
        ['title' => 'The best Red Sea resorts for 2026',     'cat' => 'Hotels',         'date' => 'May 2026', 'img' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=600&h=375&fit=crop'],
        ['title' => 'Sahel 2026: what to know before you go','cat' => 'Vacation Homes',  'date' => 'Apr 2026', 'img' => 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=600&h=375&fit=crop'],
        ['title' => 'New Cairo vs. 6th October: a buyer\'s guide','cat' => 'Real Estate', 'date' => 'Apr 2026','img' => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=600&h=375&fit=crop'],
    ];
    $properties = $properties ?? collect();
    $articles   = $articles   ?? collect();
@endphp

@section('css')
<style>
/* ── Home page: remove exp-main constraints so all sections go full-width ── */
.exp-main {
    padding: 0;
    max-width: none;
    margin: 0;
}

/* ── Hero ────────────────────────────────────────────────────────────────── */
.home-hero {
    position: relative;
    height: 480px;
    background-size: cover;
    background-position: center;
    background-color: #0B1628;
    color: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 0 48px;
}

/* ── Vertical tabs ───────────────────────────────────────────────────────── */
.h-tabs { display: flex; gap: 8px; margin-bottom: 18px; }
.h-tab {
    padding: 8px 18px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 600;
    background: rgba(255,255,255,.12);
    color: #fff;
    backdrop-filter: blur(8px);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-family: inherit;
}
.h-tab .dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.h-tab.active-ht { background: #fff; color: #0B1628; }
.h-tab.active-ht .dot { background: #BA7517; }
.h-tab.active-vh { background: #fff; color: #3E8C8A; }
.h-tab.active-vh .dot { background: #1D9E75; }
.h-tab.active-re { background: #fff; color: #C8A557; }
.h-tab.active-re .dot { background: #378ADD; }

/* ── Toggle row ──────────────────────────────────────────────────────────── */
.h-toggles { display: flex; gap: 12px; margin-bottom: 18px; }
.h-toggle-group {
    display: inline-flex;
    background: rgba(255,255,255,.14);
    backdrop-filter: blur(8px);
    border-radius: 999px;
    padding: 4px;
    gap: 2px;
}
.h-toggle-group button {
    background: transparent;
    border: none;
    color: rgba(255,255,255,.85);
    font-size: 13px;
    font-weight: 600;
    padding: 7px 16px;
    border-radius: 999px;
    cursor: pointer;
    font-family: inherit;
}
.h-toggle-group button.on {
    background: #fff;
    color: #0B1628;
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
}

/* ── Hero headings ───────────────────────────────────────────────────────── */
.home-hero h1 {
    font-size: 56px;
    font-weight: 800;
    letter-spacing: -.02em;
    line-height: 1.05;
    max-width: 720px;
    margin: 0 0 14px;
}
.home-hero p.hero-sub {
    font-size: 17px;
    opacity: .85;
    max-width: 540px;
    margin: 0 0 28px;
    line-height: 1.5;
}

/* ── Search bar ──────────────────────────────────────────────────────────── */
.home-search-bar {
    display: flex;
    background: #fff;
    border-radius: 12px;
    padding: 8px;
    box-shadow: 0 12px 32px rgba(0,0,0,.25);
    max-width: 900px;
}
.home-search-bar .seg {
    flex: 1;
    padding: 12px 18px;
    display: flex;
    flex-direction: column;
    gap: 2px;
    border-right: 1px solid #eee;
    cursor: pointer;
}
.home-search-bar .seg:last-of-type { border-right: 0; }
.home-search-bar .lbl {
    font-size: 10px;
    font-weight: 700;
    color: #9AA0A6;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.home-search-bar .val {
    font-size: 14px;
    color: #0B1628;
    font-weight: 600;
}
.home-search-bar .val.muted { color: #9AA0A6; font-weight: 500; }
.home-search-bar .go {
    background: #C8A557;
    color: #0B1628;
    width: 56px;
    height: auto;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    box-shadow: 0 3px 10px rgba(200,165,87,.4);
    flex-shrink: 0;
}

/* ── Section container max-width ─────────────────────────────────────────── */
.home-wrap {
    max-width: 1360px;
    margin: 0 auto;
}
</style>
@endsection

@section('content')

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- 1. Hero                                                       --}}
{{-- ════════════════════════════════════════════════════════════ --}}
<div class="home-hero"
     style="background-image: linear-gradient(180deg, rgba(11,22,40,.55), rgba(11,22,40,.7)), url('{{ asset('images/city.jpg') }}');">

    <div class="home-wrap" style="width:100%; display:flex; flex-direction:column; justify-content:center; flex:1; padding-top:40px;">

        {{-- Vertical switcher tabs --}}
        <div class="h-tabs">
            <a href="/?v=hotel"
               class="h-tab {{ $v === 'hotel' ? 'active-ht' : '' }}">
                <span class="dot"></span>
                Hotels
            </a>
            <a href="/?v=vacation"
               class="h-tab {{ $v === 'vacation' ? 'active-vh' : '' }}">
                <span class="dot"></span>
                Vacation Homes
            </a>
            <a href="/?v=re"
               class="h-tab {{ $v === 're' ? 'active-re' : '' }}">
                <span class="dot"></span>
                Real Estate
            </a>
        </div>

        {{-- Toggle row --}}
        @if($v === 're')
            <div class="h-toggles">
                <div class="h-toggle-group">
                    <button class="on">Buy</button>
                    <button>Rent</button>
                </div>
            </div>
        @else
            <div class="h-toggles">
                <div class="h-toggle-group">
                    <button class="on">This week</button>
                    <button>This month</button>
                    <button>Any time</button>
                </div>
            </div>
        @endif

        {{-- Headline --}}
        <h1>{{ $heroTitle }}</h1>
        <p class="hero-sub">{{ $heroSub }}</p>

        {{-- Search bar --}}
        <div class="home-search-bar">
            @if($v === 're')
                <div class="seg">
                    <span class="lbl">Location</span>
                    <span class="val muted">City, area, compound…</span>
                </div>
                <div class="seg">
                    <span class="lbl">Type</span>
                    <span class="val muted">Apartment, villa, land…</span>
                </div>
                <div class="seg">
                    <span class="lbl">Budget</span>
                    <span class="val muted">Any price</span>
                </div>
            @else
                <div class="seg">
                    <span class="lbl">Location</span>
                    <span class="val muted">City, area, hotel name…</span>
                </div>
                <div class="seg">
                    <span class="lbl">Check-in</span>
                    <span class="val muted">Add date</span>
                </div>
                <div class="seg">
                    <span class="lbl">Check-out</span>
                    <span class="val muted">Add date</span>
                </div>
                <div class="seg">
                    <span class="lbl">Guests</span>
                    <span class="val muted">Add guests</span>
                </div>
            @endif
            <button class="go" type="button" aria-label="Search">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4-4"/>
                </svg>
            </button>
        </div>

    </div>
</div>

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- 2. Featured listings                                          --}}
{{-- ════════════════════════════════════════════════════════════ --}}
<div class="web-section">
    <div class="home-wrap">
        <div class="head-row">
            <div>
                <h2>{{ $sectionHeading }}</h2>
            </div>
            <a href="/?v={{ $v }}">See all →</a>
        </div>

        <div class="web-grid">
            @forelse($properties->take(4) as $property)
            @php
                $img = $property->title_image;
                // If the accessor returned an SVG data-URI (no real image), use Unsplash placeholder
                if (empty($img) || str_starts_with($img, 'data:image/svg')) {
                    $img = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&h=300&fit=crop';
                }
                $price = $property->price ? 'EGP ' . number_format($property->price) : '—';
                $city  = $property->city ?? $property->address ?? '—';
            @endphp
            <div class="web-card">
                <div class="hero">
                    <img src="{{ $img }}" alt="{{ $property->title }}" loading="lazy">
                    <span class="tag">{{ $verticalTag }}</span>
                    <button class="heart" aria-label="Save">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                    </button>
                </div>
                <div class="body">
                    <div class="price">{{ $price }}</div>
                    <div class="title">{{ $property->title }}</div>
                    <div class="meta">
                        <span>📍 {{ $city }}</span>
                        @if($property->available_rooms)
                            <span class="sep"></span>
                            <span>{{ $property->available_rooms }} rooms</span>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            {{-- Placeholder cards when no live data --}}
            @php
                $placeholders = [
                    ['title' => 'Hurghada Malybo Hotel · Sea View', 'price' => 'EGP 3,500/night', 'loc' => 'Hurghada, Red Sea',   'img' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&h=300&fit=crop'],
                    ['title' => 'Marassi Beachfront · 2BR Chalet',  'price' => 'EGP 6,000/night', 'loc' => 'Marassi, North Coast','img' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=400&h=300&fit=crop'],
                    ['title' => 'Speranza Hotel · Executive Suite', 'price' => 'EGP 3,864/night', 'loc' => 'Cairo Downtown',      'img' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=400&h=300&fit=crop'],
                    ['title' => 'Hacienda Bay · 4BR Villa',         'price' => 'EGP 11,400/night','loc' => 'Sidi Abdel Rahman',  'img' => 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=400&h=300&fit=crop'],
                ];
            @endphp
            @foreach($placeholders as $ph)
            <div class="web-card">
                <div class="hero">
                    <img src="{{ $ph['img'] }}" alt="{{ $ph['title'] }}" loading="lazy">
                    <span class="tag">{{ $verticalTag }}</span>
                    <button class="heart" aria-label="Save">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                    </button>
                </div>
                <div class="body">
                    <div class="price">{{ $ph['price'] }}</div>
                    <div class="title">{{ $ph['title'] }}</div>
                    <div class="meta">
                        <span>📍 {{ $ph['loc'] }}</span>
                    </div>
                </div>
            </div>
            @endforeach
            @endforelse
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- 3. Top destinations                                           --}}
{{-- ════════════════════════════════════════════════════════════ --}}
<div class="web-section" style="padding-top:0;">
    <div class="home-wrap">
        <div class="head-row" style="margin-bottom:24px;">
            <h2>{{ $destHeading }}</h2>
        </div>
        <div class="web-categories">
            @foreach(array_slice($destinations, 0, 3) as $dest)
            <div class="web-cat">
                <img src="{{ $dest['img'] }}" alt="{{ $dest['name'] }}" loading="lazy">
                <div class="body">
                    <h3>{{ $dest['name'] }}</h3>
                    <p>{{ $dest['count'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @if(count($destinations) > 3)
        <div class="web-categories" style="margin-top:20px; grid-template-columns: repeat(3,1fr);">
            @foreach(array_slice($destinations, 3) as $dest)
            <div class="web-cat">
                <img src="{{ $dest['img'] }}" alt="{{ $dest['name'] }}" loading="lazy">
                <div class="body">
                    <h3>{{ $dest['name'] }}</h3>
                    <p>{{ $dest['count'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- 4. How it works / Start here                                  --}}
{{-- ════════════════════════════════════════════════════════════ --}}
<div class="web-start">
    <div class="home-wrap">
        <div class="web-start-head">
            <div class="eyebrow">Get started</div>
            <h2>Everything you need, in one place</h2>
            <p class="lead">Whether you're booking a hotel, renting a vacation home, or searching for real estate — Ashome has you covered.</p>
        </div>
        <div class="web-start-grid">
            @foreach($startCards as $card)
            <div class="web-start-card">
                <div class="thumb">
                    <img src="{{ $card['img'] }}" alt="{{ $card['title'] }}" loading="lazy">
                    <span class="tag" style="background:{{ $card['bg'] }}; color:{{ $card['color'] }};">
                        {{ $card['label'] }}
                    </span>
                </div>
                <div class="body">
                    <h3>{{ $card['title'] }}</h3>
                    <p>{{ $card['desc'] }}</p>
                    <a href="/?v={{ $card['key'] }}" class="web-btn" style="height:auto; padding:0;">
                        {{ $card['cta'] }}
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- 5. Blog teaser                                                --}}
{{-- ════════════════════════════════════════════════════════════ --}}
<div class="web-blogs">
    <div class="home-wrap">
        <div class="head-row" style="align-items:flex-start; flex-direction:column; margin-bottom:24px; gap:4px;">
            <div class="eyebrow">From the journal</div>
            <div style="display:flex; align-items:baseline; justify-content:space-between; width:100%;">
                <h2 style="margin:0;">Latest stories</h2>
                <a href="#" class="see-all">See all →</a>
            </div>
        </div>

        <div class="web-blog-grid">
            @if($articles->isNotEmpty())
                @foreach($articles as $article)
                <div class="web-blog-card">
                    <div class="thumb">
                        @php
                            $aImg = $article->image;
                            if (empty($aImg)) {
                                $aImg = 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=600&h=375&fit=crop';
                            }
                        @endphp
                        <img src="{{ $aImg }}" alt="{{ $article->title }}" loading="lazy">
                    </div>
                    <div class="body">
                        <div class="cat">{{ optional($article->category)->category ?? ($article->label_title ?? 'Ashome') }}</div>
                        <h3>{{ $article->title }}</h3>
                        <div class="meta">{{ $article->created_at ? $article->created_at->format('M Y') : '' }}</div>
                    </div>
                </div>
                @endforeach
            @else
                @foreach($fallbackArticles as $fa)
                <div class="web-blog-card">
                    <div class="thumb">
                        <img src="{{ $fa['img'] }}" alt="{{ $fa['title'] }}" loading="lazy">
                    </div>
                    <div class="body">
                        <div class="cat">{{ $fa['cat'] }}</div>
                        <h3>{{ $fa['title'] }}</h3>
                        <div class="meta">{{ $fa['date'] }}</div>
                    </div>
                </div>
                @endforeach
            @endif
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════ --}}
{{-- 6. Footer                                                     --}}
{{-- ════════════════════════════════════════════════════════════ --}}
<footer class="web-footer">
    {{-- Brand column --}}
    <div>
        <div class="brand">
            <img src="{{ asset('images/logo.png') }}" alt="Ashome" style="height:36px; width:auto;"
                 onerror="this.style.display='none'">
            Ashome
        </div>
        <p class="tagline">Find hotels, vacation rentals, and real estate across Egypt — all in one place.</p>
        <div class="social">
            <a href="#" aria-label="Facebook">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                </svg>
            </a>
            <a href="#" aria-label="Instagram">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                    <circle cx="12" cy="12" r="4"/>
                    <circle cx="17.5" cy="6.5" r=".5" fill="currentColor"/>
                </svg>
            </a>
            <a href="#" aria-label="LinkedIn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/>
                    <rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>
                </svg>
            </a>
        </div>
    </div>

    {{-- Explore --}}
    <div>
        <h4>Explore</h4>
        <ul>
            <li><a href="/?v=hotel">Hotels</a></li>
            <li><a href="/?v=vacation">Vacation Homes</a></li>
            <li><a href="/?v=re">Real Estate</a></li>
            <li><a href="#">Projects</a></li>
            <li><a href="#">Map search</a></li>
        </ul>
    </div>

    {{-- Host --}}
    <div>
        <h4>Host</h4>
        <ul>
            <li><a href="#">List your property</a></li>
            <li><a href="#">Host dashboard</a></li>
            <li><a href="#">Pricing tools</a></li>
            <li><a href="#">Host resources</a></li>
        </ul>
    </div>

    {{-- Company --}}
    <div>
        <h4>Company</h4>
        <ul>
            <li><a href="#">About Ashome</a></li>
            <li><a href="#">Newsroom</a></li>
            <li><a href="#">Careers</a></li>
            <li><a href="#">Blog</a></li>
        </ul>
    </div>

    {{-- Support --}}
    <div>
        <h4>Support</h4>
        <ul>
            <li><a href="#">Help center</a></li>
            <li><a href="#">Contact us</a></li>
            <li><a href="{{ route('customer-privacy-policy') }}">Privacy policy</a></li>
            <li><a href="{{ route('customer-terms-conditions') }}">Terms of service</a></li>
        </ul>
    </div>

    {{-- Footer bottom bar (spans all 5 columns) --}}
    <div class="web-footer-bottom">
        <span>© {{ date('Y') }} Ashome. All rights reserved.</span>
        <span style="display:flex; gap:16px;">
            <a href="#">العربية</a>
            <a href="#">EGP</a>
            <a href="#">Sitemap</a>
        </span>
    </div>
</footer>

@endsection
