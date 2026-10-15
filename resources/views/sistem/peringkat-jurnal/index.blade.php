<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Peringkat Jurnal</h2>
            <x-tombol-tautan href="{{ route('sistem.peringkat-jurnal.create') }}" ikon="plus">Tambah peringkat</x-tombol-tautan>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <p class="mb-4 text-sm text-gray-600">Pilihan peringkat pada isian publikasi dosen, ditampilkan sesuai urutan.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium text-end">Urutan</th><th class="py-2 pe-4 font-medium">Kode</th><th class="py-2 pe-4 font-medium">Nama</th><th class="py-2 pe-4 font-medium text-end">Publikasi</th><th class="py-2 pe-4 font-medium">Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($peringkat as $p)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4 text-end">{{ $p->urutan }}</td>
                                    <td class="py-2 pe-4">{{ $p->kode }}</td>
                                    <td class="py-2 pe-4">{{ $p->nama }}</td>
                                    <td class="py-2 pe-4 text-end">{{ $p->publikasi_count }}</td>
                                    <td class="py-2 pe-4"><x-status-aktif :aktif="$p->is_active" /></td>
                                    <td class="py-2 text-end"><a href="{{ route('sistem.peringkat-jurnal.edit', $p) }}" class="text-indigo-700 hover:underline">Ubah</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-4 text-gray-600">Belum ada peringkat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
