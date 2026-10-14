<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pelaksanaan Kegiatan</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-pesan />

            <x-kartu>
                <p class="mb-4 text-sm text-gray-600">Kegiatan yang termin 1-nya sudah dicairkan. Buka kegiatan untuk mengisi logbook, melaporkan capaian luaran, dan mengirim laporan kemajuan serta laporan akhir.</p>
                @if ($daftar->isEmpty())
                    <p class="text-sm text-gray-600">Belum ada kegiatan yang dananya dicairkan.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b">
                                    <th class="py-2 pe-4 font-medium">Judul</th>
                                    <th class="py-2 pe-4 font-medium">Periode</th>
                                    <th class="py-2 pe-4 font-medium">Peran</th>
                                    <th class="py-2 pe-4 font-medium">Masa kegiatan</th>
                                    <th class="py-2 pe-4 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($daftar as $proposal)
                                    <tr class="border-b border-gray-100 last:border-0">
                                        <td class="py-2 pe-4"><a href="{{ route('pelaksanaan.show', $proposal) }}" class="text-indigo-700 hover:underline">{{ $proposal->judul }}</a></td>
                                        <td class="py-2 pe-4 text-gray-600">{{ $proposal->periode->nama }}</td>
                                        <td class="py-2 pe-4 text-gray-600">{{ $proposal->isKetua($dosen) ? 'Ketua' : 'Anggota · ketua '.$proposal->ketua->namaLengkap() }}</td>
                                        <td class="py-2 pe-4 text-gray-600">
                                            @if ($proposal->tgl_mulai && $proposal->tgl_selesai)
                                                {{ $proposal->tgl_mulai->translatedFormat('j M Y') }} s.d. {{ $proposal->tgl_selesai->translatedFormat('j M Y') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-2 pe-4"><x-status-proposal :proposal="$proposal" /></td>
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
