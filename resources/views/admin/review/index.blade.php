<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Penugasan Reviewer</h2>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu ikon="check-square" judul="Proposal dalam penilaian">
                <p class="mb-4 text-sm text-gray-600">Setiap proposal dinilai dua reviewer. Nilai akhir adalah rata-rata keduanya.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" data-tabel-data>
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Proposal</th><th class="py-2 pe-4 font-medium">Periode</th><th class="py-2 pe-4 font-medium">Reviewer 1</th><th class="py-2 pe-4 font-medium">Reviewer 2</th><th class="py-2 pe-4 font-medium text-end">Rata-rata</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($proposal as $p)
                                @php($slot = $p->penilaian->keyBy('urutan_reviewer'))
                                @php($terkirim = $p->penilaian->where('status', \App\Models\Penilaian::SELESAI))
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4"><a href="{{ route('proposal.show', $p) }}" class="text-indigo-700 hover:underline">{{ $p->judul }}</a><div class="text-xs text-gray-500">{{ $p->ketua->namaLengkap() }}</div></td>
                                    <td class="py-2 pe-4">{{ $p->periode->nama }}</td>
                                    @foreach ([1, 2] as $urutan)
                                        <td class="py-2 pe-4">
                                            @if ($pen = $slot->get($urutan))
                                                {{ $pen->reviewer->name }}
                                                <div class="text-xs {{ $pen->terkirim() ? 'text-green-700' : 'text-gray-500' }}">{{ $pen->terkirim() ? 'Nilai '.number_format($pen->nilai_total, 2, ',', '.') : 'Belum dikirim' }}</div>
                                            @else
                                                <span class="text-amber-700">Belum ditugaskan</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="py-2 pe-4 text-end">{{ $terkirim->count() === 2 ? number_format($terkirim->avg('nilai_total'), 2, ',', '.') : '-' }}</td>
                                    <td class="py-2 text-end"><a href="{{ route('admin.review.show', $p) }}" class="text-indigo-700 hover:underline">Atur</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-4 text-gray-600">Belum ada proposal yang lolos seleksi administrasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
