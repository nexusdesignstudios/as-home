<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sign in') — Ashome</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @yield('css')
</head>
<body class="ash-web" style="margin:0; padding:0; min-height:100vh;">
    @yield('content')
    @yield('js')
</body>
</html>
