{{-- Satu pilihan paket/bundle. Harga di data-* dipakai JS untuk estimasi dan saat mata uang diganti. --}}
@php($label = fn (string $cur) => $service->name.' ('.($service->price_is_starting ? __('from').' ' : '').$service->priceFor($cur).')')
<option value="{{ $service->id }}" @selected((int) old('service_id', $selected) === $service->id)
        data-idr="{{ $service->amountFor('IDR') }}" data-usd="{{ $service->amountFor('USD') }}"
        data-starting="{{ $service->price_is_starting ? 1 : 0 }}"
        data-label-idr="{{ $label('IDR') }}" data-label-usd="{{ $label('USD') }}">{{ $label($currency) }}</option>
