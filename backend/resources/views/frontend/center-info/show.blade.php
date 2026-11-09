@php
    $isArabic = str_starts_with(app()->getLocale(), 'ar');
    // Dashboard links are free-text fields; only expose HTTP(S) destinations.
    $publicLink = static function ($value) {
        $value = trim((string) $value);
        return filter_var($value, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)
                ? $value : null;
    };
    $phoneLink = static function ($value) {
        $number = preg_replace('/[^0-9+]/', '', (string) $value);
        return preg_match('/^\+?[0-9]+$/', $number) ? 'tel:'.$number : null;
    };
    $whatsapp = $publicLink($centerInfo->whatsapp_url);
    $map = $publicLink($centerInfo->map_url);
    $socialLinks = collect([
        ['name' => __('center_info.whatsapp'), 'url' => $whatsapp],
        ['name' => 'Instagram', 'url' => $publicLink($centerInfo->instagram_url)],
        ['name' => 'Facebook', 'url' => $publicLink($centerInfo->facebook_url)],
        ['name' => 'X', 'url' => $publicLink($centerInfo->x_url)],
        ['name' => 'TikTok', 'url' => $publicLink($centerInfo->tiktok_url)],
    ])->filter(fn ($link) => $link['url']);
    $phones = collect([
        ['label' => __('center_info.customer_service_phone'), 'value' => $centerInfo->customer_service_phone],
        ['label' => __('center_info.support_phone'), 'value' => $centerInfo->support_phone],
    ])->filter(fn ($phone) => filled($phone['value']));
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#182d29">
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $centerInfo->about), 160) }}">
    <title>{{ $centerInfo->title }}</title>
    <style>
        :root { color-scheme: light; --ink: #182d29; --muted: #65716b; --gold: #b39660; --border: #e7e7df; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f5f4ef; color: var(--ink); font-family: Tahoma, Arial, sans-serif; line-height: 1.9; }
        a { color: inherit; text-decoration: none; }
        a:focus-visible { outline: 3px solid var(--gold); outline-offset: 5px; }
        .shell { width: min(100% - 32px, 1080px); margin: 32px auto; }
        .hero { overflow: hidden; background: white; border: 1px solid var(--border); border-radius: 24px; }
        .cover { height: clamp(180px, 30vw, 340px); background: var(--ink); }
        .cover img { display: block; width: 100%; height: 100%; object-fit: cover; }
        .cover-placeholder { background: radial-gradient(ellipse at top right, #496258, transparent 70%), var(--ink); }
        .identity { position: relative; padding: 0 36px 32px; }
        .avatar { display: block; position: relative; width: 120px; height: 120px; margin-top: -60px; border: 6px solid white; border-radius: 24px; background: white; object-fit: cover; }
        h1 { margin: 18px 0 12px; font-size: clamp(26px, 4vw, 38px); line-height: 1.5; overflow-wrap: anywhere; }
        h2 { margin: 0 0 16px; font-size: 20px; }
        .eyebrow { color: var(--muted); margin: 16px 0 4px; font-size: 13px; }
        .actions, .socials { display: flex; flex-wrap: wrap; gap: 12px; }
        .actions { margin-top: 24px; }
        .button { display: inline-flex; align-items: center; justify-content: center; padding: 10px 22px; border: 1px solid var(--border); border-radius: 12px; font-weight: bold; background: white; }
        .button-primary { background: var(--ink); color: white; border-color: var(--ink); }
        .button:hover, .social:hover { border-color: var(--gold); }
        .grid { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 24px; margin-top: 24px; align-items: start; }
        .column { display: grid; gap: 24px; min-width: 0; }
        .card { padding: 28px; border: 1px solid var(--border); border-radius: 20px; background: white; }
        .text { margin: 0; white-space: pre-wrap; overflow-wrap: anywhere; color: #4d5953; }
        .contact + .contact { border-top: 1px solid var(--border); margin-top: 18px; padding-top: 18px; }
        .label { display: block; color: var(--muted); font-size: 13px; margin-bottom: 4px; }
        .phone { font-size: 20px; font-weight: bold; overflow-wrap: anywhere; }
        .social { border: 1px solid var(--border); border-radius: 10px; padding: 7px 14px; }
        .map-link { margin-top: 18px; }
        footer { text-align: center; color: var(--muted); padding: 28px 0 8px; font-size: 13px; overflow-wrap: anywhere; }
        @media (max-width: 700px) {
            .shell { margin: 16px auto; }
            .grid { grid-template-columns: 1fr; gap: 16px; }
            .column { gap: 16px; }
            .identity { padding: 0 22px 24px; }
            .avatar { width: 96px; height: 96px; margin-top: -48px; }
            .card { padding: 22px; }
            .actions .button { flex: 1 1 auto; }
        }
    </style>
</head>
<body>
    <main class="shell">
        <header class="hero">
            <div class="cover {{ $centerInfo->cover_image ? '' : 'cover-placeholder' }}">
                @if ($centerInfo->cover_image)
                    <img src="{{ $centerInfo->cover_image_url }}" alt="" fetchpriority="high">
                @endif
            </div>
            <div class="identity">
                <img class="avatar" src="{{ $centerInfo->profile_image_url }}" alt="{{ $centerInfo->title }}" width="120" height="120">
                <h1>{{ $centerInfo->title }}</h1>
                @if ($whatsapp || $map || $phoneLink($centerInfo->customer_service_phone))
                    <nav class="actions" aria-label="{{ __('center_info.quick_links') }}">
                        @if ($whatsapp)
                            <a class="button button-primary" href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer">{{ __('center_info.whatsapp') }}</a>
                        @endif
                        @if ($phoneLink($centerInfo->customer_service_phone))
                            <a class="button" href="{{ $phoneLink($centerInfo->customer_service_phone) }}">{{ __('center_info.customer_service_phone') }}</a>
                        @endif
                        @if ($map)
                            <a class="button" href="{{ $map }}" target="_blank" rel="noopener noreferrer">{{ __('center_info.open_location') }}</a>
                        @endif
                    </nav>
                @endif
            </div>
        </header>

        <div class="grid">
            <div class="column">
                @foreach (['about', 'services_info', 'extra_info'] as $field)
                    @if (filled($centerInfo->{$field}))
                        <section class="card">
                            <h2>{{ __('center_info.'.$field) }}</h2>
                            <p class="text" dir="auto">{{ $centerInfo->{$field} }}</p>
                        </section>
                    @endif
                @endforeach
            </div>
            <div class="column">
                @if ($phones->isNotEmpty())
                    <section class="card">
                        <h2>{{ __('center_info.contact_info') }}</h2>
                        @foreach ($phones as $phone)
                            <div class="contact">
                                <span class="label">{{ $phone['label'] }}</span>
                                @if ($phoneLink($phone['value']))
                                    <a class="phone" href="{{ $phoneLink($phone['value']) }}"><bdi dir="ltr">{{ $phone['value'] }}</bdi></a>
                                @else
                                    <span class="phone"><bdi>{{ $phone['value'] }}</bdi></span>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endif
                @if (filled($centerInfo->address) || $map)
                    <section class="card">
                        <h2>{{ __('center_info.location') }}</h2>
                        @if (filled($centerInfo->address))
                            <p class="text" dir="auto">{{ $centerInfo->address }}</p>
                        @endif
                        @if ($map)
                            <a class="button map-link" href="{{ $map }}" target="_blank" rel="noopener noreferrer">{{ __('center_info.open_location') }} &larr;</a>
                        @endif
                    </section>
                @endif
                @if ($socialLinks->isNotEmpty())
                    <section class="card">
                        <h2>{{ __('center_info.quick_links') }}</h2>
                        <nav class="socials" aria-label="{{ __('center_info.quick_links') }}">
                            @foreach ($socialLinks as $link)
                                <a class="social" href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['name'] }}</a>
                            @endforeach
                        </nav>
                    </section>
                @endif
            </div>
        </div>
        <footer>{{ $centerInfo->title }} &copy; {{ date('Y') }}</footer>
    </main>
</body>
</html>
