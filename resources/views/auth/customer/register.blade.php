@extends('layouts.auth')

@section('title', 'Create account')

@section('content')
<div class="auth-split">

    {{-- ── LEFT PANEL ───────────────────────────────────────────────── --}}
    <div class="auth-left">
        <div class="auth-left-glow"></div>
        <div class="auth-left-brand">as-home</div>

        <div class="auth-left-body">
            <div class="auth-left-label">Join us</div>
            <div class="auth-left-display">
                Find your<br>perfect<br><em>stay.</em>
            </div>
            <div class="auth-left-sub">
                Hotels, vacation homes, and real estate — all in one place.
                Create your free account in under a minute.
            </div>
        </div>

        <div class="auth-left-footer">
            <span>EN · العربية</span>
            <span>EGP</span>
            <span style="flex:1;"></span>
            <span>© as-home 2026</span>
        </div>
    </div>

    {{-- ── RIGHT PANEL ──────────────────────────────────────────────── --}}
    <div class="auth-right">
        <div class="auth-right-top">
            Already have an account?
            <a href="{{ route('customer.login') }}" style="margin-left:4px;">Sign in</a>
        </div>

        <div class="auth-form-wrap">
            <div class="auth-label">Create account</div>
            <div class="auth-display" style="font-size:42px;">Start your journey.</div>

            @if($errors->any())
            <div class="auth-error-box" style="margin-top:20px;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
            @endif

            <form action="{{ route('customer.register') }}" method="POST" style="margin-top:28px;">
                @csrf

                <div class="auth-form-grid">

                    {{-- First name --}}
                    <div>
                        <div class="auth-field-label">First name</div>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" required
                            autocomplete="given-name" class="auth-input sm" placeholder="Sara">
                        @error('first_name')<div class="auth-error">{{ $message }}</div>@enderror
                    </div>

                    {{-- Last name --}}
                    <div>
                        <div class="auth-field-label">Last name</div>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required
                            autocomplete="family-name" class="auth-input sm" placeholder="Mostafa">
                        @error('last_name')<div class="auth-error">{{ $message }}</div>@enderror
                    </div>

                    {{-- Email --}}
                    <div class="full">
                        <div class="auth-field-label">Email address</div>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            autocomplete="email" class="auth-input sm" placeholder="you@example.com">
                        @error('email')<div class="auth-error">{{ $message }}</div>@enderror
                    </div>

                    {{-- Phone --}}
                    <div>
                        <div class="auth-field-label">Phone</div>
                        <input type="tel" name="mobile" value="{{ old('mobile') }}" required
                            autocomplete="tel" class="auth-input sm" placeholder="+20 1XX XXX XXXX">
                        @error('mobile')<div class="auth-error">{{ $message }}</div>@enderror
                    </div>

                    {{-- Nationality --}}
                    <div>
                        <div class="auth-field-label">Nationality</div>
                        <select name="nationality" class="auth-input sm" style="appearance:none;">
                            <option value="">Select…</option>
                            @foreach([
                                'Egyptian'      => '🇪🇬 Egyptian',
                                'Saudi Arabian' => '🇸🇦 Saudi Arabian',
                                'Emirati'       => '🇦🇪 Emirati',
                                'Kuwaiti'       => '🇰🇼 Kuwaiti',
                                'British'       => '🇬🇧 British',
                                'American'      => '🇺🇸 American',
                                'Other'         => 'Other',
                            ] as $val => $lbl)
                                <option value="{{ $val }}" {{ old('nationality') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        @error('nationality')<div class="auth-error">{{ $message }}</div>@enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <div class="auth-field-label">Password</div>
                        <input type="password" name="password" required
                            autocomplete="new-password" class="auth-input sm" placeholder="Min 8 characters">
                        @error('password')<div class="auth-error">{{ $message }}</div>@enderror
                    </div>

                    {{-- Confirm password --}}
                    <div>
                        <div class="auth-field-label">Confirm password</div>
                        <input type="password" name="password_confirmation" required
                            autocomplete="new-password" class="auth-input sm" placeholder="Repeat password">
                    </div>

                </div>

                {{-- Agreement checkbox --}}
                <label style="display:flex; align-items:flex-start; gap:10px; margin-top:18px; cursor:pointer; font-size:12px; color:var(--ash-text-secondary); line-height:1.5;">
                    <input type="checkbox" name="agree" value="1" required style="margin-top:2px; flex-shrink:0;">
                    I agree to the
                    <a href="{{ route('customer-terms-conditions') }}" style="color:var(--ash-navy-900); margin-left:3px;">Terms of Service</a>
                    &amp;&nbsp;<a href="{{ route('customer-privacy-policy') }}" style="color:var(--ash-navy-900);">Privacy Policy</a>
                </label>
                @error('agree')<div class="auth-error">{{ $message }}</div>@enderror

                <button type="submit" class="auth-btn" style="margin-top:20px;">
                    Create account →
                </button>
            </form>

            <div class="auth-legal" style="margin-top:20px;">
                as-home protects your data per Egyptian data protection law 151/2020.
            </div>
        </div>
    </div>

</div>
@endsection
