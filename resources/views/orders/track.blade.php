@extends('layouts.app', ['title' => __('Track order')])

@section('content')
    <section class="mx-auto max-w-2xl px-4 sm:px-6 pt-14">
        <div class="section-head">
            <div>
                <p class="kicker" aria-hidden="true">オーダー確認</p>
                <h1 class="headline text-5xl sm:text-6xl mt-1">{{ __('Track order') }}</h1>
            </div>
        </div>

        @isset($order)
            @php
                $steps = ['new', 'quoted', 'waiting_payment', 'in_progress', 'revision', 'completed'];
                $current = array_search($order->status, $steps, true);
            @endphp
            <div class="sticker bg-sheet overflow-hidden">
                <div class="p-6 sm:p-8 bg-sakura-soft border-b-[3px] border-ink">
                    <p class="label">{{ __('Order :code', ['code' => $order->code]) }}</p>
                    <p class="font-display text-4xl mt-2">{{ $order->statusLabel() }}</p>
                </div>
                <dl class="p-6 sm:p-8 grid grid-cols-[auto_1fr] gap-x-8 gap-y-2.5">
                    <dt class="label text-muted pt-1">{{ __('Package') }}</dt>
                    <dd class="font-bold">{{ $order->service?->name ?? __('Custom') }}</dd>
                    <dt class="label text-muted pt-1">{{ __('Sent on') }}</dt>
                    <dd class="font-bold">{{ $order->created_at->translatedFormat('d F Y') }}</dd>
                    @if ($order->quotedPriceLabel())
                        <dt class="label text-muted pt-1">{{ __('Quoted price') }}</dt>
                        <dd class="font-extrabold">{{ $order->quotedPriceLabel() }}</dd>
                    @endif
                </dl>

                {{-- Detail rekening hanya untuk order yang sudah dikonfirmasi, dan halaman ini hanya bisa dibuka dengan kode + email. --}}
                @php($paymentDetails = trim((string) \App\Models\Setting::get('payment_details', '')))
                @if ($order->showsPaymentDetails() && $paymentDetails !== '')
                    <div class="mx-6 sm:mx-8 mb-8 panel p-5 sm:p-6 bg-sky-soft!">
                        <h2 class="font-display text-xl">{{ __('Payment details') }}</h2>
                        <div class="mt-3 space-y-1 font-bold break-words">
                            @foreach (preg_split('/\r?\n/', $paymentDetails) as $line)
                                <p>{{ $line }}</p>
                            @endforeach
                        </div>
                        <p class="mt-4 text-sm text-muted">{{ __('Write your order code (:code) in the transfer note.', ['code' => $order->code]) }}</p>
                    </div>
                @endif

                @if ($order->status === 'cancelled')
                    <p class="mx-6 sm:mx-8 mb-8 notice">{{ __('This order was cancelled. Reach out if you think this is a mistake.') }}</p>
                @else
                    {{-- Progres seperti quest log di game. --}}
                    <ol class="px-6 sm:px-8 pb-8 space-y-2.5" aria-label="{{ __('Order progress') }}">
                        @foreach ($steps as $i => $step)
                            @continue($step === 'revision' && $order->status !== 'revision')
                            <li class="flex items-center gap-3 rounded-xl border-[2.5px] px-4 py-2.5 {{ $i === $current ? 'border-ink bg-lemon font-extrabold shadow-[3px_3px_0_var(--color-ink)]' : ($i < $current ? 'border-ink bg-sheet' : 'border-dashed border-rule text-muted') }}">
                                <span class="grid place-items-center w-6 h-6 flex-none rounded-full border-2 {{ $i < $current ? 'border-ink bg-mint' : ($i === $current ? 'border-ink bg-sakura' : 'border-rule') }} text-xs font-extrabold" aria-hidden="true">{{ $i < $current ? '✓' : '' }}</span>
                                <span @if ($i === $current) aria-current="step" @endif>{{ __(config('studio.order_statuses.'.$step)) }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
            <a href="{{ route('orders.track') }}" class="btn btn-ghost btn-sm mt-8">{{ __('Check another order') }}</a>
        @else
            <p class="-mt-4">{{ __('Enter the code from your confirmation email and the email you used to order.') }}</p>

            @if (session('error'))
                <div class="notice mt-6" role="alert">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('orders.track.lookup') }}" data-submit-once class="sticker bg-sheet p-6 sm:p-8 mt-8 space-y-6">
                @csrf
                <div>
                    <label for="code" class="field-label">{{ __('Order code') }}</label>
                    <input id="code" name="code" type="text" class="field font-pixel uppercase text-lg tracking-wider" value="{{ old('code') }}" placeholder="PW-2610-AB12" required autocomplete="off">
                    @error('code') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="email" class="field-label">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" class="field" value="{{ old('email') }}" required autocomplete="email">
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Check status') }}</button>
            </form>
        @endisset
    </section>
@endsection
