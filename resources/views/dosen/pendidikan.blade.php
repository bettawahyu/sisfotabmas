<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ubah Riwayat Pendidikan</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-kartu>
                <form method="POST" action="{{ route('profil-dosen.pendidikan.update', $riwayat) }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    @include('dosen._isian-pendidikan')
                    <div class="flex items-center gap-4 sm:col-span-2">
                        <x-primary-button ikon="save">Simpan</x-primary-button>
                        <a href="{{ route('profil-dosen.edit') }}" class="text-sm text-indigo-700 hover:underline">Batal</a>
                    </div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
