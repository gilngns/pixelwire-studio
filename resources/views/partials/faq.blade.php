{{-- FAQ dari Pengaturan studio. Tampil di beranda dan halaman layanan; tidak tampil kalau daftarnya kosong. --}}
@php($faq = collect($settings['faq'] ?? [])->filter(fn ($item) => filled($item['question'] ?? null) && filled($item['answer'] ?? null)))
@if ($faq->isNotEmpty())
    <section class="mx-auto max-w-6xl px-4 sm:px-6 mt-28" aria-labelledby="faq-heading" id="faq">
        <div class="grid gap-8 lg:grid-cols-[1fr_2fr]">
            <div>
                <p class="kicker" aria-hidden="true">よくある質問</p>
                <h2 id="faq-heading" class="headline text-4xl sm:text-5xl mt-1 [--shadow:var(--color-mint)]">{{ __('Questions') }}</h2>
                <p class="text-muted mt-4 max-w-sm">{{ __('Not covered here? Ask in your brief and I will answer when I reply.') }}</p>
                <p class="mt-4"><a href="{{ route('terms') }}" class="font-extrabold underline decoration-sakura">{{ __('Read the terms') }}</a></p>
            </div>
            <div class="grid gap-3.5">
                @foreach ($faq as $item)
                    <details class="faq sticker bg-sheet open:bg-sky-soft">
                        <summary class="flex items-center justify-between gap-4 px-5 sm:px-6 py-4 font-extrabold leading-snug">
                            {{ $item['question'] }}
                            <span class="faq-icon" aria-hidden="true"></span>
                        </summary>
                        <div class="px-5 sm:px-6 pb-5 -mt-1 space-y-2 text-muted">
                            @foreach (preg_split('/\r?\n/', trim($item['answer'])) as $line)
                                @if (filled($line))<p>{{ $line }}</p>@endif
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
@endif
