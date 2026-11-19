<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Acceso al panel · HIVEDFOOD</title>
    <link rel="icon" href="/images/brand/favicon.svg" type="image/svg+xml">
    @fonts
    @vite(['resources/css/admin.css'])

    @include('partials.theme')
</head>
<body class="min-h-screen bg-charcoal text-ivory">
    <main class="grid min-h-screen place-items-center px-5 py-12">
        <div class="w-full max-w-sm">
            <img src="/images/brand/hivedfood-logo-light.svg" alt="HIVEDFOOD" class="mx-auto h-11 w-auto" width="260" height="52">

            <div class="mt-9 rounded-3xl border border-white/10 bg-white/[0.04] p-7">
                <h1 class="font-sans text-xl font-extrabold">Panel administrativo</h1>
                <p class="mt-1.5 text-sm text-ivory/55">Administra el contenido del sitio comercial.</p>

                @if ($errors->any())
                    <div class="mt-5 rounded-xl border border-coral/40 bg-coral/12 px-4 py-3 text-sm text-coral-soft">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.attempt') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block font-sans text-xs font-bold uppercase tracking-wider text-ivory/55">
                            Correo
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full rounded-xl border border-white/15 bg-charcoal px-4 py-3 text-sm text-ivory outline-none transition focus:border-honey focus:ring-4 focus:ring-honey/20"
                        >
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block font-sans text-xs font-bold uppercase tracking-wider text-ivory/55">
                            Contraseña
                        </label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="w-full rounded-xl border border-white/15 bg-charcoal px-4 py-3 text-sm text-ivory outline-none transition focus:border-honey focus:ring-4 focus:ring-honey/20"
                        >
                    </div>

                    <label class="flex cursor-pointer items-center gap-2 text-xs text-ivory/60">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 accent-[#F4B83F]">
                        Mantener la sesión abierta
                    </label>

                    <button
                        type="submit"
                        class="w-full rounded-full bg-honey px-6 py-3 font-sans text-sm font-bold text-charcoal transition hover:bg-honey-dark"
                    >
                        Entrar
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-ivory/35">
                <a href="/" class="underline-offset-4 hover:underline">Volver al sitio</a>
            </p>
        </div>
    </main>
</body>
</html>
