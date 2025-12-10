<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title }}</title>
    <style>
        /* Ensure no content limits in PDF */
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        /* Allow content to flow across multiple pages */
        * {
            page-break-inside: avoid;
        }
        p, div {
            page-break-inside: avoid;
            orphans: 3;
            widows: 3;
        }
        /* Ensure tables don't break awkwardly */
        table {
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        /* Allow long content without truncation */
        .pdf-only-content {
            display: block !important;
        }
    </style>
</head>
<body>
    {!! $email_template !!}
    
    @if(!empty($logo_url) || !empty($logo_base64))
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e9ecef; text-align: center;">
        @if(isset($use_base64_logo) && $use_base64_logo && !empty($logo_base64))
            {{-- Use base64 for email (embedded, works in all email clients) --}}
            <img src="{{ $logo_base64 }}" alt="As-home Logo" style="max-width: 200px; max-height: 80px; height: auto; margin: 20px auto; display: block;" />
        @elseif(!empty($logo_url))
            {{-- Use URL for PDF (PDF generators can access URLs) --}}
            <img src="{{ $logo_url }}" alt="As-home Logo" style="max-width: 200px; max-height: 80px; height: auto; margin: 20px auto; display: block;" />
        @endif
    </div>
    @endif
</body>
</html>
