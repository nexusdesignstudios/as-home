<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Send Money')</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .header {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            color: #333;
            font-size: 2em;
            font-weight: 300;
        }
        .content {
            padding: 40px 20px;
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="header">
        <h1>Send Money</h1>
    </div>

    <div class="content">
        @yield('content')
    </div>

    @yield('scripts')
</body>
</html>
