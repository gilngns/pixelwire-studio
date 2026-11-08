<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' | ' : '' }}{{ config('studio.name') }}</title>
    <meta name="description" content="{{ $settings['tagline'] }}">
    {{-- Preview saat link dibagikan di X, Discord, WhatsApp, dll. URL gambar memakai APP_URL, jadi isi APP_URL dengan domain asli di production. --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('studio.name') }}">
    <meta property="og:title" content="{{ isset($title) ? $title.' | ' : '' }}{{ config('studio.name') }}">
    <meta property="og:description" content="{{ $settings['tagline'] }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ config('studio.name') }}: {{ __('Chat boxes, alerts and widgets, coded for your stream.') }}">
    <meta property="og:locale" content="{{ ($locale ?? 'en') === 'id' ? 'id_ID' : 'en_US' }}">
    <meta name="twitter:card" content="summary_large_image">
    @if (filled($settings['contact_x'] ?? null))
        <meta name="twitter:site" content="{{ '@'.ltrim($settings['contact_x'], '@') }}">
    @endif
    <meta name="theme-color" content="#fff6ea">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&family=DotGothic16&family=M+PLUS+Rounded+1c:wght@500;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 btn btn-primary z-50">{{ __('Skip to content') }}</a>

    @php
        $navLinks = [
            'work' => __('Work'),
            'services' => __('Services & prices'),
            'terms' => __('Terms'),
            'orders.track' => __('Track order'),
        ];
    @endphp

    <header class="px-3 sm:px-6 pt-4">
        <div class="relative mx-auto max-w-6xl panel !rounded-full pl-4 pr-2.5 sm:px-5 py-2.5 flex items-center gap-x-7 shadow-[4px_4px_0_var(--color-ink)]">
            <a href="{{ route('home') }}" class="flex items-center gap-2 no-underline">
                @include('partials.logo', ['size' => 28])
                <span class="font-display text-lg leading-none">Pixelwire</span>
            </a>

            <nav aria-label="{{ __('Main') }}" class="hidden lg:block">
                <ul class="flex gap-x-5 text-[0.95rem] font-bold whitespace-nowrap">
                    @foreach ($navLinks as $route => $label)
                        <li>
                            <a href="{{ route($route) }}"
                               class="{{ request()->routeIs($route) ? 'underline decoration-sakura decoration-[3px] underline-offset-4' : 'no-underline hover:text-sakura' }}"
                               @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="ml-auto flex items-center gap-2.5 sm:gap-3">
                <div class="toggle-group hidden lg:inline-flex" role="group" aria-label="{{ __('Language') }}">
                    <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" aria-current="{{ $locale === 'en' ? 'true' : 'false' }}" hreflang="en">EN</a>
                    <a href="{{ request()->fullUrlWithQuery(['lang' => 'id']) }}" aria-current="{{ $locale === 'id' ? 'true' : 'false' }}" hreflang="id">ID</a>
                </div>
                <a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm whitespace-nowrap"><span class="sm:hidden">{{ __('Order') }}</span><span class="hidden sm:inline">{{ __('Order a widget') }}</span></a>

                {{-- Menu HP dan tablet: navigasi dan pilihan bahasa dipindah ke sini supaya header tetap satu baris. --}}
                <details class="mobile-menu lg:hidden" data-mobile-menu>
                    <summary class="btn btn-ghost btn-sm !px-2.5" aria-label="{{ __('Menu') }}">
                        <svg class="menu-open" width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path d="M2 4h14M2 9h14M2 14h14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
                        <svg class="menu-close" width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"><path d="M4 4l10 10M14 4L4 14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
                    </summary>
                    <div class="absolute left-0 right-0 top-full mt-2 z-40 panel p-3 shadow-[4px_4px_0_var(--color-ink)]">
                        <nav aria-label="{{ __('Main') }}">
                            <ul class="grid gap-1 font-bold">
                                @foreach ($navLinks as $route => $label)
                                    <li>
                                        <a href="{{ route($route) }}"
                                           class="block rounded-xl px-3 py-2.5 no-underline {{ request()->routeIs($route) ? 'bg-sakura-soft' : 'hover:bg-sky-soft' }}"
                                           @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </nav>
                        <div class="mt-3 pt-3 px-3 border-t-2 border-dashed border-ink/20 flex items-center justify-between gap-3">
                            <span class="label text-muted">{{ __('Language') }}</span>
                            <div class="toggle-group" role="group" aria-label="{{ __('Language') }}">
                                <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" aria-current="{{ $locale === 'en' ? 'true' : 'false' }}" hreflang="en">EN</a>
                                <a href="{{ request()->fullUrlWithQuery(['lang' => 'id']) }}" aria-current="{{ $locale === 'id' ? 'true' : 'false' }}" hreflang="id">ID</a>
                            </div>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </header>

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    <footer class="mt-28 bg-ink text-paper border-t-[3px] border-ink">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 pt-12 pb-8">
            <div class="flex flex-wrap items-end justify-between gap-10">
                <div>
                    <p class="font-display text-4xl leading-none [text-shadow:3px_3px_0_var(--color-sakura)]">Pixelwire</p>
                    <p class="label text-sakura mt-2" aria-hidden="true">ピクセルワイヤー・スタジオ</p>
                    <p class="mt-6 tally tally-{{ $studioStatus['state'] }} text-ink">{{ $studioStatus['label'] }}</p>
                </div>
                <div>
                    <h2 class="label text-sky mb-3">{{ __('Contact') }}</h2>
                    @include('partials.social-links')
                </div>
            </div>
            <div class="mt-12 pt-4 border-t border-paper/20 flex flex-wrap items-center justify-between gap-x-6 gap-y-2 label text-paper/50">
                <p>&copy; {{ date('Y') }} Pixelwire Studio</p>
                <p class="flex gap-5">
                    <a href="{{ route('terms') }}" class="no-underline hover:text-paper">{{ __('Terms') }}</a>
                    <a href="{{ route('privacy') }}" class="no-underline hover:text-paper">{{ __('Privacy') }}</a>
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
