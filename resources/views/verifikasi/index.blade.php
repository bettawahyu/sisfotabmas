@php
    use App\Models\Berkas;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Verifikasi</h2>
        <p class="mt-1 text-sm text-gray-600">Laporan akhir dan capaian luaran yang menunggu diperiksa.</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-pesan />

            <x-kartu :judul="'Laporan akhir ('.$laporan->count().')'">
                @forelse ($laporan as $l)
                    <div class="border-b border-gray-100 py-4 first:pt-0 last:border-0 last:pb-0 text-sm">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <div>
                                <a href="{{ route('pelaksanaan.show', $l->proposal) }}" class="font-medium text-indigo-700 hover:underline">{{ $l->proposal->judul }}</a>
                                <p class="text-gray-500">{{ $l->proposal->ketua->namaLengkap() }} · {{ $l->proposal->periode->nama }} · dikirim {{ $l->diajukan_at->translatedFormat('j M Y') }}</p>
                            </div>
                            <p class="text-gray-600">Capaian {{ $l->persentase_capaian }}%</p>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-gray-700">{{ $l->ringkasan }}</p>
                        <p class="mt-2">
                            @foreach ($l->berkas as $berkas)
                                <a class="me-3 text-indigo-700 underline" href="{{ route('pelaksanaan.berkas', [$l->proposal, $berkas]) }}">{{ $berkas->kategori === Berkas::LAPORAN_KEUANGAN ? 'Laporan keuangan 100%' : 'Laporan akhir' }}</a>
                            @endforeach
                        </p>
                        <form method="POST" action="{{ route('verifikasi.laporan', $l) }}" class="mt-3 flex flex-wrap gap-3">
                            @csrf
                            <x-text-input name="catatan" placeholder="Catatan (wajib jika dikembalikan)" class="flex-1" />
                            <x-secondary-button type="submit" name="aksi" value="kembalikan">Kembalikan</x-secondary-button>
                            <x-primary-button name="aksi" value="terima">Verifikasi</x-primary-button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Tidak ada laporan akhir yang menunggu.</p>
                @endforelse
            </x-kartu>

            <x-kartu :judul="'Capaian luaran ('.$luaran->count().')'">
                @forelse ($luaran as $l)
                    <div class="border-b border-gray-100 py-4 first:pt-0 last:border-0 last:pb-0 text-sm">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <div>
                                <p><span class="font-medium">{{ $l->jenis->label() }}</span> <span class="text-gray-500">· {{ $l->kategori }} · {{ $l->namaCapaian() }}</span></p>
                                <a href="{{ route('pelaksanaan.show', $l->proposal) }}" class="text-gray-500 hover:underline">{{ $l->proposal->judul }} · {{ $l->proposal->ketua->namaLengkap() }}</a>
                            </div>
                        </div>
                        @if ($l->karya())
                            <x-karya-tertaut :karya="$l->karya()" />
                        @endif
                        @if ($l->url_bukti)
                            <a href="{{ $l->url_bukti }}" class="text-indigo-700 underline break-all" rel="noopener noreferrer" target="_blank">{{ $l->url_bukti }}</a>
                        @endif
                        <form method="POST" action="{{ route('verifikasi.luaran', $l) }}" class="mt-3 flex flex-wrap gap-3">
                            @csrf
                            <x-text-input name="catatan" placeholder="Catatan (wajib jika ditolak)" class="flex-1" />
                            <x-secondary-button type="submit" name="aksi" value="tolak">Tolak</x-secondary-button>
                            <x-primary-button name="aksi" value="valid">Valid</x-primary-button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Tidak ada capaian luaran yang menunggu.</p>
                @endforelse
            </x-kartu>
        </div>
    </div>
</x-app-layout>
