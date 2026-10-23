<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Profil Dosen</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu judul="Foto profil" ikon="camera" class="mb-6">
                @include('profile.partials.update-photo-form', ['user' => auth()->user()])
            </x-kartu>
            <x-kartu>
                <p class="text-sm text-gray-600 mb-6">NIDN atau NUPTK dan ID SINTA wajib diisi sebelum mengirim proposal.</p>
                <form method="POST" action="{{ route('profil-dosen.update') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <div class="sm:col-span-2">
                        <h4 class="text-sm font-semibold text-gray-800">Identitas</h4>
                    </div>
                    <div>
                        <x-input-label for="nama" value="Nama (tanpa gelar)" ikon="user" />
                        <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $dosen?->nama ?? auth()->user()->name)" required />
                        <p class="mt-1 text-xs text-gray-500">Nama akun ikut berubah.</p>
                    </div>
                    <div>
                        <x-input-label value="Email" ikon="mail" />
                        <p class="mt-1 block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 break-all">{{ auth()->user()->email }}</p>
                        <p class="mt-1 text-xs text-gray-500">Email login akun; diubah oleh Admin Sistem.</p>
                    </div>
                    <div>
                        <x-input-label for="gelar_depan" value="Gelar depan" ikon="award" />
                        <x-text-input id="gelar_depan" name="gelar_depan" class="mt-1 block w-full" :value="old('gelar_depan', $dosen?->gelar_depan)" />
                    </div>
                    <div>
                        <x-input-label for="gelar_belakang" value="Gelar belakang" ikon="award" />
                        <x-text-input id="gelar_belakang" name="gelar_belakang" class="mt-1 block w-full" :value="old('gelar_belakang', $dosen?->gelar_belakang)" />
                    </div>
                    <div>
                        <x-input-label for="nidn" value="NIDN" ikon="hash" />
                        <x-text-input id="nidn" name="nidn" class="mt-1 block w-full" :value="old('nidn', $dosen?->nidn)" />
                    </div>
                    <div>
                        <x-input-label for="nuptk" value="NUPTK" ikon="hash" />
                        <x-text-input id="nuptk" name="nuptk" class="mt-1 block w-full" :value="old('nuptk', $dosen?->nuptk)" />
                    </div>
                    <div class="sm:col-span-2 mt-2 border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-semibold text-gray-800">Data diri</h4>
                    </div>
                    <div>
                        <x-input-label for="jenis_kelamin" value="Jenis kelamin" ikon="user" />
                        <x-select-input id="jenis_kelamin" name="jenis_kelamin" class="mt-1 block w-full">
                            <option value="">-</option>
                            @foreach (\App\Models\Dosen::JENIS_KELAMIN as $kode => $label)
                                <option value="{{ $kode }}" @selected(old('jenis_kelamin', $dosen?->jenis_kelamin) === $kode)>{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="status_pernikahan" value="Status pernikahan" ikon="heart" />
                        <x-select-input id="status_pernikahan" name="status_pernikahan" class="mt-1 block w-full">
                            <option value="">-</option>
                            @foreach (\App\Models\Dosen::STATUS_PERNIKAHAN as $kode => $label)
                                <option value="{{ $kode }}" @selected(old('status_pernikahan', $dosen?->status_pernikahan) === $kode)>{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="tempat_lahir" value="Tempat lahir" ikon="map-pin" />
                        <x-text-input id="tempat_lahir" name="tempat_lahir" class="mt-1 block w-full" :value="old('tempat_lahir', $dosen?->tempat_lahir)" />
                    </div>
                    <div>
                        <x-input-label for="tgl_lahir" value="Tanggal lahir" ikon="calendar" />
                        <x-text-input id="tgl_lahir" name="tgl_lahir" type="date" class="mt-1 block w-full" :value="old('tgl_lahir', $dosen?->tgl_lahir?->format('Y-m-d'))" />
                    </div>
                    <div>
                        <x-input-label for="no_hp" value="No. telepon" ikon="phone" />
                        <x-text-input id="no_hp" name="no_hp" type="tel" class="mt-1 block w-full" :value="old('no_hp', $dosen?->no_hp)" autocomplete="tel" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="alamat" value="Alamat" ikon="home" />
                        <textarea id="alamat" name="alamat" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('alamat', $dosen?->alamat) }}</textarea>
                    </div>
                    <div class="sm:col-span-2 mt-2 border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-semibold text-gray-800">Kepegawaian dan akademik</h4>
                    </div>
                    <div>
                        <x-input-label for="jabatan_fungsional" value="Jabatan fungsional" ikon="briefcase" />
                        <x-select-input id="jabatan_fungsional" name="jabatan_fungsional" class="mt-1 block w-full">
                            <option value="">-</option>
                            @foreach (App\Enums\JabatanFungsional::cases() as $jabatan)
                                <option value="{{ $jabatan->value }}" @selected(old('jabatan_fungsional', $dosen?->jabatan_fungsional?->value) === $jabatan->value)>{{ $jabatan->label() }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label value="Pendidikan terakhir" ikon="book-open" />
                        <p class="mt-1 block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ $dosen?->pendidikan_terakhir ?? 'Belum ada' }}</p>
                        <p class="mt-1 text-xs text-gray-500">Diambil dari jenjang tertinggi di riwayat pendidikan.</p>
                    </div>
                    <div>
                        <x-input-label for="tmt_dosen" value="TMT sebagai dosen" ikon="calendar" />
                        <x-text-input id="tmt_dosen" name="tmt_dosen" type="date" class="mt-1 block w-full" :value="old('tmt_dosen', $dosen?->tmt_dosen?->format('Y-m-d'))" />
                    </div>
                    <div>
                        <x-input-label for="bidang_keahlian" value="Bidang keahlian" ikon="target" />
                        <x-text-input id="bidang_keahlian" name="bidang_keahlian" class="mt-1 block w-full" :value="old('bidang_keahlian', $dosen?->bidang_keahlian)" placeholder="Contoh: Kecerdasan buatan, sistem informasi" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="prodi_id" value="Program studi (homebase)" ikon="book" />
                        <x-select-input id="prodi_id" name="prodi_id" class="mt-1 block w-full">
                            <option value="">-</option>
                            @foreach ($prodi as $p)
                                <option value="{{ $p->id }}" @selected(old('prodi_id', $dosen?->prodi_id) == $p->id)>{{ $p->jenjang }} {{ $p->nama }} ({{ $p->fakultas->nama }})</option>
                            @endforeach
                        </x-select-input>
                    </div>

                    <div class="sm:col-span-2 mt-2 border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-semibold text-gray-800">ID peneliti</h4>
                    </div>
                    <div>
                        <x-input-label for="sinta_id" value="ID SINTA (ID atau URL profil)" ikon="hash" />
                        <x-text-input id="sinta_id" name="sinta_id" class="mt-1 block w-full" :value="old('sinta_id', $dosen?->sinta_id)" placeholder="https://sinta.kemdiktisaintek.go.id/authors/profile/..." />
                    </div>
                    <div>
                        <x-input-label for="google_scholar_id" value="Google Scholar (ID atau URL profil)" ikon="link" />
                        <x-text-input id="google_scholar_id" name="google_scholar_id" class="mt-1 block w-full" :value="old('google_scholar_id', $dosen?->google_scholar_id)" placeholder="https://scholar.google.com/citations?user=..." />
                    </div>
                    <div>
                        <x-input-label for="orcid" value="ORCID" ikon="link" />
                        <x-text-input id="orcid" name="orcid" class="mt-1 block w-full" :value="old('orcid', $dosen?->orcid)" placeholder="0000-0000-0000-0000" />
                    </div>
                    <div class="sm:col-span-2 mt-2 border-t border-gray-200 pt-4">
                        <h4 class="text-sm font-semibold text-gray-800">Sertifikasi dan pelatihan</h4>
                    </div>
                    @foreach ([
                        ['sertifikasi_dosen', 'Sertifikasi dosen (serdos)', 'tahun_serdos'],
                        ['pekerti', 'Pekerti', 'tahun_pekerti'],
                        ['applied_approach', 'Applied Approach (AA)', 'tahun_applied_approach'],
                    ] as [$kolom, $label, $tahun])
                        <div class="sm:col-span-2 grid gap-4 sm:grid-cols-2 rounded-md border border-gray-200 p-4" x-data="{ sudah: @js((bool) old($kolom, $dosen?->{$kolom})) }">
                            <div>
                                <x-input-label :for="$kolom" :value="$label" ikon="check-circle" />
                                <x-select-input :id="$kolom" :name="$kolom" class="mt-1 block w-full" x-on:change="sudah = $event.target.value === '1'">
                                    <option value="0" @selected(! old($kolom, $dosen?->{$kolom}))>Belum</option>
                                    <option value="1" @selected((bool) old($kolom, $dosen?->{$kolom}))>Sudah</option>
                                </x-select-input>
                            </div>
                            <div x-show="sudah" x-cloak>
                                <x-input-label :for="$tahun" value="Tahun" ikon="calendar" />
                                <x-text-input :id="$tahun" :name="$tahun" type="number" min="1980" :max="now()->year" class="mt-1 block w-full" :value="old($tahun, $dosen?->{$tahun})" />
                            </div>
                            @if ($kolom === 'sertifikasi_dosen')
                                <div class="sm:col-span-2" x-show="sudah" x-cloak>
                                    <x-input-label for="no_serdos" value="Nomor sertifikat pendidik" ikon="hash" />
                                    <x-text-input id="no_serdos" name="no_serdos" class="mt-1 block w-full" :value="old('no_serdos', $dosen?->no_serdos)" />
                                </div>
                            @endif
                        </div>
                    @endforeach
                    <div class="sm:col-span-2">
                        <x-primary-button ikon="save">Simpan</x-primary-button>
                    </div>
                </form>
            </x-kartu>

            <x-kartu judul="Metrik SINTA" ikon="bar-chart-2" class="mt-6">
                @if ($dosen?->sinta_id)
                    <p class="mb-4 text-sm text-gray-600">
                        Diambil dari <a href="{{ \App\Services\Sinta::urlProfil($dosen->sinta_id) }}" target="_blank" rel="noopener" class="text-indigo-700 hover:underline">profil SINTA Anda</a>, termasuk h-index dan sitasi Google Scholar yang tercatat di SINTA.
                        @if ($dosen->sinkron_terakhir_at)
                            Terakhir diperbarui {{ $dosen->sinkron_terakhir_at->translatedFormat('d F Y H:i') }}.
                        @endif
                    </p>
                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-5">
                        @foreach ([
                            'Skor SINTA' => $dosen->skor_sinta === null ? null : number_format((float) $dosen->skor_sinta, 2, ',', '.'),
                            'H-index Scopus' => $dosen->h_index_scopus,
                            'Sitasi Scopus' => $dosen->sitasi_scopus === null ? null : number_format($dosen->sitasi_scopus, 0, ',', '.'),
                            'H-index Scholar' => $dosen->h_index_scholar,
                            'Sitasi Scholar' => $dosen->sitasi_scholar === null ? null : number_format($dosen->sitasi_scholar, 0, ',', '.'),
                        ] as $label => $nilai)
                            <div class="rounded-md border border-gray-200 p-3">
                                <dt class="text-xs text-gray-500">{{ $label }}</dt>
                                <dd class="mt-1 text-lg font-semibold text-gray-800">{{ $nilai ?? '–' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <div class="mt-4">
                        @if ($berikutnya = $dosen->sinkronSintaBerikutnya())
                            <p class="text-sm text-gray-600">Sinkron berikutnya tersedia mulai {{ $berikutnya->translatedFormat('d F Y') }}.</p>
                        @else
                            <form method="POST" action="{{ route('profil-dosen.sinkron-sinta') }}">
                                @csrf
                                <x-primary-button ikon="refresh-cw">Perbarui dari SINTA</x-primary-button>
                            </form>
                            <p class="mt-2 text-xs text-gray-500">Setelah diperbarui, tombol ini tersedia lagi {{ \App\Models\Dosen::JEDA_SINKRON_SINTA }} hari kemudian.</p>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-gray-600">Isi dan simpan ID SINTA di atas untuk mengambil skor SINTA, h-index, dan sitasi dari SINTA.</p>
                @endif
            </x-kartu>

            <x-kartu judul="Riwayat Pendidikan" ikon="book-open" class="mt-6">
                @if ($dosen)
                    <p class="mb-4 text-sm text-gray-600">Jenjang tertinggi menjadi pendidikan terakhir yang dicek terhadap syarat pendidikan minimal skema hibah.</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Jenjang</th><th class="py-2 pe-4 font-medium">Perguruan tinggi</th><th class="py-2 pe-4 font-medium">Program studi</th><th class="py-2 pe-4 font-medium">Lulus</th><th></th></tr></thead>
                            <tbody>
                                @forelse ($dosen->riwayatPendidikan as $r)
                                    <tr class="border-b border-gray-100 last:border-0 align-top">
                                        <td class="py-2 pe-4 font-medium">{{ $r->jenjang }}</td>
                                        <td class="py-2 pe-4">{{ $r->institusi }}@if ($r->negara && $r->negara !== 'Indonesia'), {{ $r->negara }}@endif</td>
                                        <td class="py-2 pe-4">{{ $r->program_studi ?? '-' }}</td>
                                        <td class="py-2 pe-4">{{ $r->tahun_lulus ?? '-' }}</td>
                                        <td class="py-2 text-end whitespace-nowrap">
                                            <a href="{{ route('profil-dosen.pendidikan.edit', $r) }}" class="text-indigo-700 hover:underline">Ubah</a>
                                            <form method="POST" action="{{ route('profil-dosen.pendidikan.destroy', $r) }}" class="inline" data-konfirmasi="Hapus riwayat pendidikan ini?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="ms-3 text-red-700 hover:underline">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-4 text-gray-600">Belum ada riwayat pendidikan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h4 class="mt-6 mb-3 text-sm font-semibold text-gray-800">Tambah pendidikan</h4>
                    <form method="POST" action="{{ route('profil-dosen.pendidikan.store') }}" class="grid gap-4 sm:grid-cols-2">
                        @csrf
                        @include('dosen._isian-pendidikan', ['riwayat' => null])
                        <div class="sm:col-span-2"><x-primary-button ikon="plus">Tambah</x-primary-button></div>
                    </form>
                @else
                    <p class="text-sm text-gray-600">Simpan profil dosen di atas terlebih dahulu, lalu tambahkan riwayat pendidikan.</p>
                @endif
            </x-kartu>
        </div>
    </div>
</x-app-layout>
