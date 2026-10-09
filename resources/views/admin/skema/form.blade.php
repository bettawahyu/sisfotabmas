@php
    use App\Enums\JenisKegiatan;
    use App\Enums\SumberDana;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $skema->exists ? 'Ubah Skema Hibah' : 'Skema Hibah Baru' }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <p class="mb-6 text-sm text-gray-600">Nilai di skema hanya bawaan. Plafon dan aturan honorarium yang berlaku diatur di setiap periode hibah.</p>
                <form method="POST" action="{{ $skema->exists ? route('admin.skema.update', $skema) : route('admin.skema.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @if ($skema->exists)
                        @method('PUT')
                    @endif
                    <div>
                        <x-input-label for="kode" value="Kode" />
                        <x-text-input id="kode" name="kode" class="mt-1 block w-full" :value="old('kode', $skema->kode)" required />
                    </div>
                    <div>
                        <x-input-label for="nama" value="Nama" />
                        <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $skema->nama)" required />
                    </div>
                    <div>
                        <x-input-label for="jenis_kegiatan" value="Jenis kegiatan" />
                        <x-select-input id="jenis_kegiatan" name="jenis_kegiatan" class="mt-1 block w-full">
                            @foreach (JenisKegiatan::cases() as $jenis)
                                <option value="{{ $jenis->value }}" @selected(old('jenis_kegiatan', $skema->jenis_kegiatan?->value) === $jenis->value)>{{ $jenis->label() }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="sumber_dana" value="Sumber dana" />
                        <x-select-input id="sumber_dana" name="sumber_dana" class="mt-1 block w-full">
                            @foreach (SumberDana::cases() as $sumber)
                                <option value="{{ $sumber->value }}" @selected(old('sumber_dana', $skema->sumber_dana?->value) === $sumber->value)>{{ $sumber->label() }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="dana_maksimal_default" value="Plafon bawaan (Rp)" />
                        <x-text-input id="dana_maksimal_default" name="dana_maksimal_default" type="number" min="0" class="mt-1 block w-full" :value="old('dana_maksimal_default', $skema->dana_maksimal_default)" required />
                    </div>
                    <div>
                        <x-input-label for="tkt_min" value="TKT minimal" />
                        <x-text-input id="tkt_min" name="tkt_min" type="number" min="1" max="9" class="mt-1 block w-full" :value="old('tkt_min', $skema->tkt_min)" />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="is_multitahun" value="0">
                        <input type="checkbox" name="is_multitahun" value="1" @checked(old('is_multitahun', $skema->is_multitahun)) class="rounded border-gray-300">
                        Skema multiyear
                    </label>
                    <div>
                        <x-input-label for="lama_maks_tahun" value="Lama maksimal (tahun)" />
                        <x-text-input id="lama_maks_tahun" name="lama_maks_tahun" type="number" min="1" max="5" class="mt-1 block w-full" :value="old('lama_maks_tahun', $skema->lama_maks_tahun)" required />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="honor_diizinkan" value="0">
                        <input type="checkbox" name="honor_diizinkan" value="1" @checked(old('honor_diizinkan', $skema->honor_diizinkan)) class="rounded border-gray-300">
                        Biaya honorarium diizinkan
                    </label>
                    <div>
                        <x-input-label for="batas_honor_persen" value="Batas honorarium (% dari total, opsional)" />
                        <x-text-input id="batas_honor_persen" name="batas_honor_persen" type="number" min="1" max="100" class="mt-1 block w-full" :value="old('batas_honor_persen', $skema->batas_honor_persen)" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="luaran_wajib" value="Luaran wajib" />
                        <textarea id="luaran_wajib" name="luaran_wajib" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('luaran_wajib', $skema->luaran_wajib) }}</textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $skema->is_active)) class="rounded border-gray-300">
                        Aktif
                    </label>
                    <div class="sm:col-span-2"><x-primary-button>Simpan</x-primary-button></div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
