<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Skema Hibah</h2>
            <a href="{{ route('admin.skema.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Tambah skema</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Kode</th><th class="py-2 pe-4 font-medium">Nama</th><th class="py-2 pe-4 font-medium">Jenis</th><th class="py-2 pe-4 font-medium">Sumber dana</th><th class="py-2 pe-4 font-medium text-end">Plafon bawaan</th><th class="py-2 pe-4 font-medium">Aktif</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($skema as $s)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4">{{ $s->kode }}</td>
                                    <td class="py-2 pe-4">{{ $s->nama }}{{ $s->is_multitahun ? ' (multiyear, maks '.$s->lama_maks_tahun.' tahun)' : '' }}</td>
                                    <td class="py-2 pe-4">{{ $s->jenis_kegiatan->label() }}</td>
                                    <td class="py-2 pe-4">{{ $s->sumber_dana->label() }}</td>
                                    <td class="py-2 pe-4 text-end"><x-rupiah :nilai="$s->dana_maksimal_default" /></td>
                                    <td class="py-2 pe-4">{{ $s->is_active ? 'Ya' : 'Tidak' }}</td>
                                    <td class="py-2 text-end"><a href="{{ route('admin.skema.edit', $s) }}" class="text-indigo-700 hover:underline">Ubah</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-4 text-gray-600">Belum ada skema.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
