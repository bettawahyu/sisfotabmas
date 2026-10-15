@php
    use App\Enums\JabatanFungsional;
    use App\Enums\JenisKegiatan;
    use App\Enums\SumberDana;
    use App\Models\Dosen;
    use App\Models\JenisLuaran;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $skema->exists ? 'Ubah Skema Hibah' : 'Skema Hibah Baru' }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <p class="mb-6 text-sm text-gray-600">Nilai di skema hanya bawaan. Plafon dan aturan honorarium yang berlaku diatur di setiap periode hibah.</p>
                <form method="POST" action="{{ $skema->exists ? route('admin.skema.update', $skema) : route('admin.skema.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @if ($skema->exists)
                        @method('PUT')
                    @endif
                    <div>
                        <x-input-label for="kode" value="Kode" ikon="hash" />
                        <x-text-input id="kode" name="kode" class="mt-1 block w-full" :value="old('kode', $skema->kode)" required />
                    </div>
                    <div>
                        <x-input-label for="nama" value="Nama" ikon="edit-3" />
                        <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $skema->nama)" required />
                    </div>
                    <div>
                        <x-input-label for="jenis_kegiatan" value="Jenis kegiatan" ikon="tag" />
                        <x-select-input id="jenis_kegiatan" name="jenis_kegiatan" class="mt-1 block w-full">
                            @foreach (JenisKegiatan::cases() as $jenis)
                                <option value="{{ $jenis->value }}" @selected(old('jenis_kegiatan', $skema->jenis_kegiatan?->value) === $jenis->value)>{{ $jenis->label() }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="sumber_dana" value="Sumber dana" ikon="dollar-sign" />
                        <x-select-input id="sumber_dana" name="sumber_dana" class="mt-1 block w-full">
                            @foreach (SumberDana::cases() as $sumber)
                                <option value="{{ $sumber->value }}" @selected(old('sumber_dana', $skema->sumber_dana?->value) === $sumber->value)>{{ $sumber->label() }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="dana_maksimal_default" value="Plafon bawaan (Rp)" ikon="dollar-sign" />
                        <x-text-input id="dana_maksimal_default" name="dana_maksimal_default" type="number" min="0" class="mt-1 block w-full" :value="old('dana_maksimal_default', $skema->dana_maksimal_default)" required />
                    </div>
                    <div>
                        <x-input-label for="tkt_min" value="TKT minimal" ikon="bar-chart-2" />
                        <x-text-input id="tkt_min" name="tkt_min" type="number" min="1" max="9" class="mt-1 block w-full" :value="old('tkt_min', $skema->tkt_min)" />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="is_multitahun" value="0">
                        <input type="checkbox" name="is_multitahun" value="1" @checked(old('is_multitahun', $skema->is_multitahun)) class="rounded border-gray-300">
                        Skema multiyear
                    </label>
                    <div>
                        <x-input-label for="lama_maks_tahun" value="Lama maksimal (tahun)" ikon="clock" />
                        <x-text-input id="lama_maks_tahun" name="lama_maks_tahun" type="number" min="1" max="5" class="mt-1 block w-full" :value="old('lama_maks_tahun', $skema->lama_maks_tahun)" required />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="honor_diizinkan" value="0">
                        <input type="checkbox" name="honor_diizinkan" value="1" @checked(old('honor_diizinkan', $skema->honor_diizinkan)) class="rounded border-gray-300">
                        Biaya honorarium diizinkan
                    </label>
                    <div>
                        <x-input-label for="batas_honor_persen" value="Batas honorarium (% dari total, opsional)" ikon="dollar-sign" />
                        <x-text-input id="batas_honor_persen" name="batas_honor_persen" type="number" min="1" max="100" class="mt-1 block w-full" :value="old('batas_honor_persen', $skema->batas_honor_persen)" />
                    </div>

                    <div class="sm:col-span-2 border-t border-gray-200 pt-4">
                        <h3 class="font-semibold text-gray-800">Syarat pengusul</h3>
                        <p class="text-sm text-gray-600">Kosongkan bila tidak ada syarat. Syarat ketua dicek saat dosen menekan "Buat proposal"; syarat tim dicek saat proposal dikirim.</p>
                    </div>
                    <div>
                        <x-input-label for="jabatan_min" value="Jabatan fungsional minimal ketua" ikon="briefcase" />
                        <x-select-input id="jabatan_min" name="jabatan_min" class="mt-1 block w-full">
                            <option value="">Tanpa syarat</option>
                            @foreach (JabatanFungsional::cases() as $jabatan)
                                <option value="{{ $jabatan->value }}" @selected(old('jabatan_min', $skema->jabatan_min?->value) === $jabatan->value)>{{ $jabatan->label() }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="pendidikan_min" value="Pendidikan minimal ketua" ikon="book-open" />
                        <x-select-input id="pendidikan_min" name="pendidikan_min" class="mt-1 block w-full">
                            <option value="">Tanpa syarat</option>
                            @foreach (Dosen::PENDIDIKAN as $jenjang)
                                <option value="{{ $jenjang }}" @selected(old('pendidikan_min', $skema->pendidikan_min) === $jenjang)>{{ $jenjang }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="anggota_dosen_min" value="Anggota dosen minimal" ikon="users" />
                        <x-text-input id="anggota_dosen_min" name="anggota_dosen_min" type="number" min="0" max="20" class="mt-1 block w-full" :value="old('anggota_dosen_min', $skema->anggota_dosen_min)" />
                    </div>
                    <div>
                        <x-input-label for="anggota_dosen_maks" value="Anggota dosen maksimal" ikon="users" />
                        <x-text-input id="anggota_dosen_maks" name="anggota_dosen_maks" type="number" min="0" max="20" class="mt-1 block w-full" :value="old('anggota_dosen_maks', $skema->anggota_dosen_maks)" />
                    </div>
                    <div>
                        <x-input-label for="anggota_mahasiswa_min" value="Anggota mahasiswa minimal" ikon="user-plus" />
                        <x-text-input id="anggota_mahasiswa_min" name="anggota_mahasiswa_min" type="number" min="0" max="20" class="mt-1 block w-full" :value="old('anggota_mahasiswa_min', $skema->anggota_mahasiswa_min)" />
                    </div>

                    <div class="sm:col-span-2 border-t border-gray-200 pt-4">
                        <h3 class="font-semibold text-gray-800">Luaran wajib</h3>
                        <p class="text-sm text-gray-600">Centang jenis luaran yang wajib dicantumkan dan jumlah minimalnya. Proposal tidak bisa dikirim sebelum memenuhinya.</p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($jenisLuaran as $jenis)
                                @php($dicentang = in_array($jenis->id, array_map('intval', old('luaran_wajib_jenis', $luaranTerpilih->keys()->all())), true))
                                <div class="flex items-center justify-between gap-3 rounded-md border border-gray-200 px-3 py-2 text-sm">
                                    <label class="flex items-center gap-2 text-gray-700">
                                        <input type="checkbox" name="luaran_wajib_jenis[]" value="{{ $jenis->id }}" @checked($dicentang) class="rounded border-gray-300">
                                        {{ $jenis->nama }}
                                        @if ($jenis->berlaku_untuk !== JenisLuaran::SEMUA)
                                            <span class="text-xs text-gray-500">({{ JenisLuaran::BERLAKU[$jenis->berlaku_untuk] }})</span>
                                        @endif
                                    </label>
                                    <input type="number" name="luaran_wajib_jumlah[{{ $jenis->id }}]" min="1" max="20" aria-label="Jumlah minimal {{ $jenis->nama }}"
                                           value="{{ old('luaran_wajib_jumlah.'.$jenis->id, $luaranTerpilih->get($jenis->id, 1)) }}"
                                           class="w-16 rounded-md border-gray-300 py-1 text-sm shadow-sm">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="luaran_wajib" value="Catatan luaran (opsional, misalnya jurnal nasional terakreditasi SINTA)" ikon="target" />
                        <textarea id="luaran_wajib" name="luaran_wajib" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('luaran_wajib', $skema->luaran_wajib) }}</textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $skema->is_active)) class="rounded border-gray-300">
                        Aktif
                    </label>
                    <div class="sm:col-span-2"><x-primary-button ikon="save">Simpan</x-primary-button></div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
