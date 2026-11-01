{{-- The community service part of a final report: impact note and links. --}}
@if ($laporan->catatan_dampak || $laporan->tautan_video || $laporan->tautan_media)
    <div class="mt-3 space-y-1 text-sm">
        @if ($laporan->catatan_dampak)
            <p><span class="text-gray-500">Dampak bagi mitra:</span> <span class="whitespace-pre-line">{{ $laporan->catatan_dampak }}</span></p>
        @endif
        @if ($laporan->tautan_video)
            <p><span class="text-gray-500">Video:</span> <a href="{{ $laporan->tautan_video }}" class="text-indigo-700 underline break-all" target="_blank" rel="noopener noreferrer">{{ $laporan->tautan_video }}</a></p>
        @endif
        @foreach ($laporan->daftarTautanMedia() as $tautan)
            <p><span class="text-gray-500">Media massa:</span> <a href="{{ $tautan }}" class="text-indigo-700 underline break-all" target="_blank" rel="noopener noreferrer">{{ $tautan }}</a></p>
        @endforeach
    </div>
@endif
