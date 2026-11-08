{{--
    Layout halaman error. Sengaja tidak membaca database (pengaturan studio, slot),
    supaya halaman 500 dan 503 tetap tampil walaupun database bermasalah.
--}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} | {{ config('studio.name') }}</title>
    <meta name="theme-color" content="#fff6ea">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&family=DotGothic16&family=M+PLUS+Rounded+1c:wght@500;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen flex flex-col">
    <header class="px-3 sm:px-6 pt-4">
        <div class="mx-auto max-w-6xl panel !rounded-full px-4 sm:px-5 py-2.5 flex items-center shadow-[4px_4px_0_var(--color-ink)]">
            <a href="{{ url('/') }}" class="flex items-center gap-2 no-underline">
                @include('partials.logo', ['size' => 28])
                <span class="font-display text-lg leading-none">Pixelwire</span>
            </a>
        </div>
    </header>

    <main id="main" class="relative flex-1 grid place-items-center px-4 sm:px-6 py-16 overflow-hidden">
        <div class="speedlines absolute inset-0 -z-10 [--sx:50%] [--sy:38%]" aria-hidden="true"></div>
        <div class="relative text-center max-w-xl">
            <span class="sparkle w-9 h-9 -top-2 left-2 sm:-left-6" style="--c: var(--color-lemon)" aria-hidden="true"></span>
            <span class="sparkle w-6 h-6 top-24 right-2 sm:-right-4 [animation-delay:-1.2s]" style="--c: var(--color-sky)" aria-hidden="true"></span>

            <p class="kicker justify-center" aria-hidden="true">{{ $kicker }}</p>
            <p class="headline text-[6.5rem] sm:text-[9rem] leading-none mt-2" aria-hidden="true">{{ $code }}</p>
            <h1 class="font-display text-3xl sm:text-4xl mt-6 leading-tight">{{ $title }}</h1>
            <p class="text-muted text-lg mt-4">{{ $message }}</p>

            <div class="mt-9 flex flex-wrap justify-center gap-3">
                @yield('actions')
                <a href="{{ url('/') }}" class="btn btn-ghost">{{ __('Back to home') }}</a>
            </div>
        </div>
    </main>

    <footer class="bg-ink text-paper">
        <p class="mx-auto max-w-6xl px-4 sm:px-6 py-5 label text-paper/50">&copy; {{ date('Y') }} Pixelwire Studio</p>
    </footer>
</body>
</html>
