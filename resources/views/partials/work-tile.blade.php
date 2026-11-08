<figure class="group">
    <div class="stage aspect-video overflow-hidden relative sticker transition-transform duration-150 group-hover:-rotate-1">
        @if ($item->isVideo())
            <video class="absolute inset-0 w-full h-full object-cover"
                   src="{{ $item->mediaUrl() }}"
                   @if ($item->posterUrl()) poster="{{ $item->posterUrl() }}" @endif
                   muted loop playsinline preload="metadata" data-autoplay
                   aria-label="{{ __('Demo video: :title', ['title' => $item->title]) }}"></video>
        @elseif ($item->mediaUrl())
            <img class="absolute inset-0 w-full h-full object-cover" src="{{ $item->mediaUrl() }}" alt="{{ $item->title }}" loading="lazy">
        @elseif ($item->posterUrl())
            <img class="absolute inset-0 w-full h-full object-cover" src="{{ $item->posterUrl() }}" alt="{{ $item->title }}" loading="lazy">
        @endif
    </div>
    <figcaption class="mt-4">
        <p class="label text-sakura">
            {{ $item->categoryLabel() }}@if ($item->platformLabel()) / {{ $item->platformLabel() }}@endif
        </p>
        <p class="text-lg font-extrabold leading-tight mt-1">
            @if ($item->externalLink())
                <a href="{{ $item->externalLink() }}" target="_blank" rel="noopener" class="no-underline hover:underline">{{ $item->title }}</a>
            @else
                {{ $item->title }}
            @endif
            @if ($item->client_label)<span class="font-bold text-muted">{{ __('for :client', ['client' => $item->client_label]) }}</span>@endif
        </p>
        @if ($item->description)
            <p class="text-[0.95rem] mt-1.5 text-muted">{{ $item->description }}</p>
        @endif
    </figcaption>
</figure>
