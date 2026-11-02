@php
    use App\Models\RiwayatKegiatan;

    $tabJenis = collect(['penelitian' => ['Penelitian', 'search'], 'pkm' => ['PkM', 'heart']])->map(fn ($tab, $slug) => [
        'label' => $tab[0],
        'ikon' => $tab[1],
        'href' => route('riwayat-impor.create', $slug),
        'aktif' => $slug === $jenis,
    ])->values()->all();
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $impor->judul() }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="space-y-6 px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu class="max-w-3xl">
                <x-tab class="mb-4" :daftar="$tabJenis" />
                <p class="mb-4 text-sm text-gray-600">{{ $impor->petunjuk() }}</p>
                <form method="POST" action="{{ route('riwayat-impor.periksa', $jenis) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                    @csrf
                    <input type="file" name="berkas" accept=".xlsx" required class="text-sm max-w-full">
                    <x-primary-button ikon="eye">Periksa</x-primary-button>
                    <a href="{{ route('riwayat-impor.templat', $jenis) }}" class="inline-flex items-center gap-1 text-sm text-indigo-700 hover:underline"><x-ikon nama="download" /> Unduh templat {{ $jenis === 'pkm' ? 'PkM' : 'penelitian' }}</a>
                    <a href="{{ route('riwayat-dosen.index', $jenis) }}" class="text-sm text-gray-600 hover:underline">Kembali</a>
                </form>
                <x-input-error :messages="$errors->get('berkas')" class="mt-2" />
            </x-kartu>

            @if ($hasil)
                @php
                    $hasil = collect($hasil);
                    $bergalat = $hasil->filter(fn ($b) => $b['galat'])->count();
                    $ganda = $hasil->where('aksi', 'ganda')->filter(fn ($b) => ! $b['galat'])->count();
                @endphp
                <x-kartu judul="Pratinjau" ikon="eye">
                    <p class="mb-4 text-sm {{ $bergalat ? 'text-red-700' : 'text-gray-600' }}">
                        {{ $hasil->count() }} baris dibaca.
                        @if ($bergalat)
                            {{ $bergalat }} baris bergalat; perbaiki berkas lalu unggah lagi.
                        @else
                            {{ $hasil->count() - $ganda }} siap disimpan{{ $ganda ? ", {$ganda} mirip data lain" : '' }}.
                        @endif
                    </p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-left text-gray-500 border-b">
                                <th class="py-2 pe-4 font-medium">Baris</th>
                                <th class="py-2 pe-4 font-medium">Judul</th>
                                <th class="py-2 pe-4 font-medium">Tahun</th>
                                <th class="py-2 pe-4 font-medium">Status</th>
                                <th class="py-2 pe-4 font-medium">Sumber dana</th>
                                <th class="py-2 pe-4 font-medium">Ketua</th>
                                <th class="py-2 pe-4 font-medium">Anggota dosen</th>
                                <th class="py-2 pe-4 font-medium">Mitra</th>
                                <th class="py-2 font-medium min-w-64">Hasil</th>
                            </tr></thead>
                            <tbody>
                                @foreach ($hasil as $i => $b)
                                    @php($s = $b['data']['_simpan'])
                                    <tr class="border-b border-gray-100 last:border-0 align-top {{ $b['galat'] ? 'bg-red-50' : ($b['aksi'] === 'ganda' ? 'bg-amber-50' : '') }}">
                                        <td class="py-2 pe-4 text-gray-500">{{ $i + 2 }}</td>
                                        <td class="py-2 pe-4 min-w-64">{{ $b['data']['judul'] ?? '' }}</td>
                                        <td class="py-2 pe-4 whitespace-nowrap">{{ $s['tahun_mulai'] }}{{ $s['tahun_selesai'] && $s['tahun_selesai'] !== $s['tahun_mulai'] ? '–'.$s['tahun_selesai'] : '' }}</td>
                                        <td class="py-2 pe-4">{{ RiwayatKegiatan::STATUS_KEGIATAN[$s['status_kegiatan']] ?? ($b['data']['status_kegiatan'] ?? '') }}</td>
                                        <td class="py-2 pe-4">{{ RiwayatKegiatan::SUMBER_DANA[$s['sumber_dana']] ?? ($b['data']['sumber_dana'] ?? '') }}</td>
                                        <td class="py-2 pe-4">{{ $b['data']['_ringkas']['ketua'] ?? $b['data']['nidn_ketua'] ?? '' }}</td>
                                        <td class="py-2 pe-4">{{ $b['data']['_ringkas']['anggota'] }}</td>
                                        <td class="py-2 pe-4">{{ implode('; ', $s['mitra']) }}</td>
                                        <td class="py-2">
                                            @if ($b['galat'])
                                                <span class="text-red-700">{{ implode(' ', $b['galat']) }}</span>
                                            @else
                                                <span class="{{ $b['aksi'] === 'ganda' ? 'text-amber-800' : 'text-green-700' }}">{{ $b['aksi'] === 'ganda' ? 'Mirip data lain' : 'Siap' }}</span>
                                            @endif
                                            @foreach ($b['peringatan'] as $p)
                                                <span class="block text-gray-600">{{ $p }}</span>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @unless ($bergalat)
                        <form method="POST" action="{{ route('riwayat-impor.simpan', $jenis) }}" class="mt-4 space-y-3">
                            @csrf
                            @if ($ganda)
                                <label class="flex items-start gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="sertakan_ganda" value="1" class="mt-0.5 rounded border-gray-300 text-indigo-600">
                                    Tetap simpan {{ $ganda }} baris yang mirip data lain. Bila tidak dicentang, baris itu dilewati.
                                </label>
                            @endif
                            <x-primary-button ikon="save">Simpan</x-primary-button>
                        </form>
                    @endunless
                </x-kartu>
            @endif
        </div>
    </div>
</x-app-layout>
