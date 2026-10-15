<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bidang Fokus</h2>
            <x-tombol-tautan href="{{ route('sistem.bidang-fokus.create') }}" ikon="plus">Tambah bidang fokus</x-tombol-tautan>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" data-tabel-data>
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Kode</th><th class="py-2 pe-4 font-medium">Nama</th><th class="py-2 pe-4 font-medium">Jenis kegiatan</th><th class="py-2 pe-4 font-medium">Induk</th><th class="py-2 pe-4 font-medium text-end">Proposal</th><th class="py-2 pe-4 font-medium">Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($bidang as $b)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4">{{ $b->kode }}</td>
                                    <td class="py-2 pe-4">{{ $b->nama }}</td>
                                    <td class="py-2 pe-4">{{ $b->jenis_kegiatan->label() }}</td>
                                    <td class="py-2 pe-4">{{ $b->induk?->nama ?? '-' }}</td>
                                    <td class="py-2 pe-4 text-end">{{ $b->proposal_count }}</td>
                                    <td class="py-2 pe-4"><x-status-aktif :aktif="$b->is_active" /></td>
                                    <td class="py-2 text-end"><a href="{{ route('sistem.bidang-fokus.edit', $b) }}" class="text-indigo-700 hover:underline">Ubah</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-4 text-gray-600">Belum ada bidang fokus.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
