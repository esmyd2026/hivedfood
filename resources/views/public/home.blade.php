<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    @if ($seo['keywords'])
        <meta name="keywords" content="{{ $seo['keywords'] }}">
    @endif
    <meta name="robots" content="{{ $seo['robots'] }}">
    <link rel="canonical" href="{{ $seo['canonical'] }}">

    <meta property="og:site_name" content="{{ $seo['site_name'] }}">
    <meta property="og:locale" content="{{ $seo['locale'] }}">
    <meta property="og:type" content="{{ $seo['og_type'] }}">
    <meta property="og:title" content="{{ $seo['og_title'] }}">
    <meta property="og:description" content="{{ $seo['og_description'] }}">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    @if ($seo['og_image'])
        <meta property="og:image" content="{{ $seo['og_image'] }}">
    @endif

    <meta name="twitter:card" content="{{ $seo['twitter_card'] }}">
    <meta name="twitter:title" content="{{ $seo['og_title'] }}">
    <meta name="twitter:description" content="{{ $seo['og_description'] }}">
    @if ($seo['og_image'])
        <meta name="twitter:image" content="{{ $seo['og_image'] }}">
    @endif

    <meta name="theme-color" content="{{ $payload['settings']['color_charcoal'] ?? '#202A24' }}">

    @if ($payload['settings']['favicon'] ?? null)
        <link rel="icon" href="{{ $payload['settings']['favicon'] }}" type="image/svg+xml">
    @endif

    <script type="application/ld+json">@json($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @include('partials.theme')
</head>
<body class="bg-ivory text-charcoal antialiased">
    {{--
        The Vue application replaces this block when it mounts. Until then — and
        for crawlers or browsers without JavaScript — the page already carries
        the full textual content, rendered from the same database records.
    --}}
    <div id="app">
        @include('public.partials.fallback', ['payload' => $payload])
    </div>

    <script>
        window.__HIVEDFOOD__ = @json($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        window.__HIVEDFOOD_ROUTES__ = {
            content: @json(route('api.content')),
            calculate: @json(route('api.quotations.calculate')),
            quotations: @json(route('api.quotations.store')),
            leads: @json(route('api.leads.store')),
        };
        window.__HIVEDFOOD_TARGET__ = @json(request()->path() === '/' ? null : request()->path());
    </script>
</body>
</html>
