<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pengumuman</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl px-4 sm:px-6 lg:px-8 space-y-4">
            @forelse ($pengumuman as $p)
                <x-kartu>
                    <div class="flex flex-col gap-4 sm:flex-row">
                        @if ($p->gambar)
                            <a href="{{ route('pengumuman.show', $p) }}" class="shrink-0">
                                <img src="{{ route('pengumuman.gambar', $p) }}?v={{ $p->gambar->id }}" alt="" loading="lazy" class="h-40 w-full rounded-md object-cover sm:h-28 sm:w-44">
                            </a>
                        @endif
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-baseline gap-2 text-xs text-gray-500">
                                <span>{{ $p->terbit_at->translatedFormat('d F Y') }}</span>
                                <span>· {{ \App\Models\Pengumuman::KATEGORI[$p->kategori] ?? $p->kategori }}</span>
                                @if ($p->is_pinned)<span class="text-amber-700">· disematkan</span>@endif
                            </div>
                            <a href="{{ route('pengumuman.show', $p) }}" class="mt-1 block text-lg font-medium text-gray-900 hover:underline">{{ $p->judul }}</a>
                            <p class="mt-2 text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($p->isi, 240) }}</p>
                            @if ($p->periode)
                                <p class="mt-2 text-xs text-gray-500">Periode: {{ $p->periode->nama }}</p>
                            @endif
                        </div>
                    </div>
                </x-kartu>
            @empty
                <x-kartu><p class="text-sm text-gray-600">Belum ada pengumuman.</p></x-kartu>
            @endforelse
            {{ $pengumuman->links() }}
        </div>
    </div>
</x-app-layout>
