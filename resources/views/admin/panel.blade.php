<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panel · HIVEDFOOD</title>
    <link rel="icon" href="/images/brand/favicon.svg" type="image/svg+xml">
    @fonts
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])

    @include('partials.theme')
</head>
<body class="min-h-screen bg-ivory text-charcoal">
    <div id="admin-app"></div>

    <form id="logout-form" method="POST" action="{{ route('admin.logout') }}" class="hidden">
        @csrf
    </form>

    <script>
        window.__ADMIN__ = {
            user: @json(['name' => auth()->user()->name, 'email' => auth()->user()->email]),
            base: @json(url('/admin/api')),
            siteUrl: @json(url('/')),
        };
    </script>
</body>
</html>
