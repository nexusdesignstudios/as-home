<!DOCTYPE html>

@if($language)
    @if ($language->rtl)
        <html lang="en" dir="rtl">
    @else
        <html lang="en">
    @endif
@else
    <html lang="en">
@endif

<head>
    @php $gaId = optional(\App\Models\Setting::where('type', 'google_analytics_id')->first())->data; @endphp
    @if($gaId)
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $gaId }}');
    </script>
    <!-- Event snippet for Subscribe conversion page -->
    <script>
    function gtag_report_conversion(url) {
      var callback = function () {
        if (typeof(url) != 'undefined') {
          window.location = url;
        }
      };
      gtag('event', 'conversion', {
          'send_to': 'AW-17844407850/6KwsCNuXotobEKqc8LxC',
          'event_callback': callback
      });
      return false;
    }
    </script>
    @endif
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @php
        $faviconFile = system_setting('favicon_icon');
        $faviconPath = !empty($faviconFile) ? public_path('assets/images/logo/' . $faviconFile) : null;
        $faviconUrl = (!empty($faviconFile) && $faviconPath && file_exists($faviconPath))
            ? url('assets/images/logo/' . $faviconFile)
            : url('favicon.ico');
    @endphp
    <link rel="shortcut icon" href="{{ $faviconUrl }}" type="image/x-icon">
    <title>@yield('title') || {{ config('app.name') }}</title>
    <meta name="description" content="@yield('meta_description', 'Ashome — Find hotels, vacation rentals, and real estate in Egypt')">
    <meta property="og:title" content="@yield('og_title', config('app.name'))">
    <meta property="og:description" content="@yield('og_description', 'Ashome — Find hotels, vacation rentals, and real estate in Egypt')">
    <meta property="og:type" content="website">
    <link rel="canonical" href="{{ request()->url() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    @include('layouts.include')
    @yield('css')
</head>

<body>
    <div id="app">
        @include('layouts.sidebar')

        <div id="main" class='layout-navbar'>
            @include('layouts.topbar')
            <div id="main-content">
                <div class="page-heading">

                    @yield('page-title')
                </div>
                @yield('content')

            </div>

        </div>
        <div class="wrapper mt-5">
            <div class="content">
                @include('layouts.footer')

                <!-- Your page content here -->
            </div>
        </div>
        {{-- <div>
            @include('layouts.footer')
        </div> --}}
    </div>

    @include('layouts.footer_script')
    @yield('js')
    @yield('script')
</body>

</html>
