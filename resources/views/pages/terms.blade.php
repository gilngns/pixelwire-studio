@extends('layouts.app', ['title' => __('Terms')])

@section('content')
    <section class="mx-auto max-w-3xl px-4 sm:px-6 pt-14">
        <div class="section-head">
            <div>
                <p class="kicker" aria-hidden="true">利用規約</p>
                <h1 class="headline text-5xl sm:text-6xl mt-1">{{ __('Terms of service') }}</h1>
                <p class="text-muted mt-4">{{ __('Sending an order means you agree to these terms.') }}</p>
            </div>
        </div>
        <div class="sticker bg-sheet px-6 sm:px-9 py-4 sm:py-6 prose-terms">
            {!! $terms !!}
        </div>
        <a href="{{ route('orders.create') }}" class="btn btn-primary mt-10">{{ __('Start an order') }}</a>
    </section>
@endsection
