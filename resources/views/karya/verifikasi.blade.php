@php
    use App\Models\Hki;
    use App\Models\PublikasiPenulis;
    use App\Services\AlurKarya;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Verifikasi Publikasi, HKI, dan Paten</h2>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            <x-pesan />
            <p class="text-sm text-gray-600">Kaprodi atau dekan memeriksa data dosen di unitnya lebih dulu, lalu LPPM memvalidasinya. Hanya data yang valid yang dihitung dalam laporan.</p>
            @php
                $tabAwal = collect($menunggu)->search(fn ($daftar) => $daftar->isNotEmpty()) ?: 'publikasi';
                $tabVerifikasi = collect($menunggu)->map(fn ($daftar, $jenis) => [
                    'label' => AlurKarya::LABEL[$jenis],
                    'ikon' => AlurKarya::IKON[$jenis],
                    'kunci' => $jenis,
                    'jumlah' => $daftar->count(),
                ])->values()->all();
            @endphp
            {{-- The chosen tab goes into the address, so the page comes back on it after a decision. --}}
            <div x-data="{ tab: new URLSearchParams(location.search).get('tab') || @js($tabAwal) }"
                x-init="$watch('tab', t => history.replaceState(null, '', '?tab=' + t))">
            <x-kartu>
                <x-tab class="mb-4" :daftar="$tabVerifikasi" />
            @foreach ($menunggu as $jenis => $daftar)
                <div x-show="tab === @js($jenis)" x-cloak role="tabpanel">
                    <div class="space-y-4">
                        @forelse ($daftar as $k)
                            <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0 text-sm">
                                <p class="font-medium">{{ $k->judul }}</p>
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
                                    @if ($k->status_verifikasi === Hki::DISETUJUI_KAPRODI)
                                        · disetujui {{ $k->verifikatorKaprodi?->name }} {{ $k->diverifikasi_kaprodi_at?->translatedFormat('j F Y') }}
                                    @endif
                                </p>
                                <form method="POST" action="{{ route('verifikasi-karya.store', [$jenis, $k->id]) }}" class="mt-2 flex flex-wrap gap-3">
                                    @csrf
                                    <x-text-input name="catatan" placeholder="Alasan bila ditolak" class="flex-1" />
                                    <x-secondary-button type="submit" name="aksi" value="tolak" ikon="x-circle">Tolak</x-secondary-button>
                                    <x-primary-button name="aksi" value="setujui" ikon="check-circle">{{ $k->status_verifikasi === Hki::DISETUJUI_KAPRODI ? 'Validasi' : 'Setujui' }}</x-primary-button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-gray-600">Tidak ada yang menunggu.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
            </x-kartu>
            </div>
        </div>
    </div>
</x-app-layout>
