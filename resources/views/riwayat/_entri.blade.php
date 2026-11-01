{{-- One record: its details, team, partners and files. --}}
@php use App\Models\RiwayatKegiatan; @endphp
<div class="text-sm space-y-1">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <p class="font-medium text-gray-800">{{ $r->judul }}</p>
        <span class="{{ $r->status_verifikasi === RiwayatKegiatan::VALID ? 'text-green-700' : ($r->status_verifikasi === RiwayatKegiatan::DITOLAK ? 'text-red-700' : 'text-gray-600') }}">{{ $r->namaStatusVerifikasi() }}</span>
    </div>
    <p class="text-gray-600">
        {{ $r->periode() }} · {{ $r->namaStatusKegiatan() }} · {{ $r->namaSumberDana() }}{{ $r->pemberi_dana ? ' ('.$r->pemberi_dana.')' : '' }}
        @if ($r->skema) · {{ $r->skema }} @endif
        @if ($r->nilai_dana) · <x-rupiah :nilai="$r->nilai_dana" /> @endif
    </p>
    <p class="text-gray-600">{{ $r->anggota->map(fn ($a) => $a->nama.($a->peran === RiwayatKegiatan::KETUA ? ' (ketua)' : ''))->join(', ') }}</p>
    @if ($r->kabupatenKota || $r->alamat_lokasi)
        <p class="text-gray-600"><x-ikon nama="map-pin" class="inline h-4 w-4" /> {{ collect([$r->alamat_lokasi, $r->kabupatenKota?->namaLengkap()])->filter()->join(', ') }}</p>
    @endif
    @if ($r->mitra->isNotEmpty())
        <p class="text-gray-600">Mitra: {{ $r->mitra->map(fn ($m) => $m->nama.($m->bidang ? ' ('.$m->bidang.')' : ''))->join('; ') }}</p>
    @endif
    @if ($r->isPkm())
        @if ($r->jumlah_sasaran) <p class="text-gray-600">Sasaran {{ number_format($r->jumlah_sasaran, 0, ',', '.') }} orang</p> @endif
        @if ($r->catatan_dampak) <p class="text-gray-700">Dampak: {{ $r->catatan_dampak }}</p> @endif
        @if ($r->tautan_video)
            <a href="{{ $r->tautan_video }}" class="text-indigo-700 underline break-all" target="_blank" rel="noopener noreferrer">Video kegiatan</a>
        @endif
        @foreach ($r->daftarTautanMedia() as $tautan)
            <a href="{{ $tautan }}" class="block text-indigo-700 underline break-all" target="_blank" rel="noopener noreferrer">{{ $tautan }}</a>
        @endforeach
    @endif
    @foreach ($r->karyaTertaut() as $karya)
        <x-karya-tertaut :karya="$karya" />
    @endforeach
    @if ($r->berkas->isNotEmpty())
        <p class="flex flex-wrap gap-x-4 gap-y-1">
            @foreach ($r->berkas as $b)
                <a href="{{ route('riwayat.berkas', $b) }}" class="text-indigo-700 hover:underline"><x-ikon nama="paperclip" class="inline h-4 w-4" /> {{ $b->nama_asli }}</a>
            @endforeach
        </p>
    @endif
    @if ($r->status_verifikasi === RiwayatKegiatan::DITOLAK && $r->catatan_verifikasi)
        <p class="text-red-700">Alasan ditolak: {{ $r->catatan_verifikasi }}</p>
    @endif
</div>
