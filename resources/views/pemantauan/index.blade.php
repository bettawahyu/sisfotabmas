<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pemantauan</h2>
        <p class="mt-1 text-sm text-gray-600">Kegiatan yang perlu diingatkan.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-kartu :judul="'Logbook tidak diisi '.$hariLogbook.' hari ('.$logbookSepi->count().')'">
                @forelse ($logbookSepi as $p)
                    <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-gray-100 py-2 text-sm last:border-0">
                        <div>
                            <a href="{{ route('pelaksanaan.show', $p) }}" class="text-indigo-700 hover:underline">{{ $p->judul }}</a>
                            <p class="text-gray-500">{{ $p->ketua->namaLengkap() }} · {{ $p->periode->nama }}</p>
                        </div>
                        <p class="text-gray-600">{{ $p->logbook_max_tanggal ? 'Terakhir '.\Illuminate\Support\Carbon::parse($p->logbook_max_tanggal)->translatedFormat('j M Y') : 'Belum pernah diisi' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Semua kegiatan berjalan mengisi logbook.</p>
                @endforelse
            </x-kartu>

            <x-kartu :judul="'Laporan kemajuan terlambat ('.$laporanTerlambat->count().')'">
                @forelse ($laporanTerlambat as $p)
                    <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-gray-100 py-2 text-sm last:border-0">
                        <div>
                            <a href="{{ route('pelaksanaan.show', $p) }}" class="text-indigo-700 hover:underline">{{ $p->judul }}</a>
                            <p class="text-gray-500">{{ $p->ketua->namaLengkap() }} · {{ $p->periode->nama }}</p>
                        </div>
                        <p class="text-red-700">Batas {{ $p->periode->tgl_laporan_kemajuan->translatedFormat('j M Y') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Tidak ada laporan kemajuan yang terlambat.</p>
                @endforelse
            </x-kartu>

            <x-kartu :judul="'Laporan akhir terlambat ('.$akhirTerlambat->count().')'">
                @forelse ($akhirTerlambat as $p)
                    <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-gray-100 py-2 text-sm last:border-0">
                        <div>
                            <a href="{{ route('pelaksanaan.show', $p) }}" class="text-indigo-700 hover:underline">{{ $p->judul }}</a>
                            <p class="text-gray-500">{{ $p->ketua->namaLengkap() }} · {{ $p->periode->nama }}</p>
                        </div>
                        <p class="text-red-700">Batas {{ $p->periode->tgl_laporan_akhir->translatedFormat('j M Y') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Tidak ada laporan akhir yang terlambat.</p>
                @endforelse
            </x-kartu>

            <x-kartu :judul="'Monev belum ada hasil ('.$monevTertunda->count().')'">
                @forelse ($monevTertunda as $p)
                    @php($pen = $p->penilaian->first())
                    <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-gray-100 py-2 text-sm last:border-0">
                        <div>
                            <a href="{{ route('pelaksanaan.show', $p) }}" class="text-indigo-700 hover:underline">{{ $p->judul }}</a>
                            <p class="text-gray-500">{{ $p->ketua->namaLengkap() }} · {{ $p->periode->nama }}</p>
                        </div>
                        <p class="text-gray-600">{{ $pen ? 'Menunggu '.$pen->reviewer->name : 'Reviewer belum ditugaskan' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Semua monev sudah ada hasilnya.</p>
                @endforelse
            </x-kartu>
        </div>
    </div>
</x-app-layout>
