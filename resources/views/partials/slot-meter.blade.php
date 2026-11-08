{{-- Status commission + meter slot. Kotak terisi = slot terpakai. --}}
@php($taken = $studioStatus['total'] - $studioStatus['left'])
<div class="flex flex-wrap items-center gap-x-3 gap-y-2">
    <p class="tally tally-{{ $studioStatus['state'] }}">{{ $studioStatus['label'] }}</p>
    @if ($studioStatus['state'] !== 'closed' && $studioStatus['total'] > 0 && $studioStatus['total'] <= 12)
        <span class="slot-meter" aria-hidden="true">
            @for ($i = 0; $i < $studioStatus['total']; $i++)
                <span @if ($i < $taken) data-taken @endif></span>
            @endfor
        </span>
    @endif
</div>
