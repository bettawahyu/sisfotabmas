@php
    use App\Services\RekapRiwayat;

    $pkm = $jenis === 'pkm';
    $tabJenis = collect(['penelitian' => ['Penelitian', 'search'], 'pkm' => ['PkM', 'heart']])->map(fn ($tab, $slug) => [
        'label' => $tab[0],
        'ikon' => $tab[1],
        'href' => route('rekap-riwayat.index', [$slug, ...request()->query()]),
        'aktif' => $slug === $jenis,
    ])->values()->all();
    $th = 'py-2 pe-4 font-medium';
    $td = 'py-2 pe-4';
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rekap Penelitian &amp; PkM</h2>
            <p class="mt-1 text-sm text-gray-600">
                Hibah LPPM (didanai sampai selesai) ditambah riwayat di luar hibah yang sudah divalidasi, menurut tahun mulai kegiatan.
                @unless ($lihatSemua) Hanya dosen di unit Anda. @endunless
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            <x-kartu>
                <x-tab class="mb-4" :daftar="$tabJenis" />
                <form method="GET" action="{{ route('rekap-riwayat.index', $jenis) }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6 lg:items-end">
                    <div>
                        <x-input-label for="tahun_dari" value="Dari tahun" ikon="calendar" />
                        <x-text-input id="tahun_dari" name="tahun_dari" type="number" class="mt-1 block w-full" :value="$filter['tahun_dari']" />
                    </div>
                    <div>
                        <x-input-label for="tahun_sampai" value="Sampai tahun" ikon="calendar" />
                        <x-text-input id="tahun_sampai" name="tahun_sampai" type="number" class="mt-1 block w-full" :value="$filter['tahun_sampai']" />
                    </div>
                    <div>
                        <x-input-label for="prodi_id" value="Program studi" ikon="book" />
                        <x-select-input id="prodi_id" name="prodi_id" class="mt-1 block w-full">
                            <option value="">Semua</option>
                            @foreach ($prodi as $p)
                                <option value="{{ $p->id }}" @selected((string) ($filter['prodi_id'] ?? '') === (string) $p->id)>{{ $p->jenjang }} {{ $p->nama }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="sumber_dana" value="Sumber dana" ikon="dollar-sign" />
                        <x-select-input id="sumber_dana" name="sumber_dana" class="mt-1 block w-full">
                            <option value="">Semua</option>
                            @foreach (RekapRiwayat::sumberDana() as $kode => $label)
                                <option value="{{ $kode }}" @selected(($filter['sumber_dana'] ?? '') === $kode)>{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div class="flex flex-wrap gap-2 sm:col-span-2">
                        <x-primary-button ikon="filter">Tampilkan</x-primary-button>
                        <x-tombol-tautan :href="route('rekap-riwayat.index', $jenis)" varian="kedua" ikon="x" class="whitespace-nowrap">Atur ulang</x-tombol-tautan>
                    </div>
                </form>
            </x-kartu>

            <div class="grid gap-6 grid-cols-2 lg:grid-cols-5">
                <x-kartu>
                    <p class="text-sm text-gray-600">Jumlah kegiatan</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $ringkas['jumlah'] }}</p>
                </x-kartu>
                <x-kartu>
                    <p class="text-sm text-gray-600">Selesai</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $ringkas['selesai'] }}</p>
                </x-kartu>
                <x-kartu>
                    <p class="text-sm text-gray-600">Sedang berlangsung</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $ringkas['berlangsung'] }}</p>
                </x-kartu>
                <x-kartu>
                    <p class="text-sm text-gray-600">Dosen terlibat</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $ringkas['dosen'] }}</p>
                </x-kartu>
                <x-kartu class="col-span-2 lg:col-span-1">
                    <p class="text-sm text-gray-600">Total dana</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900"><x-rupiah :nilai="$ringkas['dana']" /></p>
                </x-kartu>
            </div>

            @if ($ringkas['menunggu'] > 0)
                <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-md px-4 py-3">
                    {{ $ringkas['menunggu'] }} riwayat pada rentang ini masih menunggu verifikasi LPPM dan belum dihitung.
                    @can('lihat-data-dosen')
                        <a href="{{ route('riwayat-dosen.index', [$jenis, 'status' => 'menunggu']) }}" class="font-medium underline">Lihat datanya</a>.
                    @endcan
                </p>
            @endif

            <x-kartu ikon="calendar" judul="Per tahun dan sumber dana">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="{{ $th }}">Tahun</th>
                                @foreach ($perTahun['sumber'] as $label)
                                    <th class="{{ $th }} text-end">{{ $label }}</th>
                                @endforeach
                                <th class="{{ $th }} text-end">Selesai</th>
                                <th class="{{ $th }} text-end">Berlangsung</th>
                                <th class="{{ $th }} text-end">Jumlah</th>
                                <th class="py-2 font-medium text-end">Dana</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($perTahun['baris'] as $b)
                                <tr class="border-b border-gray-100">
                                    <td class="{{ $td }}">{{ $b['tahun'] }}</td>
                                    @foreach ($b['sumber'] as $n)
                                        <td class="{{ $td }} text-end">{{ $n }}</td>
                                    @endforeach
                                    <td class="{{ $td }} text-end">{{ $b['selesai'] }}</td>
                                    <td class="{{ $td }} text-end">{{ $b['berlangsung'] }}</td>
                                    <td class="{{ $td }} text-end font-medium">{{ $b['jumlah'] }}</td>
                                    <td class="py-2 text-end whitespace-nowrap"><x-rupiah :nilai="$b['dana']" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold text-gray-900">
                                <td class="{{ $td }}">Total</td>
                                @foreach ($perTahun['total']['sumber'] as $n)
                                    <td class="{{ $td }} text-end">{{ $n }}</td>
                                @endforeach
                                <td class="{{ $td }} text-end">{{ $perTahun['total']['selesai'] }}</td>
                                <td class="{{ $td }} text-end">{{ $perTahun['total']['berlangsung'] }}</td>
                                <td class="{{ $td }} text-end">{{ $perTahun['total']['jumlah'] }}</td>
                                <td class="py-2 text-end whitespace-nowrap"><x-rupiah :nilai="$perTahun['total']['dana']" /></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-kartu>

            <x-kartu ikon="book" judul="Per program studi">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="{{ $th }}">Program studi</th>
                                <th class="{{ $th }} text-end">Kegiatan</th>
                                <th class="{{ $th }} text-end">Dosen aktif</th>
                                <th class="{{ $th }} text-end">Persentase</th>
                                <th class="py-2 font-medium text-end">Dana (diketuai)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($perProdi as $p)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="{{ $td }}">{{ $p['nama'] }}</td>
                                    <td class="{{ $td }} text-end">{{ $p['kegiatan'] }}</td>
                                    <td class="{{ $td }} text-end whitespace-nowrap">{{ $p['dosen_aktif'] }} dari {{ $p['dosen_total'] }}</td>
                                    <td class="{{ $td }} text-end">{{ $p['persen'] }}%</td>
                                    <td class="py-2 text-end whitespace-nowrap"><x-rupiah :nilai="$p['dana']" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-4 text-gray-600">Belum ada dosen pada program studi ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-gray-500">Satu kegiatan dihitung di setiap program studi yang dosennya ikut serta. Dana dihitung di program studi ketuanya.</p>
            </x-kartu>

            <x-kartu ikon="users" judul="Per dosen">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="{{ $th }}">Dosen</th>
                                <th class="{{ $th }}">Program studi</th>
                                <th class="{{ $th }} text-end">Ketua</th>
                                <th class="{{ $th }} text-end">Anggota</th>
                                <th class="py-2 font-medium text-end">Tahun terakhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($perDosen as $d)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="{{ $td }}">{{ $d['dosen']->nama }}</td>
                                    <td class="{{ $td }} text-gray-600">{{ $d['dosen']->prodi ? trim("{$d['dosen']->prodi->jenjang} {$d['dosen']->prodi->nama}") : '-' }}</td>
                                    <td class="{{ $td }} text-end">{{ $d['ketua'] }}</td>
                                    <td class="{{ $td }} text-end">{{ $d['anggota'] }}</td>
                                    <td class="py-2 text-end">{{ $d['terakhir'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-4 text-gray-600">Belum ada kegiatan pada rentang ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>

            @if ($pkm)
                <x-kartu ikon="map-pin" judul="Mitra dan sebaran lokasi">
                    <p class="text-sm text-gray-600">{{ $jumlahMitra }} mitra berbeda terlibat pada rentang ini.</p>
                    <div class="mt-4 space-y-4">
                        @forelse ($perWilayah as $w)
                            <div>
                                <p class="flex justify-between text-sm font-medium text-gray-900"><span>{{ $w['provinsi'] }}</span><span>{{ $w['jumlah'] }}</span></p>
                                <ul class="mt-1 space-y-1">
                                    @foreach ($w['kabupaten'] as $k)
                                        <li class="flex justify-between ps-4 text-sm text-gray-600"><span>{{ $k['nama'] }}</span><span>{{ $k['jumlah'] }}</span></li>
                                    @endforeach
                                </ul>
                            </div>
                        @empty
                            <p class="text-sm text-gray-600">Belum ada kegiatan dengan lokasi kabupaten/kota.</p>
                        @endforelse
                    </div>
                    <p class="mt-3 text-xs text-gray-500">Lokasi hibah LPPM diambil dari kabupaten/kota mitranya.</p>
                </x-kartu>
            @endif
        </div>
    </div>
</x-app-layout>
