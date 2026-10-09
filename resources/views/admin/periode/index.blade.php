<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Periode Hibah</h2>
            <a href="{{ route('admin.periode.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Tambah periode</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Tahun</th><th class="py-2 pe-4 font-medium">Nama</th><th class="py-2 pe-4 font-medium">Skema</th><th class="py-2 pe-4 font-medium">Pengajuan</th><th class="py-2 pe-4 font-medium text-end">Plafon</th><th class="py-2 pe-4 font-medium">Status</th><th class="py-2 pe-4 font-medium text-end">Proposal</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($periode as $p)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4">{{ $p->tahun_anggaran }}</td>
                                    <td class="py-2 pe-4">{{ $p->nama }}</td>
                                    <td class="py-2 pe-4">{{ $p->skema->nama }}</td>
                                    <td class="py-2 pe-4">{{ $p->tgl_buka->translatedFormat('d M') }} s.d. {{ $p->tgl_tutup->translatedFormat('d M Y') }}</td>
                                    <td class="py-2 pe-4 text-end"><x-rupiah :nilai="$p->dana_maksimal" /></td>
                                    <td class="py-2 pe-4">{{ $p->status->label() }}</td>
                                    <td class="py-2 pe-4 text-end">{{ $p->proposal_count }}</td>
                                    <td class="py-2 text-end"><a href="{{ route('admin.periode.edit', $p) }}" class="text-indigo-700 hover:underline">Ubah</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="py-4 text-gray-600">Belum ada periode.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
