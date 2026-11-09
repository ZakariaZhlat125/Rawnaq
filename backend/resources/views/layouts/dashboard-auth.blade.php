<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ language_direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', app_name()) | {{ app_name() }}</title>

    <link rel="stylesheet" href="{{ mix('css/libs.min.css') }}">
    <link rel="stylesheet" href="{{ mix('css/backend.css') }}">
    <link rel="stylesheet" href="{{ asset('custom-css/dashboard.css') }}">
    @if (language_direction() == 'rtl')
        <link rel="stylesheet" href="{{ asset('css/rtl.css') }}">
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background: #f6f7f9;
            font-family: 'Almarai', sans-serif;
        }

        .dashboard-auth-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .dashboard-auth-header {
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(17, 24, 39, 0.08);
            background: #fff;
        }

        .dashboard-auth-brand {
            color: #1f2937 !important;
            font-size: 20px;
            font-weight: 800;
            text-decoration: none;
        }

        .dashboard-auth-main {
            flex: 1;
        }
    </style>
    @stack('after-styles')
</head>
<body>
    <div class="dashboard-auth-shell">
        <header class="dashboard-auth-header">
            <a href="{{ url('/app') }}" class="dashboard-auth-brand">{{ app_name() }}</a>
        </header>

        <main class="dashboard-auth-main">
            @yield('content')
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-center',
            timeOut: 7000,
            extendedTimeOut: 1000
        };

        window.createNotify = function ({ title = '', desc = '', type = 'info' }) {
            const message = title ? `${title}: ${desc}` : desc;
            if (typeof toastr[type] === 'function') {
                toastr[type](desc, title);
                return;
            }
            toastr.info(message);
        };

        @if (session('message'))
            toastr.success(@json(session('message')));
        @endif

        @if (session('success'))
            toastr.success(@json(session('success')));
        @endif

        @if (session('error'))
            toastr.error(@json(session('error')));
        @endif

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                toastr.error(@json($error));
            @endforeach
        @endif

        if (localStorage.getItem('flash_message')) {
            toastr.success(localStorage.getItem('flash_message'));
            localStorage.removeItem('flash_message');
        }
    </script>
    @stack('after-scripts')
</body>
</html>
