@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
<div class="auth-split">

    {{-- ── LEFT PANEL: editorial navy ──────────────────────────────── --}}
    <div class="auth-left">
        <div class="auth-left-glow"></div>
        <div class="auth-left-brand">as-home</div>

        <div class="auth-left-body">
            <div class="auth-left-label">Welcome back</div>
            <div class="auth-left-display">
                Stays,<br>homes,<br><em>forever.</em>
            </div>
            <div class="auth-left-sub">
                One account for vacation homes, hotels and real estate across Egypt.
                Sign in to continue where you left off.
            </div>
        </div>

        <div class="auth-left-footer">
            <span>EN · العربية</span>
            <span>EGP</span>
            <span style="flex:1;"></span>
            <span>© as-home 2026</span>
        </div>
    </div>

    {{-- ── RIGHT PANEL: form ────────────────────────────────────────── --}}
    <div class="auth-right">
        <div class="auth-right-top">
            New here?
            <a href="{{ route('customer.register') }}" style="margin-left:4px;">Create an account</a>
        </div>

        <div class="auth-form-wrap">
            <div class="auth-label">Sign in</div>
            <div class="auth-display">Welcome back.</div>
            <div class="auth-sub">Enter your email and password to continue.</div>

            @if($errors->any())
            <div class="auth-error-box" style="margin-top:20px;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
            @endif

            @if(session('status'))
            <div style="background:#E8F5EA; border:1px solid #C5E2C8; border-radius:10px; padding:12px 16px; font-size:13px; color:#1E5028; margin-top:20px;">
                {{ session('status') }}
            </div>
            @endif

            <form action="{{ route('customer.login') }}" method="POST" style="margin-top:32px;">
                @csrf

                <div style="margin-bottom:16px;">
                    <div class="auth-field-label">Email address</div>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        class="auth-input"
                        placeholder="you@example.com"
                        style="font-family:var(--ash-font-sans); font-size:16px;"
                    >
                    @error('email')
                        <div class="auth-error">{{ $message }}</div>
                    @enderror
                </div>

                <div style="margin-bottom:20px;">
                    <div class="auth-field-label">Password</div>
                    <input
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="auth-input"
                        placeholder="••••••••"
                        style="font-family:var(--ash-font-sans); font-size:16px;"
                    >
                    @error('password')
                        <div class="auth-error">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="auth-btn">Sign in</button>
            </form>

            <div class="auth-divider">
                <hr><span>OR CONTINUE WITH</span><hr>
            </div>

            <div class="auth-social-grid">
                <button type="button" class="auth-social-btn">
                    <span style="width:14px; height:14px; border-radius:50%; background:linear-gradient(45deg,#0C447C,#3E8C8A); flex-shrink:0;"></span>
                    Google
                </button>
                <button type="button" class="auth-social-btn">
                    <svg width="14" height="14" viewBox="0 0 814 1000" fill="currentColor"><path d="M788.1 340.9c-5.8 4.5-108.2 62.2-108.2 190.5 0 148.4 130.3 200.9 134.2 202.2-.6 3.2-20.7 71.9-68.7 141.9-42.8 61.6-87.5 123.1-155.5 123.1s-85.5-39.5-164-39.5c-76.5 0-103.7 40.8-165.9 40.8s-105-43.4-150.3-107.2c-51.7-71.3-93.2-183.7-93.2-289.1C232.6 286.3 415 137 588 137c111.8 0 185.7 48.5 218.2 77.2zm-176 -213c-41.7 48.5-111.8 86.3-182.6 86.3-11.5 0-23.1-1.3-29.5-2.6 0-3.8 0-7.7 0-11.5 0-143.3 106.9-238.7 214.5-254.6 1.3 0 2.6 0 3.8 0 0 4.5 0 9 0 13.5 0 44.7-10.3 93.2-6.2 168.9z"/></svg>
                    Apple
                </button>
            </div>

            <div class="auth-legal">
                By signing in you agree to our
                <a href="{{ route('customer-terms-conditions') }}">Terms of Service</a> and
                <a href="{{ route('customer-privacy-policy') }}">Privacy Policy</a>.
                as-home protects your data per Egyptian data protection law 151/2020.
            </div>
        </div>
    </div>

</div>
@endsection
