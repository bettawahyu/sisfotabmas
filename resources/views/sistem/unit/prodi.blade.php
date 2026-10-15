<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $prodi->exists ? 'Ubah Program Studi' : 'Program Studi Baru' }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <form method="POST" action="{{ $prodi->exists ? route('sistem.prodi.update', $prodi) : route('sistem.prodi.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @if ($prodi->exists)
                        @method('PUT')
                    @endif
                    <div class="sm:col-span-2">
                        <x-input-label for="fakultas_id" value="Fakultas" ikon="home" />
                        <x-select-input id="fakultas_id" name="fakultas_id" class="mt-1 block w-full" required>
                            <option value="">-</option>
                            @foreach ($fakultas as $f)
                                <option value="{{ $f->id }}" @selected(old('fakultas_id', $prodi->fakultas_id) == $f->id)>{{ $f->nama }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="kode" value="Kode" ikon="hash" />
                        <x-text-input id="kode" name="kode" class="mt-1 block w-full" :value="old('kode', $prodi->kode)" required />
                    </div>
                    <div>
                        <x-input-label for="jenjang" value="Jenjang" ikon="layers" />
                        <x-select-input id="jenjang" name="jenjang" class="mt-1 block w-full">
                            @foreach (App\Models\ProgramStudi::JENJANG as $jenjang)
                                <option value="{{ $jenjang }}" @selected(old('jenjang', $prodi->jenjang) === $jenjang)>{{ $jenjang }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="nama" value="Nama" ikon="edit-3" />
                        <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $prodi->nama)" required />
                    </div>
                    <div class="sm:col-span-2"><x-centang-aktif :nilai="$prodi->is_active" /></div>
                    <div class="sm:col-span-2 flex items-center gap-3">
                        <x-primary-button ikon="save">Simpan</x-primary-button>
                        <a href="{{ route('sistem.fakultas.index') }}" class="text-sm text-gray-600 hover:underline">Batal</a>
                    </div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
