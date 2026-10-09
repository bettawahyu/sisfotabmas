@php
    use App\Enums\JenisKegiatan;
    use App\Enums\JenisLuaran;
    use App\Enums\KategoriRab;
    use App\Enums\StatusProposal;
    use App\Enums\TipeAnggota;
    use App\Models\Role;

    $periode = $proposal->periode;
    $isPkm = $periode->skema->jenis_kegiatan === JenisKegiatan::Pkm;
    $user = auth()->user();
    $kategoriRab = collect(KategoriRab::cases())->reject(fn ($k) => $k === KategoriRab::Honorarium && ! $periode->honor_diizinkan);
    $catatanTerakhir = $proposal->riwayatStatus->first();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $proposal->judul }}</h2>
            <x-status-proposal :proposal="$proposal" />
        </div>
        <p class="mt-1 text-sm text-gray-600">{{ $periode->nama }} · {{ $periode->skema->jenis_kegiatan->label() }} · Ketua {{ $proposal->ketua->namaLengkap() }}</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-pesan />

            @if ($proposal->status === StatusProposal::Dikembalikan)
                <div class="rounded-md bg-yellow-50 p-4 text-sm text-yellow-800">
                    Dikembalikan oleh LPPM untuk diperbaiki paling lambat {{ $proposal->batas_perbaikan_at?->translatedFormat('d M Y H:i') }}.
                    @if ($catatanTerakhir?->catatan)
                        <br>Catatan: {{ $catatanTerakhir->catatan }}
                    @endif
                </div>
            @endif

            @if ($bisaDiubah)
                <x-kartu judul="Kelengkapan sebelum dikirim">
                    @if ($kekurangan === [])
                        <p class="text-sm text-green-700">Semua syarat terpenuhi.</p>
                    @else
                        <ul class="list-disc ps-5 text-sm text-gray-700 space-y-1">
                            @foreach ($kekurangan as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('proposal.kirim', $proposal) }}">
                            @csrf
                            <x-primary-button :disabled="$kekurangan !== []" class="disabled:opacity-50">
                                {{ $proposal->status === StatusProposal::Dikembalikan ? 'Kirim perbaikan ke LPPM' : 'Kirim untuk pengesahan' }}
                            </x-primary-button>
                        </form>
                        @can('delete', $proposal)
                            <form method="POST" action="{{ route('proposal.destroy', $proposal) }}" onsubmit="return confirm('Hapus draf ini?')">
                                @csrf
                                @method('DELETE')
                                <x-danger-button>Hapus draf</x-danger-button>
                            </form>
                        @endcan
                    </div>
                </x-kartu>
            @endif

            @if ($proposal->menungguPengesahan() && $user->hasRole(Role::PIMPINAN_UNIT))
                <x-kartu judul="Pengesahan">
                    <div class="flex flex-wrap items-start gap-6">
                        <form method="POST" action="{{ route('pengesahan.sahkan', $proposal) }}">
                            @csrf
                            <x-primary-button>Sahkan</x-primary-button>
                        </form>
                        <form method="POST" action="{{ route('pengesahan.tolak', $proposal) }}" class="flex flex-1 flex-wrap gap-3">
                            @csrf
                            <x-text-input name="catatan" placeholder="Alasan dikembalikan ke ketua" class="flex-1" required />
                            <x-danger-button>Kembalikan ke ketua</x-danger-button>
                        </form>
                    </div>
                </x-kartu>
            @endif

            @if ($proposal->status === StatusProposal::Diajukan && $user->hasRole(Role::ADMIN_LPPM))
                <x-kartu judul="Seleksi administrasi">
                    <div class="space-y-4">
                        <form method="POST" action="{{ route('admin.seleksi.lolos', $proposal) }}" class="flex flex-wrap gap-3">
                            @csrf
                            <x-text-input name="catatan" placeholder="Catatan (opsional)" class="flex-1" />
                            <x-primary-button>Lolos administrasi</x-primary-button>
                        </form>
                        <form method="POST" action="{{ route('admin.seleksi.kembalikan', $proposal) }}" class="flex flex-wrap gap-3">
                            @csrf
                            <x-text-input name="catatan" placeholder="Yang harus diperbaiki" class="flex-1" required />
                            <x-text-input name="batas_perbaikan" type="date" required />
                            <x-secondary-button type="submit">Kembalikan</x-secondary-button>
                        </form>
                        <form method="POST" action="{{ route('admin.seleksi.tolak', $proposal) }}" class="flex flex-wrap gap-3">
                            @csrf
                            <x-text-input name="catatan" placeholder="Alasan ditolak" class="flex-1" required />
                            <x-danger-button>Tolak</x-danger-button>
                        </form>
                    </div>
                </x-kartu>
            @endif

            <x-kartu judul="Identitas">
                @if ($bisaDiubah)
                    <form method="POST" action="{{ route('proposal.update', $proposal) }}" class="space-y-6">
                        @csrf
                        @method('PUT')
                        @include('proposal.partials.identitas-form', ['periode' => $periode])
                        <x-primary-button>Simpan identitas</x-primary-button>
                    </form>
                @else
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div class="sm:col-span-2"><dt class="text-gray-500">Ringkasan</dt><dd class="whitespace-pre-line">{{ $proposal->ringkasan }}</dd></div>
                        <div><dt class="text-gray-500">Bidang fokus</dt><dd>{{ $proposal->bidangFokus?->nama ?? '-' }}</dd></div>
                        <div><dt class="text-gray-500">Kata kunci</dt><dd>{{ $proposal->kata_kunci ?: '-' }}</dd></div>
                        <div><dt class="text-gray-500">TKT</dt><dd>{{ $proposal->tkt_awal ?? '-' }} ke {{ $proposal->tkt_target ?? '-' }}</dd></div>
                        <div><dt class="text-gray-500">Lama kegiatan</dt><dd>{{ $proposal->lama_tahun }} tahun</dd></div>
                        <div><dt class="text-gray-500">Dana diusulkan</dt><dd><x-rupiah :nilai="$proposal->dana_diusulkan" /></dd></div>
                    </dl>
                @endif
            </x-kartu>

            <x-kartu judul="Substansi proposal (PDF)">
                @if ($proposal->berkasSubstansi)
                    <p class="text-sm"><a class="text-indigo-700 underline" href="{{ route('proposal.substansi.show', $proposal) }}">{{ $proposal->berkasSubstansi->nama_asli }}</a>
                        <span class="text-gray-500">· diunggah {{ $proposal->berkasSubstansi->created_at->translatedFormat('d M Y H:i') }}</span></p>
                @else
                    <p class="text-sm text-gray-600">Belum diunggah.</p>
                @endif
                @if ($bisaDiubah)
                    <form method="POST" action="{{ route('proposal.substansi.store', $proposal) }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-center gap-3">
                        @csrf
                        <input type="file" name="berkas" accept="application/pdf" required class="text-sm">
                        <x-secondary-button type="submit">Unggah</x-secondary-button>
                    </form>
                    <p class="mt-2 text-xs text-gray-500">Gunakan templat dari LPPM. PDF, maksimal 10 MB.</p>
                @endif
            </x-kartu>

            <x-kartu judul="Tim">
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Nama</th><th class="py-2 pe-4 font-medium">Jenis</th><th class="py-2 pe-4 font-medium">Peran</th><th class="py-2 pe-4 font-medium">Konfirmasi</th><th></th></tr></thead>
                    <tbody>
                        <tr class="border-b border-gray-100"><td class="py-2 pe-4">{{ $proposal->ketua->namaLengkap() }}</td><td class="py-2 pe-4">Dosen</td><td class="py-2 pe-4">Ketua</td><td class="py-2 pe-4">-</td><td></td></tr>
                        @foreach ($proposal->anggota as $anggota)
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="py-2 pe-4">{{ $anggota->nama }}@if ($anggota->nim) <span class="text-gray-500">({{ $anggota->nim }})</span>@endif</td>
                                <td class="py-2 pe-4">{{ $anggota->tipe->label() }}</td>
                                <td class="py-2 pe-4">{{ $anggota->peran ?: 'Anggota' }}</td>
                                <td class="py-2 pe-4">{{ $anggota->statusKonfirmasi() }}</td>
                                <td class="py-2 text-end">
                                    @if ($bisaDiubah)
                                        <form method="POST" action="{{ route('proposal.anggota.destroy', [$proposal, $anggota]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-600 hover:underline">Hapus</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($bisaDiubah)
                    <form method="POST" action="{{ route('proposal.anggota.store', $proposal) }}" class="mt-6 grid gap-3 sm:grid-cols-3" x-data="{ tipe: '{{ old('tipe', 'dosen') }}' }">
                        @csrf
                        <x-select-input name="tipe" x-model="tipe">
                            @foreach (TipeAnggota::cases() as $tipe)
                                <option value="{{ $tipe->value }}">{{ $tipe->label() }}</option>
                            @endforeach
                        </x-select-input>
                        <x-text-input name="nidn" placeholder="NIDN / NUPTK dosen" x-show="tipe === 'dosen'" :value="old('nidn')" />
                        <x-text-input name="nama" placeholder="Nama" x-show="tipe !== 'dosen'" :value="old('nama')" />
                        <x-text-input name="nim" placeholder="NIM" x-show="tipe === 'mahasiswa'" :value="old('nim')" />
                        <x-text-input name="institusi" placeholder="Institusi" x-show="tipe === 'eksternal'" :value="old('institusi')" />
                        <x-text-input name="peran" placeholder="Peran" :value="old('peran')" />
                        <x-text-input name="uraian_tugas" placeholder="Uraian tugas" class="sm:col-span-2" :value="old('uraian_tugas')" />
                        <div><x-secondary-button type="submit">Tambah anggota</x-secondary-button></div>
                    </form>
                    <p class="mt-2 text-xs text-gray-500">Anggota dosen menerima undangan di halaman Proposal Saya dan harus menyatakan bersedia sebelum proposal dikirim.</p>
                @endif
            </x-kartu>

            @if ($isPkm)
                <x-kartu judul="Mitra">
                    @forelse ($proposal->mitra as $mitra)
                        <div class="flex items-center justify-between border-b border-gray-100 py-2 text-sm last:border-0">
                            <div><span class="font-medium">{{ $mitra->nama_mitra }}</span> <span class="text-gray-500">· {{ $mitra->jenis_mitra }} · {{ $mitra->alamat }}</span></div>
                            @if ($bisaDiubah)
                                <form method="POST" action="{{ route('proposal.mitra.destroy', [$proposal, $mitra]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600 hover:underline">Hapus</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-600">Belum ada mitra.</p>
                    @endforelse
                    @if ($bisaDiubah)
                        <form method="POST" action="{{ route('proposal.mitra.store', $proposal) }}" class="mt-6 grid gap-3 sm:grid-cols-3">
                            @csrf
                            <x-text-input name="nama_mitra" placeholder="Nama mitra" required />
                            <x-select-input name="jenis_mitra" required>
                                @foreach (['Kelompok masyarakat', 'UMKM', 'Sekolah', 'Pemerintah daerah', 'Industri'] as $jenis)
                                    <option>{{ $jenis }}</option>
                                @endforeach
                            </x-select-input>
                            <x-text-input name="alamat" placeholder="Alamat" />
                            <x-text-input name="kontak" placeholder="Kontak" />
                            <x-text-input name="dana_pendamping" type="number" min="0" placeholder="Dana pendamping (Rp)" />
                            <div><x-secondary-button type="submit">Tambah mitra</x-secondary-button></div>
                        </form>
                    @endif
                </x-kartu>
            @endif

            <x-kartu judul="Rencana anggaran belanja (RAB)">
                <p class="mb-4 text-sm text-gray-600">Plafon <x-rupiah :nilai="$periode->dana_maksimal" />.
                    @if ($periode->honor_diizinkan)
                        Honorarium diizinkan{{ $periode->batas_honor_persen ? ', maksimal '.$periode->batas_honor_persen.'% dari total' : '' }}.
                    @else
                        Honorarium tidak diizinkan di periode ini.
                    @endif
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left text-gray-500 border-b">
                            @if ($proposal->lama_tahun > 1)<th class="py-2 pe-4 font-medium">Tahun</th>@endif
                            <th class="py-2 pe-4 font-medium">Kategori</th><th class="py-2 pe-4 font-medium">Uraian</th><th class="py-2 pe-4 font-medium text-end">Volume</th><th class="py-2 pe-4 font-medium text-end">Harga satuan</th><th class="py-2 pe-4 font-medium text-end">Total</th><th></th>
                        </tr></thead>
                        <tbody>
                            @foreach ($proposal->rab->sortBy(['tahun_ke', fn ($a, $b) => strcmp($a->kategori->value, $b->kategori->value)]) as $item)
                                <tr class="border-b border-gray-100">
                                    @if ($proposal->lama_tahun > 1)<td class="py-2 pe-4">{{ $item->tahun_ke }}</td>@endif
                                    <td class="py-2 pe-4">{{ $item->kategori->label() }}</td>
                                    <td class="py-2 pe-4">{{ $item->uraian }}</td>
                                    <td class="py-2 pe-4 text-end">{{ rtrim(rtrim($item->volume, '0'), '.') }} {{ $item->satuan }}</td>
                                    <td class="py-2 pe-4 text-end"><x-rupiah :nilai="$item->harga_satuan" /></td>
                                    <td class="py-2 pe-4 text-end"><x-rupiah :nilai="$item->total" /></td>
                                    <td class="py-2 text-end">
                                        @if ($bisaDiubah)
                                            <form method="POST" action="{{ route('proposal.rab.destroy', [$proposal, $item]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600 hover:underline">Hapus</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="font-medium">
                                <td colspan="{{ $proposal->lama_tahun > 1 ? 5 : 4 }}" class="py-2 pe-4 text-end">Total</td>
                                <td class="py-2 pe-4 text-end"><x-rupiah :nilai="$proposal->totalRab()" /></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @if ($bisaDiubah)
                    <form method="POST" action="{{ route('proposal.rab.store', $proposal) }}" class="mt-6 grid gap-3 sm:grid-cols-3">
                        @csrf
                        <x-select-input name="kategori" required>
                            @foreach ($kategoriRab as $kategori)
                                <option value="{{ $kategori->value }}" @selected(old('kategori') === $kategori->value)>{{ $kategori->label() }}</option>
                            @endforeach
                        </x-select-input>
                        <x-text-input name="uraian" placeholder="Uraian" class="sm:col-span-2" :value="old('uraian')" required />
                        <x-text-input name="volume" type="number" step="0.01" min="0" placeholder="Volume" :value="old('volume')" required />
                        <x-text-input name="satuan" placeholder="Satuan (OH, paket, unit)" :value="old('satuan')" required />
                        <x-text-input name="harga_satuan" type="number" min="1" placeholder="Harga satuan (Rp)" :value="old('harga_satuan')" required />
                        @if ($proposal->lama_tahun > 1)
                            <x-text-input name="tahun_ke" type="number" min="1" :max="$proposal->lama_tahun" placeholder="Tahun ke-" :value="old('tahun_ke', 1)" />
                        @endif
                        <div><x-secondary-button type="submit">Tambah item</x-secondary-button></div>
                    </form>
                @endif
            </x-kartu>

            <x-kartu judul="Target luaran">
                @forelse ($proposal->luaran as $luaran)
                    <div class="flex items-center justify-between border-b border-gray-100 py-2 text-sm last:border-0">
                        <div><span class="font-medium">{{ $luaran->jenis->label() }}</span> <span class="text-gray-500">· {{ $luaran->kategori }}{{ $luaran->target_keterangan ? ' · '.$luaran->target_keterangan : '' }}</span></div>
                        @if ($bisaDiubah)
                            <form method="POST" action="{{ route('proposal.luaran.destroy', [$proposal, $luaran]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline">Hapus</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Belum ada target luaran.</p>
                @endforelse
                @if ($periode->skema->luaran_wajib)
                    <p class="mt-3 text-xs text-gray-500">Luaran wajib skema ini: {{ $periode->skema->luaran_wajib }}</p>
                @endif
                @if ($bisaDiubah)
                    <form method="POST" action="{{ route('proposal.luaran.store', $proposal) }}" class="mt-6 grid gap-3 sm:grid-cols-4">
                        @csrf
                        <x-select-input name="kategori">
                            <option value="wajib">Wajib</option>
                            <option value="tambahan">Tambahan</option>
                        </x-select-input>
                        <x-select-input name="jenis">
                            @foreach (JenisLuaran::cases() as $jenis)
                                <option value="{{ $jenis->value }}">{{ $jenis->label() }}</option>
                            @endforeach
                        </x-select-input>
                        <x-text-input name="target_keterangan" placeholder="Target, misalnya jurnal Sinta 2" />
                        <div><x-secondary-button type="submit">Tambah luaran</x-secondary-button></div>
                    </form>
                @endif
            </x-kartu>

            <x-kartu judul="Riwayat status">
                @forelse ($proposal->riwayatStatus as $log)
                    <div class="border-b border-gray-100 py-2 text-sm last:border-0">
                        <span class="text-gray-500">{{ $log->created_at->translatedFormat('d M Y H:i') }}</span>
                        · {{ $log->status_ke->label() }}
                        · {{ $log->pelaku?->name ?? 'Sistem' }}
                        @if ($log->catatan)
                            <span class="text-gray-600">· {{ $log->catatan }}</span>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Belum ada perubahan status.</p>
                @endforelse
            </x-kartu>
        </div>
    </div>
</x-app-layout>
