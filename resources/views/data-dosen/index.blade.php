@php
    use App\Enums\JabatanFungsional;
    use App\Models\Dosen;

    $tersaring = array_filter($filter, fn ($nilai) => filled($nilai));
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Data Dosen</h2>
            <div class="flex flex-wrap items-center gap-2">
                <x-tombol-tautan href="{{ route('data-dosen.unduh-semua') }}" ikon="download" varian="kedua"
                    title="Satu sheet: identitas dan riwayat pendidikan semua dosen, tanpa filter">Unduh semua</x-tombol-tautan>
                <x-tombol-tautan href="{{ route('data-dosen.unduh', $tersaring) }}" ikon="download"
                    title="Tiga sheet: Dosen, Riwayat Pendidikan, dan Publikasi, sesuai filter">Unduh sesuai filter</x-tombol-tautan>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="space-y-6 px-4 sm:px-6 lg:px-8">
            <x-kartu>
                <form method="GET" action="{{ route('data-dosen.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                    <div class="lg:col-span-2">
                        <x-input-label for="cari" value="Nama, NIDN, NUPTK, atau NIP" ikon="search" />
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
                        <x-input-label for="jabatan_fungsional" value="Jabatan fungsional" ikon="award" />
                        <x-select-input id="jabatan_fungsional" name="jabatan_fungsional" class="mt-1 block w-full">
                            <option value="">Semua jabatan</option>
                            @foreach (JabatanFungsional::cases() as $j)
                                <option value="{{ $j->value }}" @selected(($filter['jabatan_fungsional'] ?? '') === $j->value)>{{ $j->label() }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="pendidikan_terakhir" value="Pendidikan terakhir" ikon="book-open" />
                        <x-select-input id="pendidikan_terakhir" name="pendidikan_terakhir" class="mt-1 block w-full">
                            <option value="">Semua jenjang</option>
                            @foreach (Dosen::PENDIDIKAN as $jenjang)
                                <option value="{{ $jenjang }}" @selected(($filter['pendidikan_terakhir'] ?? '') === $jenjang)>{{ $jenjang }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-5">
                        <x-secondary-button type="submit" ikon="filter">Terapkan filter</x-secondary-button>
                        @if ($tersaring)
                            <a href="{{ route('data-dosen.index') }}" class="inline-flex items-center px-2 text-sm text-indigo-700 hover:underline">Hapus filter</a>
                        @endif
                    </div>
                </form>
                <p class="mt-4 text-xs text-gray-500">
                    @unless ($lihatSemua) Hanya dosen di unit Anda yang ditampilkan. @endunless
                    "Unduh sesuai filter" berisi tiga sheet (Dosen, Riwayat Pendidikan, Publikasi) untuk dosen yang tampil di bawah.
                    "Unduh semua" berisi satu sheet identitas dan riwayat pendidikan seluruh dosen{{ $lihatSemua ? '' : ' di unit Anda' }}.
                </p>
            </x-kartu>

            <x-kartu>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" data-tabel-data data-tabel-baris="25">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2 pe-4 font-medium">Nama</th>
                                <th class="py-2 pe-4 font-medium">NIDN/NUPTK</th>
                                <th class="py-2 pe-4 font-medium">Program studi</th>
                                <th class="py-2 pe-4 font-medium">Jabatan fungsional</th>
                                <th class="py-2 pe-4 font-medium">Pendidikan</th>
                                <th class="py-2 pe-4 font-medium">Publikasi & HKI</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dosen as $d)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4">
                                        <button type="button" class="text-start font-medium text-indigo-700 hover:underline" x-data x-on:click="$dispatch('open-modal', 'dosen-{{ $d->id }}')">{{ $d->namaLengkap() }}</button>
                                        @if ($d->user && ! $d->user->is_active)
                                            <x-status-aktif :aktif="false" />
                                        @endif
                                    </td>
                                    <td class="py-2 pe-4">{{ $d->nidn ?? $d->nuptk ?? '–' }}</td>
                                    <td class="py-2 pe-4">{{ $d->prodi?->nama ?? '–' }}</td>
                                    <td class="py-2 pe-4">{{ $d->jabatan_fungsional?->label() ?? '–' }}</td>
                                    <td class="py-2 pe-4">{{ $d->pendidikan_terakhir ?? '–' }}</td>
                                    <td class="py-2 pe-4">{{ $d->publikasi_count + $d->hki_count }}</td>
                                    <td class="py-2 text-end">
                                        <button type="button" class="text-indigo-700 hover:underline" x-data x-on:click="$dispatch('open-modal', 'dosen-{{ $d->id }}')">Detail</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-4 text-gray-600">Tidak ada dosen yang cocok.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        </div>
    </div>

    {{-- Outside the table, so paging in the browser does not take the dialogs with the rows. --}}
    @foreach ($dosen as $d)
        @php
            $isian = [
                'NIDN' => $d->nidn,
                'NUPTK' => $d->nuptk,
                'NIP' => $d->nip,
                'Email' => $d->user?->email,
                'No. telepon' => $d->no_hp,
                'Jenis kelamin' => Dosen::JENIS_KELAMIN[$d->jenis_kelamin] ?? null,
                'Tempat, tanggal lahir' => collect([$d->tempat_lahir, $d->tgl_lahir?->translatedFormat('d F Y')])->filter()->implode(', '),
                'Status pernikahan' => Dosen::STATUS_PERNIKAHAN[$d->status_pernikahan] ?? null,
                'Program studi' => $d->prodi ? trim("{$d->prodi->jenjang} {$d->prodi->nama}") : null,
                'Fakultas' => $d->prodi?->fakultas?->nama,
                'Jabatan fungsional' => $d->jabatan_fungsional?->label(),
                'Pendidikan terakhir' => $d->pendidikan_terakhir,
                'Bidang keahlian' => $d->bidang_keahlian,
                'TMT sebagai dosen' => $d->tmt_dosen?->translatedFormat('d F Y'),
                'Sertifikasi dosen' => $d->sertifikasi_dosen ? trim('Sudah '.collect([$d->tahun_serdos, $d->no_serdos ? "No. {$d->no_serdos}" : null])->filter()->implode(' · ')) : 'Belum',
                'Pekerti' => $d->pekerti ? trim("Sudah {$d->tahun_pekerti}") : 'Belum',
                'Applied Approach (AA)' => $d->applied_approach ? trim("Sudah {$d->tahun_applied_approach}") : 'Belum',
                'SINTA ID' => $d->sinta_id,
                'Skor SINTA' => $d->skor_sinta,
                'Scopus ID' => $d->scopus_author_id,
                'Google Scholar ID' => $d->google_scholar_id,
                'ORCID' => $d->orcid,
                'H-index Scopus / Scholar' => collect([$d->h_index_scopus, $d->h_index_scholar])->every(fn ($n) => $n === null) ? null : ($d->h_index_scopus ?? '–').' / '.($d->h_index_scholar ?? '–'),
                'Jumlah publikasi' => $d->publikasi_count,
                'Alamat' => $d->alamat,
            ];
        @endphp
        <x-modal name="dosen-{{ $d->id }}" maxWidth="2xl" focusable>
            <div class="p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $d->namaLengkap() }}</h3>
                        <p class="text-sm text-gray-600">Profil dosen</p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600" x-on:click="$dispatch('close')" aria-label="Tutup"><x-ikon nama="x" /></button>
                </div>

                <dl class="mt-4 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                    @foreach ($isian as $label => $nilai)
                        <div class="border-b border-gray-100 py-1">
                            <dt class="text-xs text-gray-500">{{ $label }}</dt>
                            <dd class="text-gray-800 break-words">{{ filled($nilai) ? $nilai : '–' }}</dd>
                        </div>
                    @endforeach
                </dl>

                <h4 class="mt-6 font-semibold text-gray-800">Riwayat pendidikan</h4>
                @if ($d->riwayatPendidikan->isEmpty())
                    <p class="mt-2 text-sm text-gray-600">Belum diisi.</p>
                @else
                    <div class="mt-2 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Jenjang</th><th class="py-2 pe-4 font-medium">Institusi</th><th class="py-2 pe-4 font-medium">Program studi</th><th class="py-2 pe-4 font-medium">Lulus</th></tr></thead>
                            <tbody>
                                @foreach ($d->riwayatPendidikan as $r)
                                    <tr class="border-b border-gray-100 last:border-0 align-top">
                                        <td class="py-2 pe-4">{{ $r->jenjang }}</td>
                                        <td class="py-2 pe-4">{{ $r->institusi }}@if ($r->negara)<span class="text-gray-500">, {{ $r->negara }}</span>@endif</td>
                                        <td class="py-2 pe-4">
                                            {{ $r->program_studi ?? '–' }}
                                            @if ($r->judul_tugas_akhir)<p class="text-xs text-gray-500">{{ $r->judul_tugas_akhir }}</p>@endif
                                        </td>
                                        <td class="py-2 pe-4">{{ $r->tahun_lulus ?? '–' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3 text-sm">
                        @if ($d->user)
                            <span class="text-gray-500">Akun</span> <x-status-aktif :aktif="$d->user->is_active" />
                            @if ($kelolaAkun && ! $d->user->is(auth()->user()))
                                <form method="POST" action="{{ route('data-dosen.aktif', $d) }}"
                                    data-konfirmasi="{{ $d->user->is_active ? 'Nonaktifkan akun '.$d->namaLengkap().'? Dosen tidak bisa login, datanya tetap tersimpan.' : 'Aktifkan kembali akun '.$d->namaLengkap().'?' }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="aktif" value="{{ $d->user->is_active ? 0 : 1 }}">
                                    @if ($d->user->is_active)
                                        <x-danger-button ikon="user-x">Nonaktifkan</x-danger-button>
                                    @else
                                        <x-primary-button ikon="user-check">Aktifkan</x-primary-button>
                                    @endif
                                </form>
                            @endif
                        @endif
                    </div>
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">Tutup</x-secondary-button>
                </div>
            </div>
        </x-modal>
    @endforeach
</x-app-layout>
