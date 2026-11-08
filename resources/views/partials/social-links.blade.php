{{-- Ikon kontak di footer. Yang kosong di pengaturan studio tidak ditampilkan. --}}
@php
    $handle = fn (?string $value) => ltrim((string) $value, '@');
    $discord = (string) ($settings['contact_discord'] ?? '');

    $links = array_filter([
        filled($settings['contact_x'] ?? null) ? [
            'label' => 'X @'.$handle($settings['contact_x']),
            'url' => 'https://x.com/'.$handle($settings['contact_x']),
            'icon' => '<path fill="currentColor" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>',
        ] : null,
        filled($settings['contact_instagram'] ?? null) ? [
            'label' => 'Instagram @'.$handle($settings['contact_instagram']),
            'url' => 'https://www.instagram.com/'.$handle($settings['contact_instagram']),
            'icon' => '<g fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4.2"/></g><circle cx="17.4" cy="6.6" r="1.3" fill="currentColor"/>',
        ] : null,
        filled($settings['contact_threads'] ?? null) ? [
            'label' => 'Threads @'.$handle($settings['contact_threads']),
            'url' => 'https://www.threads.com/@'.$handle($settings['contact_threads']),
            'icon' => '<path fill="currentColor" d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.964-.065-1.19.408-2.285 1.33-3.082.88-.76 2.119-1.207 3.583-1.291a13.853 13.853 0 0 1 3.02.142c-.126-.742-.375-1.332-.75-1.757-.513-.586-1.308-.883-2.359-.89h-.029c-.844 0-1.992.232-2.721 1.32L7.734 7.847c.98-1.454 2.568-2.256 4.478-2.256h.044c3.194.02 5.097 1.975 5.287 5.388.108.046.216.094.321.142 1.49.7 2.58 1.761 3.154 3.07.797 1.82.871 4.79-1.548 7.158-1.85 1.81-4.094 2.628-7.277 2.65Zm1.003-11.69c-.242 0-.487.007-.739.021-1.836.103-2.98.946-2.916 2.143.067 1.256 1.452 1.839 2.784 1.767 1.224-.065 2.818-.543 3.086-3.71a10.5 10.5 0 0 0-2.215-.221z"/>',
        ] : null,
        // Discord hanya jadi ikon kalau diisi link invite; username biasa tidak bisa diklik.
        str_starts_with($discord, 'http') ? [
            'label' => 'Discord',
            'url' => $discord,
            'icon' => '<path fill="currentColor" d="M20.317 4.37a19.79 19.79 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.865-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.74 19.74 0 0 0 3.677 4.37a.07.07 0 0 0-.032.028C.533 9.046-.32 13.58.099 18.058a.082.082 0 0 0 .031.056 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.1 13.1 0 0 1-1.872-.892.077.077 0 0 1-.008-.128c.126-.094.252-.192.372-.291a.074.074 0 0 1 .078-.011c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.099.246.198.373.292a.077.077 0 0 1-.006.127 12.3 12.3 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.84 19.84 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.331c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/>',
        ] : null,
        filled($settings['contact_email'] ?? null) ? [
            'label' => 'Email '.$settings['contact_email'],
            'url' => 'mailto:'.$settings['contact_email'],
            'icon' => '<g fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"><rect x="2.5" y="5" width="19" height="14" rx="3"/><path d="m3.5 6.5 8.5 6.5 8.5-6.5"/></g>',
        ] : null,
    ]);
@endphp

@if ($links)
    <ul class="flex flex-wrap gap-3">
        @foreach ($links as $link)
            <li>
                <a href="{{ $link['url'] }}" @unless (str_starts_with($link['url'], 'mailto:')) target="_blank" rel="noopener" @endunless
                   aria-label="{{ $link['label'] }}" title="{{ $link['label'] }}"
                   class="grid place-items-center w-12 h-12 rounded-full border-[3px] border-paper bg-paper text-ink shadow-[3px_3px_0_var(--color-sakura)] transition-transform duration-100 hover:-translate-y-0.5 hover:bg-sakura">
                    <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false">{!! $link['icon'] !!}</svg>
                </a>
            </li>
        @endforeach
    </ul>
@endif
