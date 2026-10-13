@php
    use App\Enums\JenisKegiatan;
    use App\Enums\KategoriRab;
    use App\Models\Penilaian;

    $terkunci = $penilaian->terkirim();
    $skor = $penilaian->nilai->pluck('skor', 'kriteria_id');
    $monev = $penilaian->tahap === Penilaian::MONEV;
    $laporan = $penilaian->laporan;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $proposal->judul }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ $proposal->periode->nama }} · {{ $proposal->periode->skema->jenis_kegiatan->label() }} · {{ $monev ? 'Monev' : 'Penilaian' }} tanpa identitas tim</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-pesan />

            <x-kartu judul="Proposal">
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div class="sm:col-span-2"><dt class="text-gray-500">Ringkasan</dt><dd class="whitespace-pre-line">{{ $proposal->ringkasan }}</dd></div>
                    <div><dt class="text-gray-500">Rumpun ilmu</dt><dd>{{ $proposal->cabangIlmu?->jalur() ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500">Bidang fokus</dt><dd>{{ $proposal->bidangFokus?->nama ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500">Kata kunci</dt><dd>{{ $proposal->kata_kunci ?: '-' }}</dd></div>
                    <div><dt class="text-gray-500">TKT</dt><dd>{{ $proposal->tkt_awal ?? '-' }} ke {{ $proposal->tkt_target ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500">Lama kegiatan</dt><dd>{{ $proposal->lama_tahun }} tahun</dd></div>
                    <div><dt class="text-gray-500">Dana diusulkan</dt><dd><x-rupiah :nilai="$proposal->dana_diusulkan" /></dd></div>
                    <div class="sm:col-span-2"><dt class="text-gray-500">Substansi</dt><dd>
                        @if ($proposal->berkasSubstansi)
                            <a class="text-indigo-700 underline" href="{{ route('review.substansi', $penilaian) }}">Unduh substansi proposal (PDF)</a>
                        @else
                            -
                        @endif
                    </dd></div>
                </dl>
            </x-kartu>

            @if ($monev)
                <x-kartu judul="Laporan kemajuan">
                    @if ($laporan)
                        <dl class="grid gap-3 text-sm sm:grid-cols-3">
                            <div><dt class="text-gray-500">Dikirim</dt><dd>{{ $laporan->diajukan_at->translatedFormat('j F Y') }}</dd></div>
                            <div><dt class="text-gray-500">Capaian kegiatan</dt><dd>{{ $laporan->persentase_capaian }}%</dd></div>
                            <div><dt class="text-gray-500">Anggaran terpakai</dt><dd>{{ $laporan->persentase_anggaran_terpakai }}%</dd></div>
                            <div class="sm:col-span-3"><dt class="text-gray-500">Ringkasan</dt><dd class="whitespace-pre-line">{{ $laporan->ringkasan }}</dd></div>
                        </dl>
                        <ul class="mt-4 space-y-1 text-sm">
                            @foreach ($laporan->berkas as $berkas)
                                <li><a class="text-indigo-700 underline" href="{{ route('review.laporan', [$penilaian, $berkas]) }}">Unduh {{ $berkas->kategori === \App\Models\Berkas::LAPORAN_KEUANGAN ? 'laporan keuangan' : 'laporan kemajuan' }}</a></li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-600">Laporan kemajuan tidak ditemukan.</p>
                    @endif
                </x-kartu>

                <x-kartu judul="Logbook kegiatan">
                    @if ($proposal->logbook->isEmpty())
                        <p class="text-sm text-gray-600">Belum ada entri logbook.</p>
                    @else
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Tanggal</th><th class="py-2 pe-4 font-medium">Kegiatan</th><th class="py-2 font-medium text-end">Progres</th></tr></thead>
                            <tbody>
                                @foreach ($proposal->logbook as $entri)
                                    <tr class="border-b border-gray-100 last:border-0 align-top"><td class="py-2 pe-4 whitespace-nowrap">{{ $entri->tanggal->translatedFormat('j M Y') }}</td><td class="py-2 pe-4 whitespace-pre-line">{{ $entri->kegiatan }}</td><td class="py-2 text-end">{{ $entri->persentase_progres !== null ? $entri->persentase_progres.'%' : '-' }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </x-kartu>
            @endif

            <x-kartu judul="Rencana anggaran">
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Kategori</th><th class="py-2 pe-4 font-medium">Uraian</th><th class="py-2 pe-4 font-medium text-end">Volume</th><th class="py-2 font-medium text-end">Total</th></tr></thead>
                    <tbody>
                        @foreach ($proposal->rab as $item)
                            <tr class="border-b border-gray-100"><td class="py-2 pe-4">{{ $item->kategori->label() }}</td><td class="py-2 pe-4">{{ $item->uraian }}</td><td class="py-2 pe-4 text-end">{{ rtrim(rtrim(number_format($item->volume, 2, ',', '.'), '0'), ',') }} {{ $item->satuan }}</td><td class="py-2 text-end"><x-rupiah :nilai="$item->total" /></td></tr>
                        @endforeach
                        <tr><td colspan="3" class="py-2 pe-4 font-medium">Total</td><td class="py-2 text-end font-medium"><x-rupiah :nilai="$proposal->totalRab()" /></td></tr>
                    </tbody>
                </table>
            </x-kartu>

            <x-kartu judul="Target luaran">
                <ul class="space-y-1 text-sm">
                    @foreach ($proposal->luaran as $luaran)
                        <li><span class="font-medium">{{ $luaran->jenis->label() }}</span> <span class="text-gray-500">· {{ $luaran->kategori }}{{ $luaran->target_keterangan ? ' · '.$luaran->target_keterangan : '' }}</span></li>
                    @endforeach
                </ul>
                @if ($proposal->periode->skema->jenis_kegiatan === JenisKegiatan::Pkm && $proposal->mitra->isNotEmpty())
                    <h4 class="mt-4 font-medium text-gray-900">Mitra</h4>
                    <ul class="mt-1 space-y-1 text-sm">
                        @foreach ($proposal->mitra as $mitra)
                            <li>{{ $mitra->nama_mitra }} <span class="text-gray-500">· {{ $mitra->jenis_mitra }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </x-kartu>

            <x-kartu :judul="$monev ? 'Hasil monev' : 'Penilaian'">
                @if ($terkunci)
                    <p class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-800">Dikirim {{ $penilaian->dinilai_at->translatedFormat('d M Y H:i') }} dengan nilai {{ number_format($penilaian->nilai_total, 2, ',', '.') }}. Penilaian terkunci; hubungi LPPM jika perlu diubah.</p>
                @endif
                @if ($kriteria->isEmpty())
                    <p class="text-sm text-gray-600">LPPM belum mengisi kriteria penilaian untuk periode ini.</p>
                @else
                    <form method="POST" action="{{ route('review.update', $penilaian) }}" class="space-y-6">
                        @csrf
                        @method('PUT')
                        <fieldset @disabled($terkunci) class="space-y-6">
                            <table class="min-w-full text-sm">
                                <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Kriteria</th><th class="py-2 pe-4 font-medium text-end">Bobot</th><th class="py-2 font-medium">Skor</th></tr></thead>
                                <tbody>
                                    @foreach ($kriteria as $k)
                                        <tr class="border-b border-gray-100 last:border-0">
                                            <td class="py-2 pe-4">{{ $k->nama }}</td>
                                            <td class="py-2 pe-4 text-end">{{ $k->bobot }}</td>
                                            <td class="py-2">
                                                <x-select-input name="skor[{{ $k->id }}]" class="w-28">
                                                    <option value="">-</option>
                                                    @for ($i = 1; $i <= $k->skala_maks; $i++)
                                                        <option value="{{ $i }}" @selected((string) old('skor.'.$k->id, $skor->get($k->id)) === (string) $i)>{{ $i }}</option>
                                                    @endfor
                                                </x-select-input>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="rekomendasi" :value="$monev ? 'Hasil monev' : 'Rekomendasi'" />
                                    <x-select-input id="rekomendasi" name="rekomendasi" class="mt-1 block w-full">
                                        <option value="">{{ $monev ? 'Pilih hasil monev' : 'Pilih rekomendasi' }}</option>
                                        @foreach ($monev ? Penilaian::HASIL_MONEV : Penilaian::REKOMENDASI as $kode => $nama)
                                            <option value="{{ $kode }}" @selected(old('rekomendasi', $penilaian->rekomendasi) === $kode)>{{ $nama }}</option>
                                        @endforeach
                                    </x-select-input>
                                </div>
                                @unless ($monev)
                                    <div>
                                        <x-input-label for="rekomendasi_dana" value="Rekomendasi dana (Rp, opsional)" />
                                        <x-text-input id="rekomendasi_dana" name="rekomendasi_dana" type="number" min="0" class="mt-1 block w-full" :value="old('rekomendasi_dana', $penilaian->rekomendasi_dana)" />
                                    </div>
                                @endunless
                            </div>
                            <div>
                                <x-input-label for="komentar_untuk_pengusul" value="Komentar untuk pengusul" />
                                <textarea id="komentar_untuk_pengusul" name="komentar_untuk_pengusul" rows="5" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('komentar_untuk_pengusul', $penilaian->komentar_untuk_pengusul) }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Dikirim ke ketua {{ $monev ? 'bersama hasil monev' : 'bersama hasil seleksi' }}, tanpa nama Anda.</p>
                            </div>
                            <div>
                                <x-input-label for="catatan_internal" value="Catatan internal untuk LPPM (opsional)" />
                                <textarea id="catatan_internal" name="catatan_internal" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('catatan_internal', $penilaian->catatan_internal) }}</textarea>
                                <p class="mt-1 text-xs text-gray-500">Hanya dibaca LPPM.</p>
                            </div>
                            @unless ($terkunci)
                                <div class="flex flex-wrap gap-3">
                                    <x-secondary-button type="submit" name="aksi" value="simpan">Simpan draf</x-secondary-button>
                                    <x-primary-button name="aksi" value="kirim" onclick="return confirm('Kirim {{ $monev ? 'hasil monev' : 'penilaian' }}? Setelah dikirim, isian terkunci.')">{{ $monev ? 'Kirim hasil monev' : 'Kirim penilaian' }}</x-primary-button>
                                </div>
                            @endunless
                        </fieldset>
                    </form>
                @endif
            </x-kartu>
        </div>
    </div>
</x-app-layout>
