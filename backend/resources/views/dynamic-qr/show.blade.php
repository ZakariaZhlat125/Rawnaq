<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code->title }}</title>

    <style>
        body {
            margin: 0;
            padding: 24px;
            background: #f5f5f5;
            font-family: sans-serif;
        }

        main {
            max-width: 760px;
            margin: 40px auto;
            padding: 24px;
            background: white;
            border-radius: 16px;
        }

        .content {
            overflow-wrap: anywhere;
            line-height: 1.9;
        }
        .content p { margin: 0 0 1em; }
        .content ul, .content ol { padding-inline-start: 28px; }
        .content a { color: #99651d; }
        @media (max-width: 600px) {
            body { padding: 12px; }
            main { margin: 16px auto; padding: 18px; }
        }
    </style>
</head>
<body>
    <main>
        <h1>{{ $code->title }}</h1>
        <div class="content">{!! app(\App\Services\QrTextFormatter::class)->format($code->content) !!}</div>
    </main>
</body>
</html>