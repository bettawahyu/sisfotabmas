<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $mitra->exists ? 'Ubah Mitra' : 'Mitra Baru' }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <form method="POST" action="{{ $mitra->exists ? route('mitra.update', $mitra) : route('mitra.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @if ($mitra->exists)
                        @method('PUT')
                    @endif
                    <div class="sm:col-span-2">
                        <x-input-label for="nama" value="Nama mitra" ikon="briefcase" />
                        <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $mitra->nama)" required />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="bidang" value="Bergerak di bidang" ikon="tag" />
                        <x-text-input id="bidang" name="bidang" class="mt-1 block w-full" :value="old('bidang', $mitra->bidang)" required />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="alamat" value="Alamat" ikon="map-pin" />
                        <x-text-input id="alamat" name="alamat" class="mt-1 block w-full" :value="old('alamat', $mitra->alamat)" />
                    </div>
                    @include('riwayat._wilayah', ['nilai' => $mitra->kabupaten_kota_kode])
                    <div class="sm:col-span-2">
                        <x-input-label for="kontak" value="Telepon atau email" ikon="phone" />
                        <x-text-input id="kontak" name="kontak" class="mt-1 block w-full" :value="old('kontak', $mitra->kontak)" />
                    </div>
                    <div class="sm:col-span-2"><x-centang-aktif :nilai="$mitra->is_active" /></div>
                    <div class="sm:col-span-2 flex items-center gap-3">
                        <x-primary-button ikon="save">Simpan</x-primary-button>
                        <a href="{{ route('mitra.index') }}" class="text-sm text-gray-600 hover:underline">Batal</a>
                    </div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
