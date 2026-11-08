@extends('layouts.app')

@section('content')
    {{-- Hero: speed lines manga di belakang, teks di kiri, demo chat box di jendela "source" di kanan. --}}
    <section class="relative overflow-hidden">
        <div class="speedlines absolute inset-0 -z-10" aria-hidden="true"></div>

        <div class="mx-auto max-w-6xl px-4 sm:px-6 pt-12 sm:pt-16 grid gap-14 lg:grid-cols-[1fr_1fr] lg:items-center">
            <div>
                @include('partials.slot-meter')
                <p class="kicker mt-6" aria-hidden="true">ストリーム・ウィジェット</p>
                <h1 class="headline mt-2 text-[2.5rem] sm:text-[3.4rem] lg:text-[3.6rem]">
                    {{ __('Chat boxes, alerts and widgets, coded for your stream.') }}
                </h1>
                <p class="mt-6 text-lg max-w-md">{{ $settings['tagline'] }}</p>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="{{ route('orders.create') }}" class="btn btn-primary">{{ __('Start an order') }}</a>
                    <a href="{{ route('work') }}" class="btn btn-ghost">{{ __('See the work') }}</a>
                </div>
                <p class="mt-8 label text-muted max-w-sm leading-relaxed">
                    {{ __('Works with StreamElements, Streamlabs and any OBS browser source.') }}
                </p>
            </div>

            <figure data-demo class="relative">
                <span class="sparkle w-9 h-9 -top-5 -left-4 z-10" style="--c: var(--color-lemon)" aria-hidden="true"></span>
                <span class="sparkle w-6 h-6 top-16 -right-3 z-10 [animation-delay:-1.2s]" style="--c: var(--color-sky)" aria-hidden="true"></span>
                <span class="sparkle w-5 h-5 bottom-32 -left-3 z-10 [animation-delay:-0.6s]" style="--c: var(--color-sakura)" aria-hidden="true"></span>

                <div class="relative">
                    <div class="source">
                        <div class="source-bar label">
                            <span class="rec">chatbox.html</span>
                            <span>1920&times;1080</span>
                        </div>
                        <div class="stage relative h-[22rem] sm:h-[25rem] p-5 sm:p-6 overflow-hidden" data-demo-stage>
                            <div class="demo-chat" data-demo-chat aria-hidden="true"></div>
                        </div>
                    </div>
                    <span class="absolute -bottom-4 right-6 rotate-6 panel !rounded-xl px-3 py-1 font-display text-sm bg-lemon! shadow-[3px_3px_0_var(--color-ink)]" aria-hidden="true">OBS ready!</span>
                </div>
            </figure>
        </div>
    </section>

    {{-- Layanan: daftar baris supaya harga mudah dibandingkan. --}}
    @if ($services->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 sm:px-6 mt-28" aria-labelledby="services-heading">
            <div class="section-head">
                <div>
                    <p class="kicker" aria-hidden="true">サービス</p>
                    <h2 id="services-heading" class="headline text-4xl sm:text-5xl mt-1">{{ __('What I build') }}</h2>
                    <p class="text-muted mt-3">{{ __('Prices are a starting point. You get an exact quote after sending your brief.') }}</p>
                </div>
                @include('partials.currency-toggle')
            </div>

            <ul class="sticker bg-sheet overflow-hidden">
                @foreach ($services as $service)
                    <li class="px-5 sm:px-7 py-5 grid gap-x-6 gap-y-3 sm:grid-cols-[1fr_auto_auto] sm:items-center {{ $loop->last ? '' : 'border-b-2 border-dashed border-rule' }}">
                        <div>
                            <h3 class="text-xl font-extrabold leading-tight">
                                {{ $service->name }}
                                @if ($service->isBundle())<span class="label align-middle ml-1 rounded-full border-2 border-ink bg-lemon px-2 py-0.5">{{ __('Bundle') }}</span>@endif
                            </h3>
                            <p class="text-muted mt-1 max-w-2xl">{{ $service->summary }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 sm:contents">
                            <p class="sm:justify-self-end rounded-full border-[2.5px] border-ink {{ $service->isBundle() ? 'bg-lemon' : 'bg-sakura-soft' }} px-3.5 py-1 font-extrabold whitespace-nowrap">
                                @include('partials.price')
                            </p>
                            <a href="{{ route('orders.create', ['service' => $service->slug]) }}"
                               class="btn btn-ghost btn-sm sm:justify-self-end"
                               aria-label="{{ __('Order :service', ['service' => $service->name]) }}">{{ __('Order') }}</a>
                        </div>
                    </li>
                @endforeach
            </ul>
            <p class="mt-6"><a href="{{ route('services') }}" class="font-extrabold underline decoration-sakura">{{ __('Full details, revisions and turnaround') }}</a></p>
        </section>
    @endif

    @if ($featuredWork->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 sm:px-6 mt-28" aria-labelledby="work-heading">
            <div class="section-head">
                <div>
                    <p class="kicker" aria-hidden="true">ワークス</p>
                    <h2 id="work-heading" class="headline text-4xl sm:text-5xl mt-1">{{ __('Recent work') }}</h2>
                </div>
                <a href="{{ route('work') }}" class="btn btn-ghost btn-sm">{{ __('All work') }}</a>
            </div>
            <div class="grid gap-x-7 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featuredWork as $item)
                    @include('partials.work-tile', ['item' => $item])
                @endforeach
            </div>
        </section>
    @endif

    {{-- Alur order memang berurutan, jadi diberi nomor. Tampil seperti panel komik. --}}
    <section class="mx-auto max-w-6xl px-4 sm:px-6 mt-28" aria-labelledby="process-heading">
        <div class="section-head">
            <div>
                <p class="kicker" aria-hidden="true">オーダーの流れ</p>
                <h2 id="process-heading" class="headline text-4xl sm:text-5xl mt-1">{{ __('How ordering works') }}</h2>
            </div>
        </div>
        <ol class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                [__('Send the brief'), __('Tell me what the widget should do, which platform you use, and share your finished design files.'), 'bg-sakura'],
                [__('Get a quote'), __('I reply within :time with the price, timeline and any questions about the details.', ['time' => \App\Support\Studio::replyTime()]), 'bg-sky'],
                [__('Pay the deposit'), __('A :percent% deposit reserves your slot. Bank transfer or e-wallet in IDR, PayPal for USD.', ['percent' => \App\Support\Studio::depositPercent()]), 'bg-lemon'],
                [__('Install and go live'), __('You receive the code and a setup guide. I can help you install it over a call.'), 'bg-mint'],
            ] as $i => [$heading, $body, $color])
                <li class="sticker bg-sheet p-6 {{ $i % 2 ? 'lg:rotate-1' : 'lg:-rotate-1' }}">
                    <span class="grid place-items-center w-12 h-12 rounded-full border-[3px] border-ink font-display text-xl {{ $color }}" aria-hidden="true">{{ $i + 1 }}</span>
                    <h3 class="text-lg font-extrabold mt-4 leading-tight">{{ $heading }}</h3>
                    <p class="text-muted mt-2 text-[0.95rem]">{{ $body }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    @include('partials.faq')

    {{-- Tentang studio: dibuat seperti speech bubble. --}}
    <section class="mx-auto max-w-6xl px-4 sm:px-6 mt-28">
        <div class="grid gap-10 lg:grid-cols-[0.8fr_1.4fr] lg:items-center">
            <div>
                <p class="kicker" aria-hidden="true">スタジオについて</p>
                <h2 class="headline text-4xl sm:text-5xl mt-1 [--shadow:var(--color-sky)]">{{ __('About the studio') }}</h2>
                <a href="{{ route('orders.create') }}" class="btn btn-primary mt-8">{{ __('Start an order') }}</a>
            </div>
            <div class="bubble p-7 sm:p-9 mb-6 space-y-4 text-lg leading-relaxed shadow-[6px_6px_0_var(--color-ink)]">
                @foreach (preg_split('/\n\s*\n/', trim((string) $settings['about'])) as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>
        </div>
    </section>
@endsection
