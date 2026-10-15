<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kualifikasi Dosen</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <p class="text-sm text-gray-600 mb-6">NIDN atau NUPTK dan ID SINTA wajib diisi sebelum mengirim proposal.</p>
                <form method="POST" action="{{ route('profil-dosen.update') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <div class="sm:col-span-2">
                        <x-input-label for="nama" value="Nama (tanpa gelar)" ikon="user" />
                        <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $dosen?->nama ?? auth()->user()->name)" required />
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
                    <div>
                        <x-input-label for="sinta_id" value="ID SINTA" ikon="hash" />
                        <x-text-input id="sinta_id" name="sinta_id" class="mt-1 block w-full" :value="old('sinta_id', $dosen?->sinta_id)" />
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
                    <div class="sm:col-span-2">
                        <x-input-label for="google_scholar_id" value="Google Scholar (ID atau URL profil, opsional)" ikon="link" />
                        <x-text-input id="google_scholar_id" name="google_scholar_id" class="mt-1 block w-full" :value="old('google_scholar_id', $dosen?->google_scholar_id)" placeholder="https://scholar.google.com/citations?user=..." />
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
                    <div class="sm:col-span-2">
                        <x-primary-button ikon="save">Simpan</x-primary-button>
                    </div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
