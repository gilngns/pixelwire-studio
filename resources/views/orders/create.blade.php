@extends('layouts.app', ['title' => __('Order a widget')])

@php
    $open = $status['state'] === 'open';
    $err = fn (string $field) => $errors->has($field) ? 'true' : 'false';
    $legend = fn (string $n, string $color) => '<span class="grid place-items-center w-10 h-10 flex-none rounded-full border-[3px] border-ink font-display '.$color.'" aria-hidden="true">'.$n.'</span>';
@endphp

@section('content')
    <section class="mx-auto max-w-6xl px-4 sm:px-6 pt-14 grid gap-10 lg:grid-cols-[1fr_1.7fr]">
        {{-- Kolom kiri: judul dan status, ikut menempel saat form di-scroll. --}}
        <div class="lg:sticky lg:top-8 lg:self-start">
            <p class="kicker" aria-hidden="true">ご注文</p>
            <h1 class="headline text-5xl sm:text-6xl mt-1">{{ __('Order a widget') }}</h1>
            <p class="mt-5 max-w-sm">{{ __('Fill in what you know. Anything unclear can be sorted out after I reply with a quote, so there is no payment yet.') }}</p>
            <div class="mt-6">
                @include('partials.slot-meter', ['studioStatus' => $status])
            </div>
        </div>

        <div>
            @unless ($open)
                <div class="notice mb-8" role="status">
                    <h2 class="font-display text-2xl">{{ __('New orders are paused') }}</h2>
                    <p class="mt-2">{{ __('All commission slots are in use. Follow the studio on social media to hear when slots open again, or check this page later.') }}</p>
                </div>
            @endunless

            @if (session('error'))
                <div class="notice mb-8" role="alert">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="notice mb-8" role="alert">
                    <p class="font-extrabold">{{ __('Some fields need fixing before the order can be sent.') }}</p>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('orders.store') }}" class="space-y-8" novalidate data-submit-once>
                @csrf

                {{-- Honeypot anti-spam: tidak terlihat oleh manusia. --}}
                <div class="hidden" aria-hidden="true">
                    <label for="website">Website</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="sticker bg-sheet p-6 sm:p-8">
                    <fieldset @disabled(! $open) class="space-y-6">
                        <legend class="flex items-center gap-3 mb-6">
                            {!! $legend('1', 'bg-sakura') !!}
                            <span class="font-display text-2xl">{{ __('About you') }}</span>
                        </legend>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="name" class="field-label">{{ __('Name or channel name') }}</label>
                                <input id="name" name="name" type="text" class="field" value="{{ old('name') }}" required maxlength="80" autocomplete="nickname" aria-invalid="{{ $err('name') }}">
                                @error('name') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="email" class="field-label">{{ __('Email') }}</label>
                                <input id="email" name="email" type="email" class="field" value="{{ old('email') }}" required autocomplete="email" aria-invalid="{{ $err('email') }}">
                                <span class="field-hint">{{ __('Your order code and updates are sent here.') }}</span>
                                @error('email') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="contact_platform" class="field-label">{{ __('Where should I reply?') }}</label>
                                <select id="contact_platform" name="contact_platform" class="field" aria-invalid="{{ $err('contact_platform') }}">
                                    @foreach (config('studio.contact_platforms') as $key => $label)
                                        <option value="{{ $key }}" @selected(old('contact_platform', 'discord') === $key)>{{ __($label) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div data-handle-wrap>
                                <label for="contact_handle" class="field-label">{{ __('Username or number') }}</label>
                                <input id="contact_handle" name="contact_handle" type="text" class="field" value="{{ old('contact_handle') }}" maxlength="120" aria-invalid="{{ $err('contact_handle') }}">
                                @error('contact_handle') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="channel_url" class="field-label">{{ __('Channel link (optional)') }}</label>
                                <input id="channel_url" name="channel_url" type="url" class="field" value="{{ old('channel_url') }}" placeholder="https://twitch.tv/..." aria-invalid="{{ $err('channel_url') }}">
                                @error('channel_url') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="sticker bg-sheet p-6 sm:p-8">
                    <fieldset @disabled(! $open) class="space-y-6">
                        <legend class="flex items-center gap-3 mb-6">
                            {!! $legend('2', 'bg-sky') !!}
                            <span class="font-display text-2xl">{{ __('The widget') }}</span>
                        </legend>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="service_id" class="field-label">{{ __('Package') }}</label>
                                <select id="service_id" name="service_id" class="field" aria-invalid="{{ $err('service_id') }}" data-package-select>
                                    <option value="">{{ __('Custom / not sure yet') }}</option>
                                    @foreach (config('studio.categories') as $key => $label)
                                        @continue(! $packages->has($key))
                                        <optgroup label="{{ __($label) }}">
                                            @foreach ($packages[$key] as $service)
                                                @include('orders.partials.package-option')
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                    @if ($bundles->isNotEmpty())
                                        <optgroup label="{{ __('Bundles') }}">
                                            @foreach ($bundles as $service)
                                                @include('orders.partials.package-option')
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                @error('service_id') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="platform" class="field-label">{{ __('Streaming tool') }}</label>
                                <select id="platform" name="platform" class="field" aria-invalid="{{ $err('platform') }}">
                                    @foreach (config('studio.platforms') as $key => $label)
                                        <option value="{{ $key }}" @selected(old('platform', 'streamelements') === $key)>{{ __($label) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @if ($addons->isNotEmpty())
                            <fieldset>
                                <legend class="field-label">{{ __('Add-ons (optional)') }}</legend>
                                <div class="grid gap-2.5">
                                    @foreach ($addons as $addon)
                                        <div class="group rounded-xl border-[2.5px] border-ink px-4 py-3 has-[:checked]:bg-sakura-soft has-[:checked]:shadow-[3px_3px_0_var(--color-ink)]">
                                            <label class="flex items-start gap-3 cursor-pointer">
                                                <input type="checkbox" name="addons[{{ $addon->id }}]" value="1" class="w-4 h-4 mt-1.5 flex-none" @checked(old('addons.'.$addon->id))
                                                       data-addon data-type="{{ $addon->pricing_type }}" data-percent="{{ (int) $addon->percent }}"
                                                       data-idr="{{ $addon->price_idr }}" data-usd="{{ (float) $addon->price_usd }}" data-max="{{ $addon->max_quantity }}">
                                                <span class="flex-1 min-w-0">
                                                    {{-- Harga turun ke bawah nama kalau tidak muat (layar HP). --}}
                                                    <span class="flex flex-wrap items-baseline justify-between gap-x-3">
                                                        <span class="font-bold">{{ $addon->name }}</span>
                                                        <span class="text-sm font-extrabold" data-currency-label
                                                              data-label-idr="{{ $addon->priceLabel('IDR') }}" data-label-usd="{{ $addon->priceLabel('USD') }}">{{ $addon->priceLabel($currency) }}</span>
                                                    </span>
                                                    @if ($addon->description)
                                                        <span class="block text-sm text-muted">{{ $addon->description }}</span>
                                                    @endif
                                                </span>
                                            </label>
                                            @if ($addon->max_quantity > 1)
                                                <label class="hidden group-has-[:checked]:flex items-center gap-2 mt-2 ml-7 text-sm font-bold">
                                                    {{ __('Quantity') }}
                                                    <input type="number" name="addon_quantity[{{ $addon->id }}]" min="1" max="{{ $addon->max_quantity }}"
                                                           value="{{ old('addon_quantity.'.$addon->id, 1) }}" class="field !w-20 !py-1" data-addon-qty>
                                                </label>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endif

                        <fieldset>
                            <legend class="field-label">{{ __('Design assets') }}</legend>
                            <p class="field-hint !mt-0 mb-2.5">{{ __('I do not make designs. The widget is built from your finished design assets (images or frames, fonts, sounds).') }}</p>
                            <div class="grid gap-2.5">
                                @foreach (config('studio.design_options') as $key => $label)
                                    <label class="flex items-center gap-3 px-4 py-3 cursor-pointer rounded-xl border-[2.5px] border-ink has-[:checked]:bg-sakura-soft has-[:checked]:shadow-[3px_3px_0_var(--color-ink)]">
                                        <input type="radio" name="has_design" value="{{ $key }}" class="w-4 h-4" @checked(old('has_design', 'yes') === $key)>
                                        <span class="font-bold">{{ __($label) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('has_design') <span class="field-error">{{ $message }}</span> @enderror
                        </fieldset>

                        <div>
                            <label for="design_link" class="field-label">{{ __('Link to your design files (optional)') }}</label>
                            <input id="design_link" name="design_link" type="url" class="field" value="{{ old('design_link') }}" placeholder="Google Drive, Dropbox, Figma..." aria-invalid="{{ $err('design_link') }}">
                            @error('design_link') <span class="field-error">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="description" class="field-label">{{ __('Describe the widget') }}</label>
                            <textarea id="description" name="description" rows="6" class="field" required minlength="20" maxlength="3000" aria-invalid="{{ $err('description') }}"
                                      placeholder="{{ __('Example: a chat box for my VTuber overlay, pink frame for mods, star icon for subs, messages slide in from the left and fade after 30 seconds.') }}">{{ old('description') }}</textarea>
                            <span class="field-hint">{{ __('What it shows, how it should move, which events it reacts to, and anything it must not do.') }}</span>
                            @error('description') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                    </fieldset>
                </div>

                <div class="sticker bg-sheet p-6 sm:p-8">
                    <fieldset @disabled(! $open) class="space-y-6">
                        <legend class="flex items-center gap-3 mb-6">
                            {!! $legend('3', 'bg-lemon') !!}
                            <span class="font-display text-2xl">{{ __('Budget and timeline') }}</span>
                        </legend>

                        <div class="grid gap-6 sm:grid-cols-[auto_1fr_1fr] sm:items-start">
                            <div>
                                <span class="field-label" id="currency-label">{{ __('Currency') }}</span>
                                <div class="flex gap-4 py-3" role="radiogroup" aria-labelledby="currency-label">
                                    @foreach (['IDR', 'USD'] as $cur)
                                        <label class="flex items-center gap-2 cursor-pointer font-extrabold">
                                            <input type="radio" name="currency" value="{{ $cur }}" class="w-4 h-4" @checked(old('currency', $currency) === $cur)>
                                            <span>{{ $cur }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label for="budget" class="field-label">{{ __('Budget (optional)') }}</label>
                                <input id="budget" name="budget" type="text" class="field" value="{{ old('budget') }}" maxlength="60" placeholder="{{ $currency === 'USD' ? '50' : '500.000' }}" aria-invalid="{{ $err('budget') }}">
                                @error('budget') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="deadline" class="field-label">{{ __('Needed by (optional)') }}</label>
                                <input id="deadline" name="deadline" type="date" class="field" value="{{ old('deadline') }}" min="{{ now()->addDay()->toDateString() }}" aria-invalid="{{ $err('deadline') }}">
                                @error('deadline') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </fieldset>
                </div>

                {{-- Estimasi dihitung ulang oleh JS saat paket, add-on, atau mata uang berubah. Server menghitung ulang saat order disimpan. --}}
                <div class="panel bg-lemon/40 px-6 py-5" data-estimate data-from="{{ __('from') }}" aria-live="polite">
                    <p class="label">{{ __('Estimated total') }}</p>
                    <p class="font-display text-3xl mt-1 data-[state=empty]:font-sans data-[state=empty]:text-base data-[state=empty]:font-bold" data-state="empty" data-estimate-total data-empty="{{ __('Choose a package to see an estimate.') }}">{{ __('Choose a package to see an estimate.') }}</p>
                    <p class="text-sm text-muted mt-2">{{ __('The final price is sent with your quote.') }}</p>
                </div>

                <fieldset @disabled(! $open) class="space-y-6 px-1">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="agree_terms" value="1" class="w-5 h-5 mt-1" @checked(old('agree_terms')) aria-invalid="{{ $err('agree_terms') }}">
                        <span>{!! __('I have read the :terms and understand that work starts after the deposit is paid.', ['terms' => '<a href="'.route('terms').'" target="_blank" class="font-extrabold underline decoration-sakura">'.e(__('terms of service')).'</a>']) !!}</span>
                    </label>
                    @error('agree_terms') <span class="field-error">{{ $message }}</span> @enderror
                    <p class="text-sm text-muted -mt-3 ml-8">{!! __('Your details are only used to handle this order. See the :privacy.', ['privacy' => '<a href="'.route('privacy').'" target="_blank" class="font-bold underline decoration-sakura">'.e(__('privacy policy')).'</a>']) !!}</p>

                    <button type="submit" class="btn btn-primary w-full sm:w-auto !px-10 !py-4 !text-lg" @disabled(! $open)>{{ __('Send order') }}</button>
                </fieldset>
            </form>
        </div>
    </section>
@endsection
