@php
    use App\Models\Mitra;

    $tabStatus = collect(Mitra::STATUS)->map(fn ($label, $kode) => [
        'label' => $label,
        'href' => route('mitra.index', ['status' => $kode]),
        'aktif' => $filter['status'] === $kode,
        'jumlah' => $jumlah[$kode] ?? 0,
    ])->values()->all();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mitra</h2>
            <x-tombol-tautan :href="route('mitra.create')" ikon="plus">Tambah mitra</x-tombol-tautan>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <x-tab class="mb-4" :daftar="$tabStatus" />
                <p class="mb-4 text-sm text-gray-600">
                    Data master mitra dipilih dosen saat mencatat riwayat penelitian dan PkM. Mitra baru dari dosen masuk sebagai usulan:
                    terima bila layak masuk data master, atau tolak bila tidak. Mitra yang ditolak tetap tercatat pada kegiatan dosen yang memakainya, tetapi tidak muncul di pilihan dosen lain.
                </p>
                <form method="GET" action="{{ route('mitra.index') }}" class="mb-4 flex flex-wrap gap-3">
                    <input type="hidden" name="status" value="{{ $filter['status'] }}">
                    <x-text-input name="cari" placeholder="Cari nama mitra" :value="$filter['cari'] ?? ''" class="flex-1 min-w-48" />
                    <x-secondary-button type="submit" ikon="search">Cari</x-secondary-button>
                </form>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Nama</th><th class="py-2 pe-4 font-medium">Bidang</th><th class="py-2 pe-4 font-medium">Alamat dan kontak</th><th class="py-2 pe-4 font-medium text-end">Kegiatan</th><th class="py-2 pe-4 font-medium">Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($mitra as $m)
                                <tr class="border-b border-gray-100 last:border-0 align-top">
                                    <td class="py-2 pe-4">
                                        <p class="font-medium">{{ $m->nama }}</p>
                                        @if ($m->status !== Mitra::MASTER && $m->pengusul)
                                            <p class="text-xs text-gray-500">Diusulkan {{ $m->pengusul->name }} {{ $m->created_at->translatedFormat('j F Y') }}</p>
                                        @endif
                                    </td>
                                    <td class="py-2 pe-4">{{ $m->bidang }}</td>
                                    <td class="py-2 pe-4">
                                        {{ collect([$m->alamat, $m->kabupatenKota?->namaLengkap()])->filter()->join(', ') }}
                                        @if ($m->kontak) <p class="text-gray-500">{{ $m->kontak }}</p> @endif
                                    </td>
                                    <td class="py-2 pe-4 text-end">{{ $m->riwayat_kegiatan_count + $m->proposal_mitra_count }}</td>
                                    <td class="py-2 pe-4"><x-status-aktif :aktif="$m->is_active" /></td>
                                    <td class="py-2 text-end whitespace-nowrap">
                                        @if ($m->status === Mitra::USULAN)
                                            <form method="POST" action="{{ route('mitra.verifikasi', $m) }}" class="inline-flex gap-3">
                                                @csrf
                                                <button type="submit" name="aksi" value="terima" class="text-green-700 hover:underline">Terima</button>
                                                <button type="submit" name="aksi" value="tolak" class="text-red-700 hover:underline">Tolak</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('mitra.edit', $m) }}" class="ms-3 text-indigo-700 hover:underline">Ubah</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-4 text-gray-600">Belum ada mitra.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $mitra->links() }}</div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
