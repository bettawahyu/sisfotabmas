<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Penetapan dan SK</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Periode</th><th class="py-2 pe-4 font-medium text-end">Menunggu penetapan</th><th class="py-2 pe-4 font-medium text-end">Disetujui</th><th class="py-2 pe-4 font-medium text-end">Kuota</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($periode as $p)
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="py-2 pe-4">{{ $p->nama }}</td>
                                <td class="py-2 pe-4 text-end">{{ $p->menunggu_count }}</td>
                                <td class="py-2 pe-4 text-end">{{ $p->disetujui_count }}</td>
                                <td class="py-2 pe-4 text-end">{{ $p->kuota ?? '-' }}</td>
                                <td class="py-2 text-end"><a href="{{ route('admin.penetapan.show', $p) }}" class="text-indigo-700 hover:underline">Buka</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-gray-600">Belum ada periode dengan proposal yang selesai dinilai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
