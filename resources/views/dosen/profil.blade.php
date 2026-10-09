<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Profil Dosen</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <p class="text-sm text-gray-600 mb-6">NIDN atau NUPTK dan ID SINTA wajib diisi sebelum mengirim proposal.</p>
                <form method="POST" action="{{ route('profil-dosen.update') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <div class="sm:col-span-2">
                        <x-input-label for="nama" value="Nama (tanpa gelar)" />
                        <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $dosen?->nama ?? auth()->user()->name)" required />
                    </div>
                    <div>
                        <x-input-label for="gelar_depan" value="Gelar depan" />
                        <x-text-input id="gelar_depan" name="gelar_depan" class="mt-1 block w-full" :value="old('gelar_depan', $dosen?->gelar_depan)" />
                    </div>
                    <div>
                        <x-input-label for="gelar_belakang" value="Gelar belakang" />
                        <x-text-input id="gelar_belakang" name="gelar_belakang" class="mt-1 block w-full" :value="old('gelar_belakang', $dosen?->gelar_belakang)" />
                    </div>
                    <div>
                        <x-input-label for="nidn" value="NIDN" />
                        <x-text-input id="nidn" name="nidn" class="mt-1 block w-full" :value="old('nidn', $dosen?->nidn)" />
                    </div>
                    <div>
                        <x-input-label for="nuptk" value="NUPTK" />
                        <x-text-input id="nuptk" name="nuptk" class="mt-1 block w-full" :value="old('nuptk', $dosen?->nuptk)" />
                    </div>
                    <div>
                        <x-input-label for="sinta_id" value="ID SINTA" />
                        <x-text-input id="sinta_id" name="sinta_id" class="mt-1 block w-full" :value="old('sinta_id', $dosen?->sinta_id)" />
                    </div>
                    <div>
                        <x-input-label for="jabatan_fungsional" value="Jabatan fungsional" />
                        <x-text-input id="jabatan_fungsional" name="jabatan_fungsional" class="mt-1 block w-full" :value="old('jabatan_fungsional', $dosen?->jabatan_fungsional)" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="prodi_id" value="Program studi (homebase)" />
                        <x-select-input id="prodi_id" name="prodi_id" class="mt-1 block w-full">
                            <option value="">-</option>
                            @foreach ($prodi as $p)
                                <option value="{{ $p->id }}" @selected(old('prodi_id', $dosen?->prodi_id) == $p->id)>{{ $p->jenjang }} {{ $p->nama }} ({{ $p->fakultas->nama }})</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div class="sm:col-span-2">
                        <x-primary-button>Simpan</x-primary-button>
                    </div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
