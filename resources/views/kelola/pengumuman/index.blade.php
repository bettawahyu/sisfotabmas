<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kelola Pengumuman</h2>
            <x-tombol-tautan href="{{ route('kelola.pengumuman.create') }}" ikon="edit-3">Tulis pengumuman</x-tombol-tautan>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" data-tabel-data>
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Judul</th><th class="py-2 pe-4 font-medium">Kategori</th><th class="py-2 pe-4 font-medium">Untuk</th><th class="py-2 pe-4 font-medium">Terbit</th><th class="py-2 pe-4 font-medium">Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($pengumuman as $p)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4">
                                        <a href="{{ route('pengumuman.show', $p) }}" class="text-indigo-700 hover:underline">{{ $p->judul }}</a>
                                        @if ($p->is_pinned)<span class="ms-1 text-xs text-amber-700">disematkan</span>@endif
                                        @if ($p->periode)<div class="text-xs text-gray-500">{{ $p->periode->nama }}</div>@endif
                                    </td>
                                    <td class="py-2 pe-4">{{ \App\Models\Pengumuman::KATEGORI[$p->kategori] ?? $p->kategori }}</td>
                                    <td class="py-2 pe-4">{{ $p->untukSemua() ? 'Semua' : (\App\Models\Role::ALL[$p->target_peran] ?? $p->target_peran) }}</td>
                                    <td class="py-2 pe-4">{{ $p->terbit_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                    <td class="py-2 pe-4">
                                        @if ($p->sedangTampil())
                                            <span class="text-green-700">Tayang</span>
                                        @elseif ($p->terbit_at?->isFuture())
                                            <span class="text-gray-600">Terjadwal</span>
                                        @else
                                            <span class="text-gray-500">Berakhir</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-end whitespace-nowrap">
                                        <a href="{{ route('kelola.pengumuman.edit', $p) }}" class="text-indigo-700 hover:underline">Ubah</a>
                                        <form method="POST" action="{{ route('kelola.pengumuman.destroy', $p) }}" class="inline" onsubmit="return confirm('Hapus pengumuman ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ms-3 text-red-700 hover:underline">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-4 text-gray-600">Belum ada pengumuman.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
