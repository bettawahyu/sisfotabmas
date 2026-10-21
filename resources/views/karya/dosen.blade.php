@php
    use App\Models\Hki;
    use App\Models\PublikasiPenulis;
    use App\Services\AlurKarya;

    $tersaring = array_filter(\Illuminate\Support\Arr::except($filter, 'tab'), fn ($nilai) => filled($nilai) && $nilai !== 'semua');
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Publikasi &amp; KI Dosen</h2>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            <x-pesan />
            @php
                $tabAwal = $filter['tab'] ?? (collect($karya)->search(fn ($daftar) => $daftar->isNotEmpty()) ?: 'publikasi');
                $tabKarya = collect($karya)->map(fn ($daftar, $jenis) => [
                    'label' => AlurKarya::LABEL[$jenis],
                    'ikon' => AlurKarya::IKON[$jenis],
                    'kunci' => $jenis,
                    'jumlah' => $daftar->count(),
                ])->values()->all();
            @endphp
            {{-- The chosen tab goes into the address, so the page comes back on it after a decision or a filter. --}}
            <div x-data="{ tab: new URLSearchParams(location.search).get('tab') || @js($tabAwal) }"
                x-init="$watch('tab', t => { const u = new URL(location); u.searchParams.set('tab', t); history.replaceState(null, '', u) })"
                class="space-y-6">
            <x-kartu>
                <form method="GET" action="{{ route('karya-dosen.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                    <input type="hidden" name="tab" x-bind:value="tab">
                    <div class="lg:col-span-2">
                        <x-input-label for="cari" value="Judul" ikon="search" />
                        <x-text-input id="cari" name="cari" class="mt-1 block w-full" :value="$filter['cari'] ?? ''" />
                    </div>
                    <div>
                        <x-input-label for="prodi_id" value="Program studi" ikon="book" />
                        <x-select-input id="prodi_id" name="prodi_id" class="mt-1 block w-full">
                            <option value="">Semua program studi</option>
                            @foreach ($prodi as $p)
                                <option value="{{ $p->id }}" @selected((string) ($filter['prodi_id'] ?? '') === (string) $p->id)>{{ $p->jenjang }} {{ $p->nama }} ({{ $p->fakultas->nama }})</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="status" value="Verifikasi" ikon="shield" />
                        <x-select-input id="status" name="status" class="mt-1 block w-full">
                            <option value="semua">Semua status</option>
                            @foreach (Hki::STATUS_VERIFIKASI as $kode => $label)
                                <option value="{{ $kode }}" @selected($filter['status'] === $kode)>{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-4">
                        <x-secondary-button type="submit" ikon="filter">Terapkan filter</x-secondary-button>
                    </div>
                </form>
                <p class="mt-4 text-xs text-gray-500">
                    @unless ($lihatSemua) Hanya data dosen di unit Anda yang ditampilkan. @endunless
                    LPPM atau operator memverifikasi setiap data. Hanya data yang valid yang dihitung dalam laporan.
                </p>
            </x-kartu>

            <x-kartu>
                <x-tab class="mb-4" :daftar="$tabKarya" />
            @foreach ($karya as $jenis => $daftar)
                <div x-show="tab === @js($jenis)" x-cloak role="tabpanel">
                    <div class="space-y-4">
                        @forelse ($daftar as $k)
                            <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0 text-sm">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <p class="font-medium">{{ $k->judul }}</p>
                                    <span class="{{ $k->status_verifikasi === Hki::VALID ? 'text-green-700' : ($k->status_verifikasi === Hki::DITOLAK ? 'text-red-700' : 'text-gray-600') }}">{{ $k->namaStatusVerifikasi() }}</span>
                                </div>
                                <p class="text-gray-600">
                                    {{ $k->namaJenis() }} · {{ $k->namaStatus() }}
                                    @if ($jenis === 'publikasi')
                                        · {{ $k->nama_wadah }} {{ $k->tahun }}{{ $k->peringkat ? ' · '.$k->namaPeringkat() : '' }}
                                    @elseif ($k->no_pencatatan ?? $k->no_paten ?? $k->no_permohonan)
                                        · No. {{ $k->no_pencatatan ?? $k->no_paten ?? $k->no_permohonan }}
                                    @endif
                                </p>
                                <p class="text-gray-600">{{ $k->kontributor->map(fn ($n) => $n instanceof PublikasiPenulis ? $n->nama_penulis : $n->nama)->join(', ') }}</p>
                                @php($tautan = $k->url ?? $k->url_pdki ?? ($k->doi ? 'https://doi.org/'.$k->doi : null))
                                @if ($tautan)
                                    <a href="{{ $tautan }}" class="text-indigo-700 underline break-all" rel="noopener noreferrer" target="_blank">{{ $tautan }}</a>
                                @endif
                                <p class="text-gray-500">
                                    Diisi {{ $k->penginput?->name ?? '-' }}
                                    @if ($k->status_verifikasi === Hki::VALID)
                                        · divalidasi {{ $k->verifikatorLppm?->name }} {{ $k->diverifikasi_lppm_at?->translatedFormat('j F Y') }}
                                    @endif
                                </p>
                                @if ($k->status_verifikasi === Hki::DITOLAK && $k->catatan_verifikasi)
                                    <p class="text-red-700">{{ $k->catatan_verifikasi }}</p>
                                @endif
                                @if ($verifikator && $k->status_verifikasi === Hki::MENUNGGU)
                                    <form method="POST" action="{{ route('karya-dosen.verifikasi', [$jenis, $k->id]) }}" class="mt-2 flex flex-wrap gap-3">
                                        @csrf
                                        <x-text-input name="catatan" placeholder="Alasan bila ditolak" class="flex-1" />
                                        <x-secondary-button type="submit" name="aksi" value="tolak" ikon="x-circle">Tolak</x-secondary-button>
                                        <x-primary-button name="aksi" value="setujui" ikon="check-circle">Validasi</x-primary-button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-600">{{ $tersaring ? 'Tidak ada data yang cocok dengan filter.' : 'Belum ada data '.AlurKarya::LABEL[$jenis].'.' }}</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
            </x-kartu>
            </div>
        </div>
    </div>
</x-app-layout>
