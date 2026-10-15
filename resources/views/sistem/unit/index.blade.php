<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Unit</h2>
            <div class="flex flex-wrap gap-2">
                <x-tombol-tautan href="{{ route('sistem.impor.create', 'prodi') }}" ikon="upload" varian="kedua">Impor CSV</x-tombol-tautan>
                <x-tombol-tautan href="{{ route('sistem.fakultas.create') }}" ikon="plus">Tambah fakultas</x-tombol-tautan>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="space-y-6 px-4 sm:px-6 lg:px-8">
            <x-pesan />
            @forelse ($fakultas as $f)
                <x-kartu>
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-gray-300 pb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">{{ $f->nama }} <span class="text-sm font-normal text-gray-500">({{ $f->kode }})</span></h3>
                            <p class="mt-1 flex items-center gap-2 text-sm text-gray-600"><x-status-aktif :aktif="$f->is_active" /> {{ $f->program_studi_count }} program studi</p>
                        </div>
                        <div class="flex gap-4 text-sm">
                            <a href="{{ route('sistem.prodi.create', ['fakultas' => $f->id]) }}" class="text-indigo-700 hover:underline">Tambah prodi</a>
                            <a href="{{ route('sistem.fakultas.edit', $f) }}" class="text-indigo-700 hover:underline">Ubah fakultas</a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Kode</th><th class="py-2 pe-4 font-medium">Program studi</th><th class="py-2 pe-4 font-medium">Jenjang</th><th class="py-2 pe-4 font-medium text-end">Dosen</th><th class="py-2 pe-4 font-medium">Status</th><th></th></tr></thead>
                            <tbody>
                                @forelse ($f->programStudi as $p)
                                    <tr class="border-b border-gray-100 last:border-0">
                                        <td class="py-2 pe-4">{{ $p->kode }}</td>
                                        <td class="py-2 pe-4">{{ $p->nama }}</td>
                                        <td class="py-2 pe-4">{{ $p->jenjang }}</td>
                                        <td class="py-2 pe-4 text-end">{{ $p->dosen_count }}</td>
                                        <td class="py-2 pe-4"><x-status-aktif :aktif="$p->is_active" /></td>
                                        <td class="py-2 text-end"><a href="{{ route('sistem.prodi.edit', $p) }}" class="text-indigo-700 hover:underline">Ubah</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="py-4 text-gray-600">Belum ada program studi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-kartu>
            @empty
                <x-kartu><p class="text-sm text-gray-600">Belum ada fakultas.</p></x-kartu>
            @endforelse
        </div>
    </div>
</x-app-layout>
