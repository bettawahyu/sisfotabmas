<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $peringkat->exists ? 'Ubah Peringkat Jurnal' : 'Peringkat Jurnal Baru' }}</h2>
    </x-slot>

    @php($kodeTerkunci = $peringkat->exists && $peringkat->publikasi()->exists())
    <div class="py-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <form method="POST" action="{{ $peringkat->exists ? route('sistem.peringkat-jurnal.update', $peringkat) : route('sistem.peringkat-jurnal.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @if ($peringkat->exists)
                        @method('PUT')
                    @endif
                    <div>
                        <x-input-label for="kode" value="Kode (huruf kecil, angka, garis bawah)" ikon="hash" />
                        <x-text-input id="kode" name="kode" class="mt-1 block w-full" :value="old('kode', $peringkat->kode)" required :readonly="$kodeTerkunci" />
                        @if ($kodeTerkunci)
                            <p class="mt-1 text-xs text-gray-500">Kode tidak bisa diubah karena sudah dipakai publikasi.</p>
                        @endif
                    </div>
                    <div>
                        <x-input-label for="urutan" value="Urutan tampil" ikon="list" />
                        <x-text-input id="urutan" name="urutan" type="number" min="0" class="mt-1 block w-full" :value="old('urutan', $peringkat->urutan)" required />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="nama" value="Nama" ikon="edit-3" />
                        <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $peringkat->nama)" required />
                    </div>
                    <div class="sm:col-span-2"><x-centang-aktif :nilai="$peringkat->is_active" /></div>
                    <div class="sm:col-span-2 flex items-center gap-3">
                        <x-primary-button ikon="save">Simpan</x-primary-button>
                        <a href="{{ route('sistem.peringkat-jurnal.index') }}" class="text-sm text-gray-600 hover:underline">Batal</a>
                    </div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
