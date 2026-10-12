<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pengesahan Proposal</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu judul="Menunggu pengesahan">
                @include('proposal.partials.tabel', ['daftar' => $menunggu, 'tampilKetua' => true])
            </x-kartu>
            <x-kartu judul="Riwayat pengesahan saya" class="mt-6">
                @if ($riwayat->isEmpty())
                    <p class="text-sm text-gray-600">Belum ada.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b">
                                    <th class="py-2 pe-4 font-medium">Tanggal</th>
                                    <th class="py-2 pe-4 font-medium">Judul</th>
                                    <th class="py-2 pe-4 font-medium">Ketua</th>
                                    <th class="py-2 pe-4 font-medium">Keputusan</th>
                                    <th class="py-2 pe-4 font-medium">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($riwayat as $pengesahan)
                                    <tr class="border-b border-gray-100 last:border-0">
                                        <td class="py-2 pe-4 text-gray-600 whitespace-nowrap">{{ $pengesahan->created_at->translatedFormat('j M Y H:i') }}</td>
                                        <td class="py-2 pe-4"><a href="{{ route('proposal.show', $pengesahan->proposal) }}" class="text-indigo-700 hover:underline">{{ $pengesahan->proposal->judul }}</a></td>
                                        <td class="py-2 pe-4 text-gray-600">{{ $pengesahan->proposal->ketua->namaLengkap() }}</td>
                                        <td class="py-2 pe-4">{{ $pengesahan->keputusan === \App\Models\PengesahanProposal::DISETUJUI ? 'Disahkan' : 'Ditolak' }}</td>
                                        <td class="py-2 pe-4 text-gray-600">{{ $pengesahan->catatan ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-kartu>
        </div>
    </div>
</x-app-layout>
