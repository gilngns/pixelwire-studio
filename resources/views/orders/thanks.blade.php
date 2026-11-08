@extends('layouts.app', ['title' => __('Order sent')])

@section('content')
    <section class="mx-auto max-w-2xl px-4 sm:px-6 pt-16 text-center">
        <p class="kicker" aria-hidden="true">ありがとう！</p>
        <h1 class="headline text-5xl sm:text-6xl mt-1">{{ __('Order sent') }}</h1>
        <p class="mt-5 text-lg">{{ __('I will review your brief and reply with a quote within :time. A confirmation has been sent to your email.', ['time' => \App\Support\Studio::replyTime()]) }}</p>

        <div class="relative mt-12 text-left">
            <span class="sparkle w-9 h-9 -top-5 -right-3 z-10" aria-hidden="true"></span>
            <span class="sparkle w-6 h-6 -bottom-3 -left-2 z-10 [animation-delay:-1s]" style="--c: var(--color-sakura)" aria-hidden="true"></span>
            <div class="source">
                <div class="source-bar label">
                    <span>{{ __('Your order code') }}</span>
                </div>
                <div class="stage px-6 py-12 sm:py-14 grid place-items-center">
                    <p class="panel bg-lemon! -rotate-2 px-6 py-3 font-display text-3xl sm:text-4xl tracking-wide shadow-[5px_5px_0_var(--color-ink)] select-all">{{ $code }}</p>
                </div>
            </div>
        </div>
        <p class="mt-4 text-sm text-muted">{{ __('Save this code to check your order status later.') }}</p>

        <div class="mt-10 flex flex-wrap justify-center gap-4">
            <a href="{{ route('orders.track') }}" class="btn btn-primary">{{ __('Check order status') }}</a>
            <a href="{{ route('work') }}" class="btn btn-ghost">{{ __('See the work') }}</a>
        </div>
    </section>
@endsection
