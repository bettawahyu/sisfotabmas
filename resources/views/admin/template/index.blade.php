<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Templat Dokumen</h2>
            <a href="{{ route('admin.template.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Unggah templat</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <p class="mb-4 text-sm text-gray-600">Templat tanpa skema atau tahun berlaku untuk semua skema atau semua tahun. Dosen mengunduhnya dari halaman proposal.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Nama</th><th class="py-2 pe-4 font-medium">Jenis</th><th class="py-2 pe-4 font-medium">Skema</th><th class="py-2 pe-4 font-medium">Tahun</th><th class="py-2 pe-4 font-medium">Berkas</th><th class="py-2 pe-4 font-medium">Aktif</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($template as $t)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4">{{ $t->nama }}{{ $t->versi ? ' (v'.$t->versi.')' : '' }}</td>
                                    <td class="py-2 pe-4">{{ $t->namaJenis() }}</td>
                                    <td class="py-2 pe-4">{{ $t->skema?->nama ?? 'Semua skema' }}</td>
                                    <td class="py-2 pe-4">{{ $t->tahun ?? 'Semua' }}</td>
                                    <td class="py-2 pe-4">
                                        @if ($t->berkasTerbaru)
                                            <a href="{{ route('templat.unduh', $t) }}" class="text-indigo-700 hover:underline">{{ $t->berkasTerbaru->nama_asli }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="py-2 pe-4">{{ $t->is_active ? 'Ya' : 'Tidak' }}</td>
                                    <td class="py-2 text-end"><a href="{{ route('admin.template.edit', $t) }}" class="text-indigo-700 hover:underline">Ubah</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-4 text-gray-600">Belum ada templat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
