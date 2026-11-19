@php
    $settings = $payload['settings'];
    $hero = $payload['banners'][0] ?? null;
    $sections = collect($payload['sections']);
    $flow = $sections->whereNotIn('component', ['header', 'hero', 'footer']);
@endphp

<div class="min-h-screen">
    <header class="bg-charcoal text-ivory">
        <div class="hf-shell flex items-center justify-between gap-6 py-5">
            <a href="/" class="font-sans text-lg font-extrabold tracking-tight">{{ $settings['site_name'] ?? 'HIVEDFOOD' }}</a>
            <nav aria-label="Navegación principal" class="hidden gap-6 text-sm md:flex">
                @foreach (($settings['nav'] ?? []) as $link)
                    <a href="{{ $link['url'] }}" class="opacity-80 hover:opacity-100">{{ $link['label'] }}</a>
                @endforeach
            </nav>
            <a href="#contacto" class="hf-btn hf-btn-primary"><span>Solicitar demostración</span></a>
        </div>
    </header>

    @if ($hero)
        <section class="bg-charcoal text-ivory">
            <div class="hf-shell grid gap-10 py-16 md:py-24 lg:grid-cols-2 lg:items-center">
                <div>
                    @if ($hero['eyebrow'])
                        <p class="hf-eyebrow text-honey">{{ $hero['eyebrow'] }}</p>
                    @endif
                    <h1 class="mt-5 text-4xl md:text-6xl">{{ $hero['title'] }}</h1>
                    @if ($hero['description'])
                        <p class="mt-6 max-w-xl text-lg opacity-80">{{ $hero['description'] }}</p>
                    @endif
                    <div class="mt-8 flex flex-wrap gap-3">
                        @if ($hero['cta_text'])
                            <a href="{{ $hero['cta_url'] ?? '#' }}" class="hf-btn hf-btn-primary"><span>{{ $hero['cta_text'] }}</span></a>
                        @endif
                        @if ($hero['cta_secondary_text'])
                            <a href="{{ $hero['cta_secondary_url'] ?? '#' }}" class="hf-btn hf-btn-ghost"><span>{{ $hero['cta_secondary_text'] }}</span></a>
                        @endif
                    </div>
                </div>
                @if ($hero['image_desktop'])
                    <img src="{{ $hero['image_desktop'] }}" alt="{{ $hero['image_alt'] }}" width="960" height="720" class="w-full rounded-3xl">
                @endif
            </div>
        </section>

        {{-- Remaining banners stay in the markup so their copy is indexable. --}}
        @foreach (array_slice($payload['banners'], 1) as $banner)
            <section class="bg-charcoal-700 text-ivory">
                <div class="hf-shell py-10">
                    <h2 class="text-2xl">{{ $banner['title'] }}</h2>
                    @if ($banner['description'])
                        <p class="mt-3 max-w-2xl opacity-80">{{ $banner['description'] }}</p>
                    @endif
                    @if ($banner['cta_text'])
                        <a href="{{ $banner['cta_url'] ?? '#' }}" class="mt-4 inline-block font-semibold text-honey">{{ $banner['cta_text'] }}</a>
                    @endif
                </div>
            </section>
        @endforeach
    @endif

    <main>
        @foreach ($flow as $section)
            <section id="{{ $section['key'] }}" class="border-b border-charcoal/5 py-14">
                <div class="hf-shell">
                    @if ($section['eyebrow'])
                        <p class="hf-eyebrow text-coral">{{ $section['eyebrow'] }}</p>
                    @endif
                    @if ($section['title'])
                        <h2 class="mt-4 text-3xl md:text-4xl">{{ $section['title'] }}</h2>
                    @endif
                    @if ($section['description'])
                        <p class="mt-4 max-w-3xl text-lg opacity-75">{{ $section['description'] }}</p>
                    @endif

                    @if (count($section['modules']))
                        <ul class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            @foreach ($section['modules'] as $module)
                                <li class="hf-card p-6">
                                    @if ($module['value'])
                                        <p class="font-sans text-2xl font-extrabold text-honey">{{ $module['value'] }}</p>
                                    @endif
                                    <h3 class="text-lg">{{ $module['title'] }}</h3>
                                    @if ($module['description'])
                                        <p class="mt-2 text-sm opacity-75">{{ $module['description'] }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($section['component'] === 'features')
                        <ul class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            @foreach ($payload['features'] as $feature)
                                <li class="hf-card p-6">
                                    <h3 class="text-lg">{{ $feature['name'] }}</h3>
                                    <p class="mt-2 text-sm opacity-75">{{ $feature['short_description'] }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($section['component'] === 'demos')
                        <ul class="mt-8 grid gap-6 md:grid-cols-3">
                            @foreach ($payload['demonstrations'] as $demo)
                                <li class="hf-card p-6">
                                    <h3 class="text-lg">{{ $demo['business_name'] }}</h3>
                                    <p class="text-xs uppercase tracking-widest opacity-60">{{ $demo['category'] }}</p>
                                    <p class="mt-2 text-sm opacity-75">{{ $demo['description'] }}</p>
                                    @if ($demo['demo_url'])
                                        <a href="{{ $demo['demo_url'] }}" rel="noopener" class="mt-3 inline-block font-semibold text-coral">Ver demostración</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($section['component'] === 'business-types')
                        <ul class="mt-8 flex flex-wrap gap-3">
                            @foreach ($payload['quoter']['business_types'] as $type)
                                <li class="rounded-full border border-charcoal/15 px-4 py-2 text-sm">{{ $type['name'] }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($section['component'] === 'pricing')
                        <ul class="mt-8 grid gap-6 md:grid-cols-2">
                            @foreach ($payload['quoter']['packages'] as $package)
                                <li class="hf-card p-6">
                                    <h3 class="text-lg">{{ $package['name'] }}</h3>
                                    <p class="mt-2 text-sm opacity-75">{{ $package['headline'] }}</p>
                                    @if (! empty($package['included']))
                                        <ul class="mt-3 space-y-1 text-sm opacity-70">
                                            @foreach ($package['included'] as $line)
                                                <li>{{ $line }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        @if (! empty($payload['quoter']['modules']))
                            <h3 class="mt-10 font-sans text-base font-bold">Módulos que puedes añadir</h3>
                            <ul class="mt-4 grid gap-3 md:grid-cols-2">
                                @foreach ($payload['quoter']['modules'] as $module)
                                    @if ($module['is_available'])
                                        <li class="text-sm opacity-75">
                                            <strong class="font-sans">{{ $module['name'] }}</strong>
                                            @if ($module['short_description'])
                                                — {{ $module['short_description'] }}
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif

                        @if (! empty($payload['quoter']['external_costs']))
                            <h3 class="mt-10 font-sans text-base font-bold">Servicios externos no incluidos</h3>
                            <ul class="mt-4 space-y-2 text-sm opacity-70">
                                @foreach ($payload['quoter']['external_costs'] as $cost)
                                    <li><strong class="font-sans">{{ $cost['title'] }}</strong> — {{ $cost['description'] }}</li>
                                @endforeach
                            </ul>
                        @endif
                    @endif

                    @if ($section['component'] === 'quoter')
                        <ol class="mt-8 grid gap-4 md:grid-cols-2">
                            @foreach ($payload['quoter']['packages'] as $package)
                                <li class="hf-card p-6">
                                    <h3 class="text-lg">{{ $package['name'] }}</h3>
                                    <p class="mt-2 text-sm opacity-75">{{ $package['description'] }}</p>
                                </li>
                            @endforeach
                        </ol>
                        <p class="mt-6 text-sm opacity-70">
                            El cotizador interactivo requiere JavaScript. Escríbenos y preparamos tu propuesta.
                        </p>
                    @endif

                    @if ($section['component'] === 'testimonials')
                        <ul class="mt-8 grid gap-6 md:grid-cols-3">
                            @foreach ($payload['testimonials'] as $testimonial)
                                <li class="hf-card p-6">
                                    <blockquote class="text-sm italic opacity-80">{{ $testimonial['quote'] }}</blockquote>
                                    <p class="mt-3 font-sans text-sm font-bold">{{ $testimonial['author'] }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($section['component'] === 'faq')
                        <dl class="mt-8 space-y-5">
                            @foreach ($payload['faqs'] as $faq)
                                <div class="hf-card p-6">
                                    <dt class="font-sans text-base font-bold">{{ $faq['question'] }}</dt>
                                    <dd class="mt-2 text-sm opacity-75">{{ $faq['answer'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif

                    @if ($section['component'] === 'cta')
                        <p class="mt-6">
                            @if ($settings['email'])
                                <a href="mailto:{{ $settings['email'] }}" class="font-semibold">{{ $settings['email'] }}</a>
                            @endif
                            @if ($settings['whatsapp_url'])
                                · <a href="{{ $settings['whatsapp_url'] }}" rel="noopener" class="font-semibold">WhatsApp</a>
                            @endif
                        </p>
                    @endif
                </div>
            </section>
        @endforeach
    </main>

    <footer class="bg-charcoal py-12 text-ivory">
        <div class="hf-shell">
            <p class="font-sans text-lg font-extrabold">{{ $settings['site_name'] ?? 'HIVEDFOOD' }}</p>
            <p class="mt-3 max-w-2xl text-sm opacity-70">{{ $settings['footer_text'] }}</p>
            <p class="mt-6 text-xs opacity-50">&copy; {{ date('Y') }} {{ $settings['legal_name'] ?: ($settings['site_name'] ?? 'HIVEDFOOD') }}</p>
        </div>
    </footer>
</div>
