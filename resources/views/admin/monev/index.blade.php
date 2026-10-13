@php
    use App\Models\Berkas;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Monev</h2>
        <p class="mt-1 text-sm text-gray-600">Proposal yang sudah mengirim laporan kemajuan. Satu reviewer menilai laporan dari meja (desk review).</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-pesan />
            @forelse ($proposal as $p)
                @php($laporan = $p->laporan->first())
                @php($pen = $p->penilaian->first())
                <x-kartu>
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <div>
                            <a href="{{ route('proposal.pelaksanaan.show', $p) }}" class="font-medium text-indigo-700 hover:underline">{{ $p->judul }}</a>
                            <p class="text-sm text-gray-500">{{ $p->ketua->namaLengkap() }} · {{ $p->periode->nama }}{{ $p->periode->tgl_monev ? ' · batas monev '.$p->periode->tgl_monev->translatedFormat('j M Y') : '' }}</p>
                        </div>
                        @if ($laporan)
                            <p class="text-sm text-gray-600">Capaian {{ $laporan->persentase_capaian }}% · anggaran {{ $laporan->persentase_anggaran_terpakai }}%</p>
                        @endif
                    </div>
                    @if ($laporan)
                        <p class="mt-2 text-sm">
                            @foreach ($laporan->berkas as $berkas)
                                <a class="me-3 text-indigo-700 underline" href="{{ route('proposal.pelaksanaan.berkas', [$p, $berkas]) }}">{{ $berkas->kategori === Berkas::LAPORAN_KEUANGAN ? 'Laporan keuangan' : 'Laporan kemajuan' }}</a>
                            @endforeach
                        </p>
                    @endif

                    @if ($pen?->terkirim())
                        <dl class="mt-4 grid gap-3 border-t border-gray-100 pt-4 text-sm sm:grid-cols-3">
                            <div><dt class="text-gray-500">Reviewer</dt><dd>{{ $pen->reviewer->name }}</dd></div>
                            <div><dt class="text-gray-500">Hasil</dt><dd class="font-medium">{{ $pen->namaRekomendasi() }}</dd></div>
                            <div><dt class="text-gray-500">Nilai</dt><dd>{{ $pen->nilai_total !== null ? number_format($pen->nilai_total, 2, ',', '.') : '-' }}</dd></div>
                            <div class="sm:col-span-3"><dt class="text-gray-500">Komentar untuk pengusul</dt><dd class="whitespace-pre-line">{{ $pen->komentar_untuk_pengusul }}</dd></div>
                            <div class="sm:col-span-3"><dt class="text-gray-500">Catatan internal</dt><dd class="whitespace-pre-line">{{ $pen->catatan_internal ?: '-' }}</dd></div>
                        </dl>
                        <form method="POST" action="{{ route('admin.monev.buka', [$p, $pen]) }}" class="mt-4 flex flex-wrap gap-3">
                            @csrf
                            <x-text-input name="alasan" placeholder="Alasan membuka kembali" class="flex-1" required />
                            <x-secondary-button type="submit">Buka kembali</x-secondary-button>
                        </form>
                    @else
                        @if ($pen)
                            <div class="mt-4 flex flex-wrap items-baseline gap-3 border-t border-gray-100 pt-4 text-sm">
                                <p>Reviewer: <span class="font-medium">{{ $pen->reviewer->name }}</span> <span class="text-gray-500">· belum dikirim</span></p>
                                <form method="POST" action="{{ route('admin.monev.destroy', [$p, $pen]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-700 hover:underline">Batalkan penugasan</button>
                                </form>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('admin.monev.store', $p) }}" class="mt-4 flex flex-wrap gap-3">
                            @csrf
                            <x-select-input name="reviewer_id" class="flex-1" required>
                                <option value="">Pilih reviewer monev</option>
                                @foreach ($reviewer as $r)
                                    <option value="{{ $r->id }}" @selected($pen?->reviewer_id === $r->id)>{{ $r->name }}</option>
                                @endforeach
                            </x-select-input>
                            <x-primary-button>{{ $pen ? 'Ganti reviewer' : 'Tugaskan' }}</x-primary-button>
                        </form>
                    @endif
                </x-kartu>
            @empty
                <x-kartu><p class="text-sm text-gray-600">Belum ada proposal dalam tahap monev.</p></x-kartu>
            @endforelse
        </div>
    </div>
</x-app-layout>
