<div class="toggle-group" role="group" aria-label="{{ __('Currency') }}">
    <a href="{{ request()->fullUrlWithQuery(['cur' => 'idr']) }}" aria-current="{{ $currency === 'IDR' ? 'true' : 'false' }}">IDR</a>
    <a href="{{ request()->fullUrlWithQuery(['cur' => 'usd']) }}" aria-current="{{ $currency === 'USD' ? 'true' : 'false' }}">USD</a>
</div>
