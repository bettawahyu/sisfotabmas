@php
    use App\Models\RiwayatKegiatan;
    use App\Services\AlurRiwayat;

    $tabJenis = collect(AlurRiwayat::LABEL)->map(fn ($label, $slug) => [
        'label' => $slug === 'pkm' ? 'PkM' : 'Penelitian',
        'ikon' => $slug === 'pkm' ? 'heart' : 'search',
        'href' => route('riwayat-dosen.index', $slug),
        'aktif' => $slug === $jenis,
    ])->values()->all();
    $tersaring = array_filter(\Illuminate\Support\Arr::except($filter, 'status'), fn ($nilai) => filled($nilai)) || $filter['status'] !== 'semua';
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Riwayat Penelitian &amp; PkM Dosen</h2>
            @if ($petugas)
                <x-tombol-tautan :href="route('riwayat.create', $jenis)" ikon="plus">Catat untuk dosen</x-tombol-tautan>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            <x-pesan />
            <x-kartu>
                <x-tab class="mb-4" :daftar="$tabJenis" />
                <form method="GET" action="{{ route('riwayat-dosen.index', $jenis) }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6 lg:items-end">
                    <div class="lg:col-span-2">
                        <x-input-label for="cari" value="Judul atau nama" ikon="search" />
                        <x-text-input id="cari" name="cari" class="mt-1 block w-full" :value="$filter['cari'] ?? ''" />
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
                        <x-input-label for="tahun" value="Tahun" ikon="calendar" />
                        <x-text-input id="tahun" name="tahun" type="number" class="mt-1 block w-full" :value="$filter['tahun'] ?? ''" />
                    </div>
                    <div>
                        <x-input-label for="kegiatan" value="Status kegiatan" ikon="flag" />
                        <x-select-input id="kegiatan" name="kegiatan" class="mt-1 block w-full">
                            <option value="">Semua</option>
                            @foreach (RiwayatKegiatan::STATUS_KEGIATAN as $kode => $label)
                                <option value="{{ $kode }}" @selected(($filter['kegiatan'] ?? '') === $kode)>{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="status" value="Verifikasi" ikon="shield" />
                        <x-select-input id="status" name="status" class="mt-1 block w-full">
                            <option value="semua">Semua</option>
                            @foreach (RiwayatKegiatan::STATUS_VERIFIKASI as $kode => $label)
                                <option value="{{ $kode }}" @selected($filter['status'] === $kode)>{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="sumber_dana" value="Sumber dana" ikon="dollar-sign" />
                        <x-select-input id="sumber_dana" name="sumber_dana" class="mt-1 block w-full">
                            <option value="">Semua</option>
                            @foreach (RiwayatKegiatan::SUMBER_DANA as $kode => $label)
                                <option value="{{ $kode }}" @selected(($filter['sumber_dana'] ?? '') === $kode)>{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-5">
                        <x-secondary-button type="submit" ikon="filter">Terapkan filter</x-secondary-button>
                    </div>
                </form>
                <p class="mt-4 text-xs text-gray-500">
                    @unless ($lihatSemua) Hanya data dosen di unit Anda yang ditampilkan. @endunless
                    Kegiatan di luar hibah LPPM. Data yang diisi dosen diverifikasi LPPM atau operator; data yang diisi LPPM atau operator langsung valid. Hibah LPPM dilihat di menu proposal.
                </p>
            </x-kartu>

            <x-kartu>
                <div class="space-y-4">
                    @forelse ($riwayat as $r)
                        <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                            @include('riwayat._entri', ['r' => $r])
                            <p class="mt-1 text-sm text-gray-500">
                                Diisi {{ $r->penginput?->name ?? '-' }}
                                @if ($r->status_verifikasi === RiwayatKegiatan::VALID)
                                    · divalidasi {{ $r->verifikatorLppm?->name }} {{ $r->diverifikasi_lppm_at?->translatedFormat('j F Y') }}
                                @endif
                                @if ($petugas)
                                    · <a href="{{ route('riwayat.edit', [$jenis, $r]) }}" class="text-indigo-700 hover:underline">Koreksi</a>
                                @endif
                            </p>
                            @if ($petugas && $r->status_verifikasi === RiwayatKegiatan::MENUNGGU)
                                <form method="POST" action="{{ route('riwayat-dosen.verifikasi', [$jenis, $r]) }}" class="mt-2 flex flex-wrap gap-3">
                                    @csrf
                                    <x-text-input name="catatan" placeholder="Alasan bila ditolak" class="flex-1" />
                                    <x-secondary-button type="submit" name="aksi" value="tolak" ikon="x-circle">Tolak</x-secondary-button>
                                    <x-primary-button name="aksi" value="setujui" ikon="check-circle">Validasi</x-primary-button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-600">{{ $tersaring ? 'Tidak ada data yang cocok dengan filter.' : 'Belum ada data.' }}</p>
                    @endforelse
                </div>
                <div class="mt-4">{{ $riwayat->links() }}</div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
