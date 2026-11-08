@extends('layouts.app', ['title' => __('Privacy')])

@section('content')
    <section class="mx-auto max-w-3xl px-4 sm:px-6 pt-14">
        <div class="section-head">
            <div>
                <p class="kicker" aria-hidden="true">プライバシー</p>
                <h1 class="headline text-5xl sm:text-6xl mt-1 [--shadow:var(--color-sky)]">{{ __('Privacy policy') }}</h1>
                <p class="text-muted mt-4">{{ __('What happens to the details you send with an order.') }}</p>
            </div>
        </div>
        <div class="sticker bg-sheet px-6 sm:px-9 py-4 sm:py-6 prose-terms">
            {!! $privacy !!}
        </div>
        <a href="{{ route('orders.create') }}" class="btn btn-primary mt-10">{{ __('Start an order') }}</a>
    </section>
@endsection
