@php
    use App\Enums\StatusProposal;
    use App\Models\Berkas;

    $periode = $proposal->periode;
    $isKetua = $proposal->isKetua(auth()->user()->dosen);
    $monev = $proposal->penilaian->first();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pelaksanaan: {{ $proposal->judul }}</h2>
            <x-status-proposal :proposal="$proposal" />
        </div>
        <p class="mt-1 text-sm text-gray-600"><a href="{{ route('proposal.show', $proposal) }}" class="text-indigo-700 hover:underline">Kembali ke proposal</a> · {{ $periode->nama }}</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-pesan />

            @if ($templat->isNotEmpty())
                <x-kartu judul="Templat">
                    <ul class="space-y-1 text-sm">
                        @foreach ($templat as $t)
                            <li><a class="text-indigo-700 underline" href="{{ route('templat.unduh', $t) }}">{{ $t->nama }}</a> <span class="text-gray-500">· {{ $t->namaJenis() }}</span></li>
                        @endforeach
                    </ul>
                </x-kartu>
            @endif

            <x-kartu judul="Laporan kemajuan">
                @if ($laporanKemajuan)
                    <dl class="grid gap-3 text-sm sm:grid-cols-3">
                        <div><dt class="text-gray-500">Dikirim</dt><dd>{{ $laporanKemajuan->diajukan_at->translatedFormat('j F Y') }}</dd></div>
                        <div><dt class="text-gray-500">Capaian kegiatan</dt><dd>{{ $laporanKemajuan->persentase_capaian }}%</dd></div>
                        <div><dt class="text-gray-500">Anggaran terpakai</dt><dd>{{ $laporanKemajuan->persentase_anggaran_terpakai }}%</dd></div>
                        <div class="sm:col-span-3"><dt class="text-gray-500">Ringkasan</dt><dd class="whitespace-pre-line">{{ $laporanKemajuan->ringkasan }}</dd></div>
                    </dl>
                    <ul class="mt-4 space-y-1 text-sm">
                        @foreach ($laporanKemajuan->berkas as $berkas)
                            <li><a class="text-indigo-700 underline" href="{{ route('proposal.pelaksanaan.berkas', [$proposal, $berkas]) }}">{{ $berkas->kategori === Berkas::LAPORAN_KEUANGAN ? 'Laporan keuangan' : 'Laporan kemajuan' }}</a> <span class="text-gray-500">· {{ $berkas->nama_asli }}</span></li>
                        @endforeach
                    </ul>
                    <div class="mt-4 border-t border-gray-100 pt-4 text-sm">
                        @if ($monev)
                            <p><span class="text-gray-500">Hasil monev:</span> <span class="font-medium">{{ $monev->namaRekomendasi() }}</span></p>
                            <p class="mt-1 whitespace-pre-line text-gray-700">{{ $monev->komentar_untuk_pengusul }}</p>
                        @else
                            <p class="text-gray-600">Menunggu hasil monev dari reviewer.</p>
                        @endif
                    </div>
                @elseif ($proposal->status === StatusProposal::Berjalan && $isKetua)
                    <p class="mb-4 text-sm text-gray-600">Unggah laporan kemajuan dan laporan penggunaan dana termin 1{{ $periode->tgl_laporan_kemajuan ? ' paling lambat '.$periode->tgl_laporan_kemajuan->translatedFormat('j F Y') : '' }}. Setelah dikirim, laporan tidak bisa diubah dan proposal masuk tahap monev.</p>
                    <form method="POST" action="{{ route('proposal.pelaksanaan.laporan-kemajuan.store', $proposal) }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div class="sm:col-span-2">
                            <x-input-label for="ringkasan" value="Ringkasan kemajuan" />
                            <textarea id="ringkasan" name="ringkasan" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>{{ old('ringkasan') }}</textarea>
                        </div>
                        <div><x-input-label for="persentase_capaian" value="Capaian kegiatan (%)" /><x-text-input id="persentase_capaian" name="persentase_capaian" type="number" min="0" max="100" class="mt-1 block w-full" :value="old('persentase_capaian')" required /></div>
                        <div><x-input-label for="persentase_anggaran_terpakai" value="Anggaran terpakai (%)" /><x-text-input id="persentase_anggaran_terpakai" name="persentase_anggaran_terpakai" type="number" min="0" max="100" class="mt-1 block w-full" :value="old('persentase_anggaran_terpakai')" required /></div>
                        <div><x-input-label for="laporan" value="Laporan kemajuan (PDF)" /><input id="laporan" name="laporan" type="file" accept="application/pdf" class="mt-1 block w-full text-sm" required></div>
                        <div><x-input-label for="keuangan" value="Laporan keuangan termin 1 (PDF)" /><input id="keuangan" name="keuangan" type="file" accept="application/pdf" class="mt-1 block w-full text-sm" required></div>
                        <div class="sm:col-span-2"><x-primary-button onclick="return confirm('Kirim laporan kemajuan? Setelah dikirim, laporan tidak bisa diubah.')">Kirim laporan kemajuan</x-primary-button></div>
                    </form>
                @else
                    <p class="text-sm text-gray-600">Laporan kemajuan belum dikirim{{ $periode->tgl_laporan_kemajuan ? '. Batas '.$periode->tgl_laporan_kemajuan->translatedFormat('j F Y') : '' }}.</p>
                @endif
            </x-kartu>

            <x-kartu judul="Logbook kegiatan">
                @if ($bisaMencatat)
                    <form method="POST" action="{{ route('proposal.pelaksanaan.logbook.store', $proposal) }}" enctype="multipart/form-data" class="mb-6 grid gap-4 sm:grid-cols-4">
                        @csrf
                        <div><x-input-label for="tanggal" value="Tanggal" /><x-text-input id="tanggal" name="tanggal" type="date" class="mt-1 block w-full" :value="old('tanggal', today()->toDateString())" :max="today()->toDateString()" required /></div>
                        <div><x-input-label for="persentase_progres" value="Progres (%, opsional)" /><x-text-input id="persentase_progres" name="persentase_progres" type="number" min="0" max="100" class="mt-1 block w-full" :value="old('persentase_progres')" /></div>
                        <div class="sm:col-span-2"><x-input-label for="bukti" value="Bukti (PDF/JPG/PNG, opsional)" /><input id="bukti" name="bukti" type="file" accept="application/pdf,image/jpeg,image/png" class="mt-1 block w-full text-sm"></div>
                        <div class="sm:col-span-4">
                            <x-input-label for="kegiatan" value="Kegiatan" />
                            <textarea id="kegiatan" name="kegiatan" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>{{ old('kegiatan') }}</textarea>
                        </div>
                        <div class="sm:col-span-4"><x-primary-button>Simpan entri</x-primary-button></div>
                    </form>
                @endif

                @if ($proposal->logbook->isEmpty())
                    <p class="text-sm text-gray-600">Belum ada entri logbook.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Tanggal</th><th class="py-2 pe-4 font-medium">Kegiatan</th><th class="py-2 pe-4 font-medium text-end">Progres</th><th class="py-2 pe-4 font-medium">Dicatat</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($proposal->logbook->sortByDesc('tanggal') as $entri)
                                    <tr class="border-b border-gray-100 last:border-0 align-top">
                                        <td class="py-2 pe-4 whitespace-nowrap">{{ $entri->tanggal->translatedFormat('j M Y') }}</td>
                                        <td class="py-2 pe-4">
                                            <p class="whitespace-pre-line">{{ $entri->kegiatan }}</p>
                                            @foreach ($entri->berkas as $berkas)
                                                <a class="text-xs text-indigo-700 underline" href="{{ route('proposal.pelaksanaan.berkas', [$proposal, $berkas]) }}">{{ $berkas->nama_asli }}</a>
                                            @endforeach
                                        </td>
                                        <td class="py-2 pe-4 text-end">{{ $entri->persentase_progres !== null ? $entri->persentase_progres.'%' : '-' }}</td>
                                        <td class="py-2 pe-4">{{ $entri->pencatat?->namaLengkap() ?? '-' }}</td>
                                        <td class="py-2 text-end">
                                            @if ($bisaMencatat && $entri->dicatat_oleh === auth()->user()->dosen?->id)
                                                <form method="POST" action="{{ route('proposal.pelaksanaan.logbook.destroy', [$proposal, $entri]) }}" onsubmit="return confirm('Hapus entri ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-red-700 hover:underline">Hapus</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-kartu>
        </div>
    </div>
</x-app-layout>
