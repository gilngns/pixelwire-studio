@extends('layouts.app', ['title' => __('Services & prices')])

@section('content')
    <section class="mx-auto max-w-6xl px-4 sm:px-6 pt-14">
        <div class="section-head">
            <div>
                <p class="kicker" aria-hidden="true">サービスと料金</p>
                <h1 class="headline text-5xl sm:text-6xl mt-1">{{ __('Services & prices') }}</h1>
                <p class="text-muted mt-4 max-w-2xl">{{ __('Prices are a starting point. You get an exact quote after sending your brief.') }}</p>
            </div>
            @include('partials.currency-toggle')
        </div>

        <p class="panel bg-sky-soft! px-5 py-4 max-w-3xl font-bold">
            {{ __('Prices are for coding your finished design into a working widget. Design is not included, so send your design assets with the brief.') }}
        </p>

        @if ($packages->isEmpty() && $bundles->isEmpty())
            <p class="text-muted mt-10">{{ __('The price list is being updated. You can still send a brief and get a quote.') }}</p>
        @endif

        {{-- Paket per kategori, urut sesuai config/studio.php. --}}
        @foreach (config('studio.categories') as $key => $label)
            @continue(! $packages->has($key))
            <div class="mt-16" id="{{ $key }}">
                <h2 class="headline text-3xl sm:text-4xl [--shadow:var(--color-sky)]">{{ __($label) }}</h2>
                <div class="mt-6 grid gap-7 md:grid-cols-2">
                    @foreach ($packages[$key] as $service)
                        <article class="sticker bg-sheet p-6 flex flex-col" id="{{ $service->slug }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <h3 class="text-xl font-extrabold leading-tight">{{ $service->name }}</h3>
                                <p class="font-display text-2xl whitespace-nowrap">@include('partials.price')</p>
                            </div>
                            <p class="mt-2 text-muted">{{ $service->summary }}</p>
                            @if (! empty($service->features))
                                <ul class="mt-4 prose-terms">
                                    @foreach ($service->features as $feature)
                                        <li>{{ $feature }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            <div class="mt-auto pt-5 flex flex-wrap items-center justify-between gap-3">
                                <dl class="flex flex-wrap gap-x-5 gap-y-1 text-sm">
                                    @if ($service->turnaround)
                                        <div class="flex gap-1.5"><dt class="label text-muted pt-0.5">{{ __('Turnaround') }}</dt><dd class="font-bold">{{ $service->turnaround }}</dd></div>
                                    @endif
                                    <div class="flex gap-1.5"><dt class="label text-muted pt-0.5">{{ __('Revisions') }}</dt><dd class="font-bold">{{ $service->revisions }}</dd></div>
                                </dl>
                                <a href="{{ route('orders.create', ['service' => $service->slug]) }}" class="btn btn-primary btn-sm"
                                   aria-label="{{ __('Order :service', ['service' => $service->name]) }}">{{ __('Order') }}</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endforeach

        @if ($bundles->isNotEmpty())
            <div class="mt-20" id="bundles">
                <p class="kicker" aria-hidden="true">セット</p>
                <h2 class="headline text-3xl sm:text-4xl mt-1 [--shadow:var(--color-lemon)]">{{ __('Bundles') }}</h2>
                <div class="mt-6 grid gap-7">
                    @foreach ($bundles as $service)
                        <article class="sticker bg-lemon/40 p-5 sm:p-8 grid gap-6 lg:grid-cols-[1.4fr_1fr] lg:items-center" id="{{ $service->slug }}">
                            <div class="min-w-0">
                                <h3 class="font-display text-3xl">{{ $service->name }}</h3>
                                <p class="mt-2 text-lg">{{ $service->summary }}</p>
                                @if (! empty($service->features))
                                    <p class="label text-muted mt-4">{{ __('Includes') }}</p>
                                    <ul class="mt-1 prose-terms">
                                        @foreach ($service->features as $feature)
                                            <li>{{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <div class="panel p-5 sm:p-6 min-w-0">
                                <p class="font-display text-3xl sm:text-4xl">@include('partials.price')</p>
                                @if ($saving = $service->savingsFor($currency))
                                    <p class="mt-2 inline-block rounded-full border-[2.5px] border-ink bg-sakura px-3 py-0.5 text-sm font-extrabold">{{ __('Save :amount', ['amount' => $saving]) }}</p>
                                @endif
                                <dl class="mt-4 flex flex-wrap gap-x-5 gap-y-1 text-sm">
                                    @if ($service->turnaround)
                                        <div class="flex gap-1.5"><dt class="label text-muted pt-0.5">{{ __('Turnaround') }}</dt><dd class="font-bold">{{ $service->turnaround }}</dd></div>
                                    @endif
                                    <div class="flex gap-1.5"><dt class="label text-muted pt-0.5">{{ __('Revisions') }}</dt><dd class="font-bold">{{ $service->revisions }}</dd></div>
                                </dl>
                                <a href="{{ route('orders.create', ['service' => $service->slug]) }}" class="btn btn-primary mt-5">
                                    {{ __('Order :service', ['service' => $service->name]) }}
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($addons->isNotEmpty())
            <div class="mt-20" id="addons">
                <p class="kicker" aria-hidden="true">オプション</p>
                <h2 class="headline text-3xl sm:text-4xl mt-1 [--shadow:var(--color-mint)]">{{ __('Add-ons') }}</h2>
                <p class="text-muted mt-3">{{ __('Add these to any package or bundle in the order form.') }}</p>
                <table class="mt-6 w-full sticker bg-sheet overflow-hidden border-separate border-spacing-0">
                    <thead class="sr-only">
                        <tr><th scope="col">{{ __('Add-on') }}</th><th scope="col">{{ __('Price') }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($addons as $addon)
                            {{-- Di HP tiap baris ditumpuk: nama dulu, harga di bawahnya. --}}
                            <tr class="max-sm:block">
                                <td class="max-sm:block px-5 sm:px-7 pt-4 sm:pb-4 {{ $loop->last ? '' : 'sm:border-b-2 sm:border-dashed border-rule' }}">
                                    <p class="font-extrabold">{{ $addon->name }}</p>
                                    @if ($addon->description)
                                        <p class="text-sm text-muted mt-0.5">{{ $addon->description }}</p>
                                    @endif
                                </td>
                                <td class="max-sm:block px-5 sm:px-7 pt-2 pb-4 sm:pt-4 sm:text-right align-middle {{ $loop->last ? '' : 'border-b-2 border-dashed border-rule' }}">
                                    <span class="inline-block rounded-full border-[2.5px] border-ink px-3 py-0.5 text-sm font-extrabold whitespace-nowrap {{ $addon->pricing_type === 'free' ? 'bg-mint' : 'bg-sakura-soft' }}">{{ $addon->priceLabel($currency) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="mt-16 grid gap-8 md:grid-cols-2">
            <div class="panel p-7 bg-sky-soft!">
                <h2 class="headline text-3xl [--shadow:var(--color-sky)]">{{ __('Something else in mind?') }}</h2>
                <p class="mt-3">{{ __('If your idea does not fit a package, choose "Custom" in the order form and describe it. Most custom widgets can be built.') }}</p>
                <a href="{{ route('orders.create') }}" class="btn btn-ghost mt-6">{{ __('Send a custom brief') }}</a>
            </div>
            @if (filled($paymentInfo))
                <div class="panel p-7">
                    <h2 class="headline text-3xl [--shadow:var(--color-lemon)]">{{ __('Payment') }}</h2>
                    <div class="mt-3 space-y-1">
                        @foreach (preg_split('/\r?\n/', trim($paymentInfo)) as $line)
                            <p>{{ $line }}</p>
                        @endforeach
                    </div>
                    <p class="mt-5"><a href="{{ route('terms') }}" class="font-extrabold underline decoration-sakura">{{ __('Read the terms') }}</a></p>
                </div>
            @endif
        </div>
    </section>

    @include('partials.faq')
@endsection
