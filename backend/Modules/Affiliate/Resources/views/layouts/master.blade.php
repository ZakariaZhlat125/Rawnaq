<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ language_direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | {{ app_name() }}</title>

    <link rel="stylesheet" href="{{ mix('css/libs.min.css') }}">
    <style>
        :root {
            --af-primary: #1f2937;
            --af-muted: #6b7280;
            --af-accent: #cf9233;
            --af-accent-2: #bf9456;
            --af-accent-soft: #fff7eb;
            --af-border: #ece5d8;
            --af-bg: #f8f6f1;
            --af-surface: #ffffff;
            --af-dark: #1f1f1f;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: var(--af-primary);
            font-family: "Tajawal", "Segoe UI", Tahoma, sans-serif;
            background:
                radial-gradient(circle at 5% 0%, #fff9ee 0%, transparent 35%),
                radial-gradient(circle at 95% 0%, #fff6e8 0%, transparent 30%),
                var(--af-bg);
        }

        .af-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 290px 1fr;
            gap: 0;
        }

        .af-sidebar {
            background: linear-gradient(180deg, #222222 0%, #171717 100%);
            color: #fff;
            padding: 24px 14px;
            border-inline-end: 1px solid rgba(207, 146, 51, 0.25);
            position: sticky;
            top: 0;
            height: 100vh;
            box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.02), 0 24px 40px rgba(0, 0, 0, 0.28);
        }

        .af-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.10);
            margin-bottom: 16px;
        }

        .af-brand img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            border-radius: 10px;
            background: #ffffff;
            padding: 6px;
        }

        .af-brand strong {
            font-size: 18px;
            letter-spacing: 0.2px;
        }

        .af-brand small {
            color: #c9b089;
            display: block;
            margin-top: 2px;
            font-size: 12px;
        }

        .af-nav {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .af-nav li {
            margin-bottom: 8px;
        }

        .af-nav a {
            display: block;
            color: #dadada;
            text-decoration: none;
            padding: 12px 14px;
            border-radius: 12px;
            font-weight: 600;
            border: 1px solid transparent;
            transition: .22s ease;
            position: relative;
            overflow: hidden;
        }

        .af-nav a:hover {
            color: #fff;
            border-color: rgba(207, 146, 51, 0.35);
            background: linear-gradient(90deg, rgba(207, 146, 51, 0.20), rgba(207, 146, 51, 0.05));
        }

        .af-nav .is-active a {
            color: #fff;
            border-color: rgba(207, 146, 51, 0.45);
            background: linear-gradient(90deg, rgba(207, 146, 51, 0.34), rgba(191, 148, 86, 0.12));
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.06);
        }

        .af-main {
            min-width: 0;
            padding: 14px 14px 18px;
        }

        .af-topbar {
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid var(--af-border);
            border-radius: 16px;
            backdrop-filter: blur(8px);
            padding: 0 18px;
            position: sticky;
            top: 14px;
            z-index: 20;
            box-shadow: 0 10px 26px rgba(17, 24, 39, 0.06);
        }

        .af-title {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            color: #2f2f2f;
        }

        .af-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .af-user-badge {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--af-accent), var(--af-accent-2));
            color: #fff;
            font-weight: 800;
            border: 2px solid #fff;
            box-shadow: 0 6px 18px rgba(207, 146, 51, 0.4);
        }

        .af-logout {
            border: 0;
            padding: 9px 14px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--af-accent), var(--af-accent-2));
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(207, 146, 51, 0.25);
        }

        .af-logout:hover {
            filter: brightness(1.03);
        }

        .af-content {
            margin-top: 14px;
            background: var(--af-surface);
            border: 1px solid var(--af-border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 10px 28px rgba(17, 24, 39, 0.04);
        }

        .af-content .card {
            border: 1px solid #f0e7d8;
            border-radius: 14px;
            box-shadow: 0 8px 22px rgba(17, 24, 39, 0.05);
        }

        .af-content .card-header {
            background: linear-gradient(180deg, #fffefb 0%, #fff8ed 100%);
            border-bottom: 1px solid #f3e5ca;
            border-radius: 14px 14px 0 0;
        }

        .af-content .btn-primary {
            background: linear-gradient(135deg, var(--af-accent), var(--af-accent-2));
            border: none;
        }

        .af-toggle {
            display: none;
            border: 1px solid var(--af-border);
            background: #fff;
            border-radius: 9px;
            padding: 6px 11px;
            color: #333;
        }

        @media (max-width: 992px) {
            .af-shell {
                grid-template-columns: 1fr;
            }

            .af-sidebar {
                position: fixed;
                inset-inline-start: -300px;
                top: 0;
                width: 290px;
                z-index: 50;
                transition: .22s ease;
            }

            .af-sidebar.is-open {
                inset-inline-start: 0;
            }

            .af-toggle {
                display: inline-block;
            }

            .af-main {
                padding: 10px;
            }

            .af-topbar {
                top: 10px;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="af-shell">
    <aside id="affiliateSidebar" class="af-sidebar">
        <div class="af-brand">
            <img src="{{ asset('images/samilogo.png') }}" alt="logo">
            <div>
                <strong>{{ app_name() }}</strong>
                <small>{{ __('affiliate.affiliate_area') }}</small>
            </div>
        </div>

        <ul class="af-nav">
            <li class="{{ request()->routeIs('affiliate.dashboard') ? 'is-active' : '' }}">
                <a href="{{ route('affiliate.dashboard') }}">{{ __('affiliate.menu_dashboard') }}</a>
            </li>
            <li class="{{ request()->routeIs('affiliate.stats') ? 'is-active' : '' }}">
                <a href="{{ route('affiliate.stats') }}">{{ __('affiliate.menu_stats') }}</a>
            </li>
            <li class="{{ request()->routeIs('affiliate.conversions') ? 'is-active' : '' }}">
                <a href="{{ route('affiliate.conversions') }}">{{ __('affiliate.menu_conversions') }}</a>
            </li>
        </ul>
    </aside>

    <main class="af-main">
        <header class="af-topbar">
            <div style="display:flex;align-items:center;gap:10px;">
                <button id="affiliateSidebarToggle" class="af-toggle" type="button">&#9776;</button>
                <h1 class="af-title">@yield('title')</h1>
            </div>
            <div class="af-user">
                <span class="af-user-badge">{{ strtoupper(substr(auth()->user()->first_name ?? 'A', 0, 1)) }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="af-logout" type="submit">{{ __('messages.logout') }}</button>
                </form>
            </div>
        </header>

        <section class="af-content">
            @yield('content')
        </section>
    </main>
</div>

<script>
    (function () {
        const toggleBtn = document.getElementById('affiliateSidebarToggle');
        const sidebar = document.getElementById('affiliateSidebar');
        if (!toggleBtn || !sidebar) return;
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
        });
    })();
</script>
@stack('scripts')
</body>
</html>
