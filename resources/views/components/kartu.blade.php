@props(['judul' => null, 'ikon' => null])
<section {{ $attributes->merge(['class' => 'rounded-md bg-white p-6 shadow']) }}>
    @if ($judul)
        <div class="mb-4 flex items-center gap-3 border-b border-gray-300 pb-4">
            @if ($ikon)
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon :nama="$ikon" /></span>
            @endif
            <h3 class="text-lg font-semibold text-gray-800">{{ $judul }}</h3>
        </div>
    @endif
    {{ $slot }}
</section>
