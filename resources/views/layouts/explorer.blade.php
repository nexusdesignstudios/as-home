<!DOCTYPE html>
@php
    // Active nav item: child views set @section('explorer_nav_active', 'rsv') etc.
    // Sections are populated before the parent layout renders in Blade's lifecycle.
    $navActive = $__env->yieldContent('explorer_nav_active', 'home');

    $navItems = [
        ['id' => 'home',  'label' => 'Discover',    'href' => '#'],
        ['id' => 'trips', 'label' => 'My trips',     'href' => '#'],
        ['id' => 'rsv',   'label' => 'Reservations', 'href' => route('explorer.reservations.index')],
        ['id' => 'saved', 'label' => 'Saved',         'href' => '#'],
        ['id' => 'inbox', 'label' => 'Inbox',         'href' => '#'],
        ['id' => 'loyal', 'label' => 'Loyalty',       'href' => '#'],
    ];

    $gaId = optional(\App\Models\Setting::where('type', 'google_analytics_id')->first())->data;
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @if($gaId)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $gaId }}');
    </script>
    @endif

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Ashome — Discover &amp; Book')</title>
    <meta name="description" content="@yield('meta_description', 'Ashome — Find hotels, vacation rentals, and real estate in Egypt')">
    <meta property="og:title" content="@yield('og_title', 'Ashome')">
    <meta property="og:type" content="website">
    <link rel="canonical" href="{{ request()->url() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @yield('css')

    <style>
        /* ── Explorer shell resets & base ──────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body.explorer-body {
            min-height: 100vh;
            background: #FAF7F0;
            color: #1A1B1F;
            font-family: 'Inter', 'Helvetica Neue', sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ── Header ────────────────────────────────────────────── */
        .exp-header {
            display: flex;
            align-items: center;
            padding: 22px 56px;
            border-bottom: 1px solid #EAE7E0;
            background: #FFFEFA;
            gap: 48px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .exp-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }
        .exp-brand img {
            height: 30px;
            width: auto;
        }
        .exp-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px 5px 8px;
            border: 1px solid #4A7FA8;
            background: transparent;
            color: #4A7FA8;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: .2em;
            text-transform: uppercase;
        }

        /* ── Nav ────────────────────────────────────────────────── */
        .exp-nav {
            display: flex;
            gap: 32px;
            margin-left: 24px;
        }
        .exp-nav a {
            text-decoration: none;
            font-size: 13px;
            padding-bottom: 6px;
            border-bottom: 1.5px solid transparent;
            transition: color .15s, border-color .15s;
            white-space: nowrap;
        }
        .exp-nav a.active {
            color: #1A1B1F;
            font-weight: 600;
            border-bottom-color: #4A7FA8;
        }
        .exp-nav a:not(.active) {
            color: #5A5C63;
            font-weight: 500;
        }

        /* ── Header right ───────────────────────────────────────── */
        .exp-spacer { flex: 1; }
        .exp-btn-search {
            background: transparent;
            border: none;
            color: #5A5C63;
            cursor: pointer;
            padding: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-family: inherit;
        }
        .exp-btn-host {
            background: transparent;
            color: #1A1B1F;
            border: 1px solid #EAE7E0;
            padding: 10px 18px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            margin-left: 20px;
            font-family: inherit;
            text-decoration: none;
            white-space: nowrap;
        }
        .exp-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4A7FA8, #1E4A6F);
            color: #FFFEFA;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
            margin-left: 14px;
            flex-shrink: 0;
        }
        .exp-btn-login {
            color: #1A1B1F;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            margin-left: 20px;
        }
        .exp-btn-register {
            background: #0B1628;
            color: #FFFEFA;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            padding: 10px 18px;
            border-radius: 999px;
            margin-left: 10px;
        }

        /* ── Main content ───────────────────────────────────────── */
        .exp-main {
            padding: 56px 56px 80px;
            max-width: 1360px;
            margin: 0 auto;
        }

        /* ── Explorer display + label helpers ───────────────────── */
        .exp-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: #9AA0A6;
        }
        .exp-display {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-weight: 500;
            line-height: 1.05;
            letter-spacing: -.01em;
        }
    </style>
</head>

<body class="explorer-body">

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <header class="exp-header">

        {{-- Brand + Explorer badge --}}
        <div class="exp-brand">
            {{-- Logo: public/images/logo.png — FLAG: file is missing, see deployment notes --}}
            <img src="{{ asset('images/logo.png') }}" alt="Ashome" onerror="this.style.display='none'">
            <span class="exp-badge">
                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4-4"/>
                </svg>
                Explorer
            </span>
        </div>

        {{-- Primary nav --}}
        <nav class="exp-nav">
            @foreach($navItems as $item)
                <a href="{{ $item['href'] }}" class="{{ $navActive === $item['id'] ? 'active' : '' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="exp-spacer"></div>

        {{-- Search --}}
        <button class="exp-btn-search" type="button">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <circle cx="11" cy="11" r="7"/>
                <path d="m21 21-4-4"/>
            </svg>
            Search
        </button>

        {{-- Switch to host --}}
        <a href="#" class="exp-btn-host">Switch to host</a>

        {{-- Auth: avatar or login/register --}}
        @auth
            @php
                $initials = collect(explode(' ', auth()->user()->name ?? ''))
                    ->map(fn($w) => strtoupper($w[0] ?? ''))
                    ->filter()
                    ->take(2)
                    ->join('');
            @endphp
            <div class="exp-avatar" title="{{ auth()->user()->name }}">{{ $initials ?: 'AY' }}</div>
        @else
            <a href="{{ route('login') }}" class="exp-btn-login">Login</a>
            <a href="{{ route('register') }}" class="exp-btn-register">Register</a>
        @endauth

    </header>

    {{-- ── Page content ──────────────────────────────────────────── --}}
    <main class="exp-main">
        @yield('content')
    </main>

    @yield('js')
    @yield('script')

</body>
</html>
