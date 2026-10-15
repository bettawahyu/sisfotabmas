@php
    use App\Enums\StatusProposal;
    use App\Models\Berkas;
    use App\Models\Laporan;
    use App\Models\Luaran;

    $periode = $proposal->periode;
    $bisaLaporLuaran = $isKetua && in_array($proposal->status, [StatusProposal::Berjalan, StatusProposal::Dimonev], true);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pelaksanaan: {{ $proposal->judul }}</h2>
            <x-status-proposal :proposal="$proposal" />
        </div>
        <p class="mt-1 text-sm text-gray-600">
            @if (Auth::user()->hasRole(\App\Models\Role::DOSEN))
                <a href="{{ route('pelaksanaan.index') }}" class="text-indigo-700 hover:underline">Kembali ke daftar pelaksanaan</a> ·
            @endif
            <a href="{{ route('proposal.show', $proposal) }}" class="text-indigo-700 hover:underline">Lihat proposal</a> · {{ $periode->nama }}
        </p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">
            <x-pesan />

            @if ($templat->isNotEmpty())
                <x-kartu ikon="file" judul="Templat">
                    <ul class="space-y-1 text-sm">
                        @foreach ($templat as $t)
                            <li><a class="text-indigo-700 underline" href="{{ route('templat.unduh', $t) }}">{{ $t->nama }}</a> <span class="text-gray-500">· {{ $t->namaJenis() }}</span></li>
                        @endforeach
                    </ul>
                </x-kartu>
            @endif

            <x-kartu ikon="trending-up" judul="Laporan kemajuan">
                @if ($laporanKemajuan)
                    <dl class="grid gap-3 text-sm sm:grid-cols-3">
                        <div><dt class="text-gray-500">Dikirim</dt><dd>{{ $laporanKemajuan->diajukan_at->translatedFormat('j F Y') }}</dd></div>
                        <div><dt class="text-gray-500">Capaian kegiatan</dt><dd>{{ $laporanKemajuan->persentase_capaian }}%</dd></div>
                        <div><dt class="text-gray-500">Anggaran terpakai</dt><dd>{{ $laporanKemajuan->persentase_anggaran_terpakai }}%</dd></div>
                        <div class="sm:col-span-3"><dt class="text-gray-500">Ringkasan</dt><dd class="whitespace-pre-line">{{ $laporanKemajuan->ringkasan }}</dd></div>
                    </dl>
                    <ul class="mt-4 space-y-1 text-sm">
                        @foreach ($laporanKemajuan->berkas as $berkas)
                            <li><a class="text-indigo-700 underline" href="{{ route('pelaksanaan.berkas', [$proposal, $berkas]) }}">{{ $berkas->kategori === Berkas::LAPORAN_KEUANGAN ? 'Laporan keuangan' : 'Laporan kemajuan' }}</a> <span class="text-gray-500">· {{ $berkas->nama_asli }}</span></li>
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
                    <form method="POST" action="{{ route('pelaksanaan.laporan-kemajuan.store', $proposal) }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div class="sm:col-span-2">
                            <x-input-label for="ringkasan" value="Ringkasan kemajuan" ikon="align-left" />
                            <textarea id="ringkasan" name="ringkasan" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>{{ old('ringkasan') }}</textarea>
                        </div>
                        <div><x-input-label for="persentase_capaian" value="Capaian kegiatan (%)" ikon="percent" /><x-text-input id="persentase_capaian" name="persentase_capaian" type="number" min="0" max="100" class="mt-1 block w-full" :value="old('persentase_capaian')" required /></div>
                        <div><x-input-label for="persentase_anggaran_terpakai" value="Anggaran terpakai (%)" ikon="dollar-sign" /><x-text-input id="persentase_anggaran_terpakai" name="persentase_anggaran_terpakai" type="number" min="0" max="100" class="mt-1 block w-full" :value="old('persentase_anggaran_terpakai')" required /></div>
                        <div><x-input-label for="laporan" value="Laporan kemajuan (PDF)" ikon="paperclip" /><input id="laporan" name="laporan" type="file" accept="application/pdf" class="mt-1 block w-full text-sm" required></div>
                        <div><x-input-label for="keuangan" value="Laporan keuangan termin 1 (PDF)" ikon="paperclip" /><input id="keuangan" name="keuangan" type="file" accept="application/pdf" class="mt-1 block w-full text-sm" required></div>
                        <div class="sm:col-span-2"><x-primary-button onclick="return confirm('Kirim laporan kemajuan? Setelah dikirim, laporan tidak bisa diubah.')" ikon="send">Kirim laporan kemajuan</x-primary-button></div>
                    </form>
                @else
                    <p class="text-sm text-gray-600">Laporan kemajuan belum dikirim{{ $periode->tgl_laporan_kemajuan ? '. Batas '.$periode->tgl_laporan_kemajuan->translatedFormat('j F Y') : '' }}.</p>
                @endif
            </x-kartu>

            @if ($laporanKemajuan)
                <x-kartu ikon="flag" judul="Laporan akhir">
                    @if ($laporanAkhir && $laporanAkhir->status !== Laporan::DIKEMBALIKAN)
                        <dl class="grid gap-3 text-sm sm:grid-cols-3">
                            <div><dt class="text-gray-500">Dikirim</dt><dd>{{ $laporanAkhir->diajukan_at->translatedFormat('j F Y') }}</dd></div>
                            <div><dt class="text-gray-500">Capaian kegiatan</dt><dd>{{ $laporanAkhir->persentase_capaian }}%</dd></div>
                            <div><dt class="text-gray-500">Status</dt><dd class="font-medium">{{ $laporanAkhir->status === Laporan::DIVERIFIKASI ? 'Diverifikasi '.$laporanAkhir->diverifikasi_at->translatedFormat('j M Y') : 'Menunggu verifikasi operator' }}</dd></div>
                            <div class="sm:col-span-3"><dt class="text-gray-500">Ringkasan</dt><dd class="whitespace-pre-line">{{ $laporanAkhir->ringkasan }}</dd></div>
                        </dl>
                        <ul class="mt-4 space-y-1 text-sm">
                            @foreach ($laporanAkhir->berkas as $berkas)
                                <li><a class="text-indigo-700 underline" href="{{ route('pelaksanaan.berkas', [$proposal, $berkas]) }}">{{ $berkas->kategori === Berkas::LAPORAN_KEUANGAN ? 'Laporan keuangan 100%' : 'Laporan akhir' }}</a> <span class="text-gray-500">· {{ $berkas->nama_asli }}</span></li>
                            @endforeach
                        </ul>
                    @else
                        @if ($laporanAkhir?->status === Laporan::DIKEMBALIKAN)
                            <div class="mb-4 rounded-md bg-yellow-50 p-3 text-sm text-yellow-800">Laporan akhir dikembalikan {{ $laporanAkhir->diverifikasi_at->translatedFormat('j M Y') }}. Catatan: {{ $laporanAkhir->catatan_verifikasi }}</div>
                        @endif
                        @if ($bisaKirimAkhir)
                            <p class="mb-4 text-sm text-gray-600">Unggah laporan akhir dan laporan penggunaan dana 100%{{ $periode->tgl_laporan_akhir ? ' paling lambat '.$periode->tgl_laporan_akhir->translatedFormat('j F Y') : '' }}. Operator memverifikasinya sebelum termin 2 dicairkan.</p>
                            <form method="POST" action="{{ route('pelaksanaan.laporan-akhir.store', $proposal) }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
                                @csrf
                                <div class="sm:col-span-2">
                                    <x-input-label for="ringkasan_akhir" value="Ringkasan hasil" ikon="align-left" />
                                    <textarea id="ringkasan_akhir" name="ringkasan" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>{{ old('ringkasan') }}</textarea>
                                </div>
                                <div><x-input-label for="capaian_akhir" value="Capaian kegiatan (%)" ikon="percent" /><x-text-input id="capaian_akhir" name="persentase_capaian" type="number" min="0" max="100" class="mt-1 block w-full" :value="old('persentase_capaian', 100)" required /></div>
                                <div></div>
                                <div><x-input-label for="laporan_akhir" value="Laporan akhir (PDF)" ikon="paperclip" /><input id="laporan_akhir" name="laporan" type="file" accept="application/pdf" class="mt-1 block w-full text-sm" required></div>
                                <div><x-input-label for="keuangan_akhir" value="Laporan keuangan 100% (PDF)" ikon="paperclip" /><input id="keuangan_akhir" name="keuangan" type="file" accept="application/pdf" class="mt-1 block w-full text-sm" required></div>
                                <div class="sm:col-span-2"><x-primary-button ikon="send">Kirim laporan akhir</x-primary-button></div>
                            </form>
                        @else
                            <p class="text-sm text-gray-600">{{ $monev ? 'Laporan akhir belum dikirim.' : 'Laporan akhir dikirim setelah hasil monev keluar.' }}{{ $periode->tgl_laporan_akhir ? ' Batas '.$periode->tgl_laporan_akhir->translatedFormat('j F Y').'.' : '' }}</p>
                        @endif
                    @endif
                </x-kartu>
            @endif

            <x-kartu ikon="target" judul="Capaian luaran">
                <p class="mb-4 text-sm text-gray-600">Termin 2 dicairkan setelah setiap luaran wajib divalidasi operator minimal berstatus sudah dikirim (submitted).</p>
                <div class="space-y-4">
                    @foreach ($proposal->luaran as $luaran)
                        <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0 text-sm">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <p><span class="font-medium">{{ $luaran->namaJenis() }}</span> <span class="text-gray-500">· {{ $luaran->kategori }}{{ $luaran->target_keterangan ? ' · '.$luaran->target_keterangan : '' }}</span></p>
                                <p class="{{ $luaran->status_validasi === Luaran::VALID ? 'text-green-700' : ($luaran->status_validasi === Luaran::DITOLAK ? 'text-red-700' : 'text-gray-600') }}">
                                    {{ $luaran->namaCapaian() }}@if ($luaran->status_capaian !== Luaran::DIRENCANAKAN) · {{ Luaran::VALIDASI[$luaran->status_validasi] ?? $luaran->status_validasi }}@endif
                                </p>
                            </div>
                            @if ($luaran->karya())
                                <x-karya-tertaut :karya="$luaran->karya()" />
                            @endif
                            @if ($luaran->url_bukti)
                                <a href="{{ $luaran->url_bukti }}" class="text-indigo-700 underline break-all" rel="noopener noreferrer" target="_blank">{{ $luaran->url_bukti }}</a>
                            @endif
                            @if ($luaran->status_validasi === Luaran::DITOLAK && $luaran->catatan_validasi)
                                <p class="mt-1 text-red-700">Catatan operator: {{ $luaran->catatan_validasi }}</p>
                            @endif
                            @if ($bisaLaporLuaran)
                                <form method="POST" action="{{ route('pelaksanaan.luaran.update', [$proposal, $luaran]) }}" class="mt-2 flex flex-wrap gap-3">
                                    @csrf
                                    @method('PUT')
                                    <x-select-input name="status_capaian">
                                        @foreach (Luaran::CAPAIAN as $kode => $nama)
                                            <option value="{{ $kode }}" @selected($luaran->status_capaian === $kode)>{{ $nama }}</option>
                                        @endforeach
                                    </x-select-input>
                                    <x-text-input name="url_bukti" type="url" placeholder="Tautan bukti (DOI, URL artikel, nomor permohonan)" class="flex-1" :value="$luaran->url_bukti" />
                                    @if ($model = $luaran->modelKarya())
                                        <x-select-input name="karya_id" class="w-full" aria-label="Tautkan ke data publikasi, HKI, atau paten">
                                            <option value="">Tidak ditautkan</option>
                                            @foreach ($karyaKetua[$model] as $karya)
                                                <option value="{{ $karya->id }}" @selected($luaran->karya()?->is($karya))>{{ Str::limit($karya->judul, 90) }} ({{ $karya->namaStatus() }})</option>
                                            @endforeach
                                        </x-select-input>
                                        <p class="w-full -mt-2 text-xs text-gray-500">Belum ada di daftar? Tambahkan dulu di <a href="{{ route('karya.'.\App\Services\AlurKarya::slug(new $model).'.create') }}" class="text-indigo-700 hover:underline">halaman publikasi, HKI, dan paten</a>.</p>
                                    @endif
                                    <x-secondary-button type="submit" ikon="save">Simpan</x-secondary-button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-kartu>

            <x-kartu ikon="book" judul="Logbook kegiatan">
                @if ($bisaMencatat)
                    <form method="POST" action="{{ route('pelaksanaan.logbook.store', $proposal) }}" enctype="multipart/form-data" class="mb-6 grid gap-4 sm:grid-cols-4">
                        @csrf
                        <div><x-input-label for="tanggal" value="Tanggal" ikon="calendar" /><x-text-input id="tanggal" name="tanggal" type="date" class="mt-1 block w-full" :value="old('tanggal', today()->toDateString())" :max="today()->toDateString()" required /></div>
                        <div><x-input-label for="persentase_progres" value="Progres (%, opsional)" ikon="percent" /><x-text-input id="persentase_progres" name="persentase_progres" type="number" min="0" max="100" class="mt-1 block w-full" :value="old('persentase_progres')" /></div>
                        <div class="sm:col-span-2"><x-input-label for="bukti" value="Bukti (PDF/JPG/PNG, opsional)" ikon="paperclip" /><input id="bukti" name="bukti" type="file" accept="application/pdf,image/jpeg,image/png" class="mt-1 block w-full text-sm"></div>
                        <div class="sm:col-span-4">
                            <x-input-label for="kegiatan" value="Kegiatan" ikon="activity" />
                            <textarea id="kegiatan" name="kegiatan" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>{{ old('kegiatan') }}</textarea>
                        </div>
                        <div class="sm:col-span-4"><x-primary-button ikon="save">Simpan entri</x-primary-button></div>
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
                                                <a class="text-xs text-indigo-700 underline" href="{{ route('pelaksanaan.berkas', [$proposal, $berkas]) }}">{{ $berkas->nama_asli }}</a>
                                            @endforeach
                                        </td>
                                        <td class="py-2 pe-4 text-end">{{ $entri->persentase_progres !== null ? $entri->persentase_progres.'%' : '-' }}</td>
                                        <td class="py-2 pe-4">{{ $entri->pencatat?->namaLengkap() ?? '-' }}</td>
                                        <td class="py-2 text-end">
                                            @if ($bisaMencatat && $entri->dicatat_oleh === auth()->user()->dosen?->id)
                                                <form method="POST" action="{{ route('pelaksanaan.logbook.destroy', [$proposal, $entri]) }}" onsubmit="return confirm('Hapus entri ini?')">
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
