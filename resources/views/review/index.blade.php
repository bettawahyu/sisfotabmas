<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tugas Review</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <div class="mb-4 flex gap-2 text-sm">
                <a href="{{ route('review.index') }}" class="px-4 py-2 rounded-md {{ $tab === 'menunggu' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Menunggu ({{ $jumlahMenunggu }})</a>
                <a href="{{ route('review.index', ['tab' => 'selesai']) }}" class="px-4 py-2 rounded-md {{ $tab === 'selesai' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">Selesai</a>
            </div>
            <x-kartu>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" data-tabel-data>
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Proposal</th><th class="py-2 pe-4 font-medium">Periode</th><th class="py-2 pe-4 font-medium">{{ $tab === 'selesai' ? 'Dikirim' : 'Batas' }}</th>@if ($tab === 'selesai')<th class="py-2 pe-4 font-medium text-end">Nilai</th>@endif<th></th></tr></thead>
                        <tbody>
                            @forelse ($tugas as $t)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4">@if ($t->tahap === \App\Models\Penilaian::MONEV)<span class="me-1 rounded bg-indigo-50 px-1.5 py-0.5 text-xs text-indigo-700">Monev</span>@endif{{ $t->proposal->judul }}@if ($tab === 'menunggu' && $t->nilai_total !== null)<div class="text-xs text-gray-500">Draf tersimpan</div>@endif</td>
                                    <td class="py-2 pe-4">{{ $t->proposal->periode->nama }}</td>
                                    <td class="py-2 pe-4 whitespace-nowrap">{{ $tab === 'selesai' ? $t->dinilai_at->translatedFormat('d M Y') : (($t->tahap === \App\Models\Penilaian::MONEV ? $t->proposal->periode->tgl_monev : $t->proposal->periode->tgl_seleksi_selesai)?->translatedFormat('d M Y') ?? '-') }}</td>
                                    @if ($tab === 'selesai')<td class="py-2 pe-4 text-end">{{ number_format($t->nilai_total, 2, ',', '.') }}</td>@endif
                                    <td class="py-2 text-end"><a href="{{ route('review.show', $t) }}" class="text-indigo-700 hover:underline">{{ $tab === 'selesai' ? 'Lihat' : 'Nilai' }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-4 text-gray-600">{{ $tab === 'selesai' ? 'Belum ada penilaian yang dikirim.' : 'Tidak ada tugas review yang menunggu.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
