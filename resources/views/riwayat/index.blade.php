@php
    use App\Models\Proposal;
    use App\Services\AlurRiwayat;

    $bagian = [
        'berlangsung' => ['Sedang berlangsung', 'clock', 'Hibah LPPM yang didanai atau berjalan, dan kegiatan mandiri atau luar hibah yang belum selesai.'],
        'selesai' => ['Riwayat', 'archive', 'Hibah LPPM yang sudah dinyatakan selesai, dan kegiatan mandiri atau luar hibah yang sudah selesai.'],
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ AlurRiwayat::LABEL[$jenis] }}</h2>
            <x-tombol-tautan :href="route('riwayat.create', $jenis)" ikon="plus">Catat {{ $jenis === 'pkm' ? 'PkM' : 'penelitian' }}</x-tombol-tautan>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            <x-pesan />
            <p class="text-sm text-gray-600">
                Catat {{ $jenis === 'pkm' ? 'pengabdian kepada masyarakat' : 'penelitian' }} yang tidak lewat hibah LPPM, misalnya mandiri atau didanai Kemdiktisaintek, pemerintah daerah, atau industri.
                Hibah LPPM tampil otomatis. Data yang Anda isi diverifikasi LPPM atau operator; data yang diisi LPPM untuk Anda langsung tervalidasi.
                Data yang sudah valid hanya bisa dikoreksi dengan menghubungi operator LPPM.
            </p>

            @foreach ($bagian as $kunci => [$judul, $ikon, $keterangan])
                <x-kartu :ikon="$ikon" :judul="$judul.' ('.$daftar[$kunci]->count().')'">
                    <p class="mb-4 text-xs text-gray-500">{{ $keterangan }}</p>
                    <div class="space-y-4">
                        @forelse ($daftar[$kunci] as $item)
                            <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                                @if ($item instanceof Proposal)
                                    <div class="flex flex-wrap items-start justify-between gap-2 text-sm">
                                        <div>
                                            <a href="{{ route('proposal.show', $item) }}" class="font-medium text-indigo-700 hover:underline">{{ $item->judul }}</a>
                                            <p class="text-gray-600">Hibah LPPM · {{ $item->periode->skema->nama }} · {{ $item->periode->tahun_anggaran }} · Ketua {{ $item->ketua->namaLengkap() }}</p>
                                        </div>
                                        <x-status-proposal :proposal="$item" />
                                    </div>
                                @else
                                    @include('riwayat._entri', ['r' => $item])
                                    @if (AlurRiwayat::bolehUbah($item, auth()->user()))
                                        <div class="mt-2 flex gap-4 text-sm">
                                            <a href="{{ route('riwayat.edit', [$jenis, $item]) }}" class="text-indigo-700 hover:underline">Ubah</a>
                                            <form method="POST" action="{{ route('riwayat.destroy', [$jenis, $item]) }}" data-konfirmasi="Hapus data ini?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-700 hover:underline">Hapus</button>
                                            </form>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-600">Belum ada data.</p>
                        @endforelse
                    </div>
                </x-kartu>
            @endforeach
        </div>
    </div>
</x-app-layout>
