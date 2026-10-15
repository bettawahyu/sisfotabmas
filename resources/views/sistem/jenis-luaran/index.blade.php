@php use App\Models\JenisLuaran; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Jenis Luaran</h2>
            <x-tombol-tautan href="{{ route('sistem.jenis-luaran.create') }}" ikon="plus">Tambah jenis luaran</x-tombol-tautan>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <p class="mb-4 text-sm text-gray-600">Pilihan target luaran pada proposal dan luaran wajib pada skema hibah, ditampilkan sesuai urutan.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium text-end">Urutan</th><th class="py-2 pe-4 font-medium">Kode</th><th class="py-2 pe-4 font-medium">Nama</th><th class="py-2 pe-4 font-medium">Berlaku untuk</th><th class="py-2 pe-4 font-medium">Tautan karya</th><th class="py-2 pe-4 font-medium text-end">Dipakai</th><th class="py-2 pe-4 font-medium">Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($jenis as $j)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4 text-end">{{ $j->urutan }}</td>
                                    <td class="py-2 pe-4">{{ $j->kode }}</td>
                                    <td class="py-2 pe-4">{{ $j->nama }}</td>
                                    <td class="py-2 pe-4">{{ JenisLuaran::BERLAKU[$j->berlaku_untuk] ?? $j->berlaku_untuk }}</td>
                                    <td class="py-2 pe-4">{{ JenisLuaran::NAMA_TAUTAN[$j->tautan_karya] ?? '-' }}</td>
                                    <td class="py-2 pe-4 text-end">{{ $j->luaran_count }}</td>
                                    <td class="py-2 pe-4"><x-status-aktif :aktif="$j->is_active" /></td>
                                    <td class="py-2 text-end"><a href="{{ route('sistem.jenis-luaran.edit', $j) }}" class="text-indigo-700 hover:underline">Ubah</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="py-4 text-gray-600">Belum ada jenis luaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
