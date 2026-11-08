@extends('layouts.app', ['title' => __('Work')])

@section('content')
    <section class="mx-auto max-w-6xl px-4 sm:px-6 pt-14">
        <div class="section-head">
            <div>
                <p class="kicker" aria-hidden="true">ワークス</p>
                <h1 class="headline text-5xl sm:text-6xl mt-1">{{ __('Work') }}</h1>
                <p class="text-muted mt-4 max-w-2xl">{{ __('Demos of widgets built for streamers and VTubers.') }}</p>
            </div>
        </div>

        <nav aria-label="{{ __('Filter by category') }}">
            <ul class="flex flex-wrap gap-3">
                <li>
                    <a href="{{ route('work') }}" class="btn btn-sm {{ $active ? 'btn-ghost' : 'btn-active' }}"
                       @unless ($active) aria-current="page" @endunless>{{ __('All') }}</a>
                </li>
                @foreach ($categories as $key => $label)
                    <li>
                        <a href="{{ route('work', ['category' => $key]) }}" class="btn btn-sm {{ $active === $key ? 'btn-active' : 'btn-ghost' }}"
                           @if ($active === $key) aria-current="page" @endif>{{ __($label) }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @if ($items->isEmpty())
            <div class="mt-12 grid gap-10 md:grid-cols-[1fr_1fr] md:items-center">
                <div class="source">
                    <div class="source-bar label"><span>demo.webm</span><span>0:00</span></div>
                    <div class="stage h-56 grid place-items-center">
                        <span class="panel !rounded-full px-4 py-1.5 font-display text-sm shadow-[3px_3px_0_var(--color-ink)]">{{ __('No demos here yet') }}</span>
                    </div>
                </div>
                <div>
                    <p class="text-lg">{{ __('New demos are added after each project. If you want to see a similar widget, mention it in your brief and I will send examples.') }}</p>
                    <a href="{{ route('orders.create') }}" class="btn btn-primary mt-6">{{ __('Start an order') }}</a>
                </div>
            </div>
        @else
            <div class="mt-10 grid gap-x-7 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($items as $item)
                    @include('partials.work-tile', ['item' => $item])
                @endforeach
            </div>
        @endif
    </section>
@endsection
