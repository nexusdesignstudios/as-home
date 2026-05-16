<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Checkout') — Ashome</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @yield('css')
</head>
<body class="ash-web" style="background:#FAFAFA; min-height:100vh;">

    @php
        $checkoutCurrentStep = match($step ?? 'review') {
            'payment' => 3,
            'done'    => 4,
            default   => 2,   // 'review'
        };
        $checkoutStepLabels = ['Listing', 'Review', 'Payment'];
    @endphp

    <nav class="checkout-nav">
        <a href="{{ route('explorer.home') }}" class="brand">
            <img src="{{ asset('images/logo.png') }}" alt="Ashome" onerror="this.style.display='none'">
            <span>As-home</span>
        </a>

        <div class="steps">
            @foreach($checkoutStepLabels as $i => $label)
                @php
                    $num      = $i + 1;
                    $isDone   = $num < $checkoutCurrentStep;
                    $isActive = $num === $checkoutCurrentStep;
                @endphp
                <div class="step {{ $isDone ? 'done' : '' }} {{ $isActive ? 'active' : '' }}">
                    <span class="num">{{ $isDone ? '✓' : $num }}</span>
                    <span class="lbl">{{ $label }}</span>
                    @if($i < count($checkoutStepLabels) - 1)
                        <span class="bar"></span>
                    @endif
                </div>
            @endforeach
        </div>

        <a href="#" class="help">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M9 9a3 3 0 1 1 4 2.8c-.9.3-1 .9-1 1.7M12 17h.01"/>
            </svg>
            Need help?
        </a>
    </nav>

    @yield('content')

    @yield('js')

</body>
</html>
