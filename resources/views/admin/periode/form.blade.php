@php
    use App\Enums\StatusPeriode;
    $tanggal = fn ($kolom) => old($kolom, $periode->{$kolom}?->format('Y-m-d'));
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $periode->exists ? 'Ubah Periode Hibah' : 'Periode Hibah Baru' }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                @unless ($periode->exists)
                    <form method="GET" action="{{ route('admin.periode.create') }}" class="mb-6 flex flex-wrap items-end gap-3 border-b pb-6">
                        <div class="flex-1">
                            <x-input-label for="pilih_skema" value="Ambil nilai bawaan dari skema" ikon="tag" />
                            <x-select-input id="pilih_skema" name="skema_id" class="mt-1 block w-full">
                                @foreach ($daftarSkema as $s)
                                    <option value="{{ $s->id }}" @selected($periode->skema_id == $s->id)>{{ $s->nama }}</option>
                                @endforeach
                            </x-select-input>
                        </div>
                        <x-secondary-button type="submit" ikon="download">Ambil</x-secondary-button>
                    </form>
                @endunless

                <form method="POST" action="{{ $periode->exists ? route('admin.periode.update', $periode) : route('admin.periode.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @if ($periode->exists)
                        @method('PUT')
                    @endif
                    <div>
                        <x-input-label for="skema_id" value="Skema" ikon="tag" />
                        <x-select-input id="skema_id" name="skema_id" class="mt-1 block w-full">
                            @foreach ($daftarSkema as $s)
                                <option value="{{ $s->id }}" @selected(old('skema_id', $periode->skema_id) == $s->id)>{{ $s->nama }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="tahun_anggaran" value="Tahun anggaran" ikon="calendar" />
                        <x-text-input id="tahun_anggaran" name="tahun_anggaran" type="number" class="mt-1 block w-full" :value="old('tahun_anggaran', $periode->tahun_anggaran)" required />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="nama" value="Nama pengumuman" ikon="edit-3" />
                        <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $periode->nama)" required />
                    </div>
                    @foreach ([
                        'tgl_buka' => 'Pengajuan dibuka',
                        'tgl_tutup' => 'Pengajuan ditutup',
                        'tgl_seleksi_selesai' => 'Seleksi selesai',
                        'tgl_pengumuman' => 'Pengumuman hasil',
                        'tgl_laporan_kemajuan' => 'Batas laporan kemajuan',
                        'tgl_monev' => 'Monev',
                        'tgl_laporan_akhir' => 'Batas laporan akhir',
                    ] as $kolom => $label)
                        <div>
                            <x-input-label :for="$kolom" :value="$label" />
                            <x-text-input :id="$kolom" :name="$kolom" type="date" class="mt-1 block w-full" :value="$tanggal($kolom)" :required="in_array($kolom, ['tgl_buka', 'tgl_tutup'])" />
                        </div>
                    @endforeach
                    <div>
                        <x-input-label for="dana_maksimal" value="Plafon per proposal (Rp)" ikon="dollar-sign" />
                        <x-text-input id="dana_maksimal" name="dana_maksimal" type="number" min="1" class="mt-1 block w-full" :value="old('dana_maksimal', $periode->dana_maksimal)" required />
                    </div>
                    <div>
                        <x-input-label for="kuota" value="Kuota proposal didanai" ikon="list" />
                        <x-text-input id="kuota" name="kuota" type="number" min="1" class="mt-1 block w-full" :value="old('kuota', $periode->kuota)" />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="honor_diizinkan" value="0">
                        <input type="checkbox" name="honor_diizinkan" value="1" @checked(old('honor_diizinkan', $periode->honor_diizinkan)) class="rounded border-gray-300">
                        Biaya honorarium diizinkan
                    </label>
                    <div>
                        <x-input-label for="batas_honor_persen" value="Batas honorarium (%)" ikon="dollar-sign" />
                        <x-text-input id="batas_honor_persen" name="batas_honor_persen" type="number" min="1" max="100" class="mt-1 block w-full" :value="old('batas_honor_persen', $periode->batas_honor_persen)" />
                    </div>
                    <div>
                        <x-input-label for="termin_pertama_persen" value="Termin pertama (%)" ikon="percent" />
                        <x-text-input id="termin_pertama_persen" name="termin_pertama_persen" type="number" min="1" max="100" class="mt-1 block w-full" :value="old('termin_pertama_persen', $periode->termin_pertama_persen)" required />
                    </div>
                    <div>
                        <x-input-label for="maks_sebagai_ketua" value="Maks. proposal sebagai ketua" ikon="list" />
                        <x-text-input id="maks_sebagai_ketua" name="maks_sebagai_ketua" type="number" min="1" class="mt-1 block w-full" :value="old('maks_sebagai_ketua', $periode->maks_sebagai_ketua)" required />
                    </div>
                    <div>
                        <x-input-label for="maks_sebagai_anggota" value="Maks. proposal sebagai anggota" ikon="list" />
                        <x-text-input id="maks_sebagai_anggota" name="maks_sebagai_anggota" type="number" min="0" class="mt-1 block w-full" :value="old('maks_sebagai_anggota', $periode->maks_sebagai_anggota)" required />
                    </div>
                    <div>
                        <x-input-label for="status" value="Status" ikon="flag" />
                        <x-select-input id="status" name="status" class="mt-1 block w-full">
                            @foreach (StatusPeriode::cases() as $status)
                                <option value="{{ $status->value }}" @selected(old('status', $periode->status?->value) === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div class="sm:col-span-2"><x-primary-button ikon="save">Simpan</x-primary-button></div>
                </form>
            </x-kartu>

            @if ($periode->exists)
                @php($tahapKriteria = [\App\Models\Penilaian::SELEKSI => 'Seleksi proposal', \App\Models\Penilaian::MONEV => 'Monev', \App\Models\Penilaian::AKHIR => 'Laporan akhir'])
                <x-kartu ikon="sliders" judul="Kriteria penilaian" class="mt-6">
                    <p class="mb-4 text-sm text-gray-600">Nilai akhir reviewer dihitung dari skor setiap kriteria dibagi skala maksimal, dikali bobot, lalu dijadikan skala 0 sampai 100.</p>
                    @foreach ($tahapKriteria as $tahap => $namaTahap)
                        @php($daftar = $periode->kriteria->where('tahap', $tahap)->sortBy('urutan'))
                        @if ($daftar->isNotEmpty())
                            <h4 class="mt-4 font-medium text-gray-900">{{ $namaTahap }} <span class="text-sm font-normal text-gray-500">· total bobot {{ $daftar->sum('bobot') }}</span></h4>
                            <ul class="mt-2 divide-y divide-gray-100 text-sm">
                                @foreach ($daftar as $k)
                                    <li class="py-2 flex items-center justify-between gap-4">
                                        <span>{{ $k->nama }} <span class="text-gray-500">· bobot {{ $k->bobot }} · skala 1 sampai {{ $k->skala_maks }}</span></span>
                                        <form method="POST" action="{{ route('admin.periode.kriteria.destroy', [$periode, $k]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-700 hover:underline">Hapus</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach
                    <form method="POST" action="{{ route('admin.periode.kriteria.store', $periode) }}" class="mt-6 grid gap-3 sm:grid-cols-5">
                        @csrf
                        <x-select-input name="tahap" class="block w-full">
                            @foreach ($tahapKriteria as $tahap => $namaTahap)
                                <option value="{{ $tahap }}">{{ $namaTahap }}</option>
                            @endforeach
                        </x-select-input>
                        <x-text-input name="nama" placeholder="Nama kriteria" class="block w-full sm:col-span-2" required />
                        <x-text-input name="bobot" type="number" min="1" max="100" placeholder="Bobot" class="block w-full" required />
                        <x-text-input name="skala_maks" type="number" min="2" max="10" value="7" title="Skala maksimal" class="block w-full" required />
                        <div class="sm:col-span-5"><x-secondary-button type="submit" ikon="plus">Tambah kriteria</x-secondary-button></div>
                    </form>
                </x-kartu>
            @endif
        </div>
    </div>
</x-app-layout>
