<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rumpun Ilmu</h2>
            <div class="flex flex-wrap gap-2">
                <x-tombol-tautan href="{{ route('sistem.impor.create', 'rumpun-ilmu') }}" ikon="upload" varian="kedua">Impor CSV</x-tombol-tautan>
                <x-tombol-tautan href="{{ route('sistem.rumpun-ilmu.create') }}" ikon="plus">Tambah rumpun</x-tombol-tautan>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <p class="mb-4 text-sm text-gray-600">Struktur tiga tingkat: rumpun › pohon › cabang. Proposal memilih tingkat cabang.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Kode</th><th class="py-2 pe-4 font-medium">Nama</th><th class="py-2 pe-4 font-medium">Dipakai</th><th class="py-2 pe-4 font-medium">Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($rumpun as $r)
                                @include('sistem.rumpun-ilmu.baris', ['node' => $r, 'kedalaman' => 0])
                                @foreach ($turunan->get($r->id, []) as $pohon)
                                    @include('sistem.rumpun-ilmu.baris', ['node' => $pohon, 'kedalaman' => 1])
                                    @foreach ($turunan->get($pohon->id, []) as $cabang)
                                        @include('sistem.rumpun-ilmu.baris', ['node' => $cabang, 'kedalaman' => 2])
                                    @endforeach
                                @endforeach
                            @empty
                                <tr><td colspan="5" class="py-4 text-gray-600">Belum ada rumpun ilmu.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
