<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pengumuman</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl px-4 sm:px-6 lg:px-8">
            <x-kartu>
                <div class="text-xs text-gray-500">
                    {{ $pengumuman->terbit_at?->translatedFormat('d F Y H:i') }}
                    · {{ \App\Models\Pengumuman::KATEGORI[$pengumuman->kategori] ?? $pengumuman->kategori }}
                    @unless ($pengumuman->sedangTampil())
                        <span class="text-amber-700">· belum atau tidak lagi tayang</span>
                    @endunless
                </div>
                <h1 class="mt-1 text-2xl font-semibold text-gray-900">{{ $pengumuman->judul }}</h1>
                <div class="mt-4 text-gray-800 leading-relaxed">{!! nl2br(e($pengumuman->isi)) !!}</div>
                @if ($pengumuman->lampiran)
                    <p class="mt-6 text-sm">Lampiran: <a class="text-indigo-700 underline" href="{{ route('pengumuman.lampiran', $pengumuman) }}">{{ $pengumuman->lampiran->nama_asli }}</a></p>
                @endif
                @if ($pengumuman->periode)
                    <p class="mt-2 text-sm text-gray-600">Periode hibah: {{ $pengumuman->periode->nama }}, pengajuan {{ $pengumuman->periode->tgl_buka->translatedFormat('j M Y') }} sampai {{ $pengumuman->periode->tgl_tutup->translatedFormat('j M Y') }}.</p>
                @endif
                <p class="mt-6"><a href="{{ route('pengumuman.index') }}" class="text-sm text-indigo-700 hover:underline">Kembali ke daftar pengumuman</a></p>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
