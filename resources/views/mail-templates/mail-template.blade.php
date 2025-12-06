<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title }}</title>
</head>
<body>
    {!! $email_template !!}
    
    @if(!empty($logo_url))
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e9ecef; text-align: center;">
        <img src="{{ $logo_url }}" alt="As-home Logo" style="max-width: 200px; max-height: 80px; height: auto; margin: 20px auto; display: block;" />
    </div>
    @endif
</body>
</html>
