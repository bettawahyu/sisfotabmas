<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $fakultas->exists ? 'Ubah Fakultas' : 'Fakultas Baru' }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <form method="POST" action="{{ $fakultas->exists ? route('sistem.fakultas.update', $fakultas) : route('sistem.fakultas.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @if ($fakultas->exists)
                        @method('PUT')
                    @endif
                    <div>
                        <x-input-label for="kode" value="Kode" ikon="hash" />
                        <x-text-input id="kode" name="kode" class="mt-1 block w-full" :value="old('kode', $fakultas->kode)" required />
                    </div>
                    <div>
                        <x-input-label for="nama" value="Nama" ikon="edit-3" />
                        <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $fakultas->nama)" required />
                    </div>
                    <div class="sm:col-span-2"><x-centang-aktif :nilai="$fakultas->is_active" /></div>
                    <div class="sm:col-span-2 flex items-center gap-3">
                        <x-primary-button ikon="save">Simpan</x-primary-button>
                        <a href="{{ route('sistem.fakultas.index') }}" class="text-sm text-gray-600 hover:underline">Batal</a>
                    </div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
