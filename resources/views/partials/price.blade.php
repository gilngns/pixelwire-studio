{{-- Harga paket atau bundle: harga normal dicoret untuk bundle, awalan "from"/"mulai" untuk harga mulai. --}}
@php($compare = $service->comparePriceFor($currency))
@if ($compare)
    <s class="font-sans text-[0.7em] font-bold text-muted decoration-2 decoration-sakura mr-1"><span class="sr-only">{{ __('Normal price') }}:</span>{{ $compare }}</s>
@endif
@if ($service->price_is_starting)
    <span class="font-sans text-[0.6em] font-bold text-muted">{{ __('from') }}</span>
@endif
{{ $service->priceFor($currency) }}
