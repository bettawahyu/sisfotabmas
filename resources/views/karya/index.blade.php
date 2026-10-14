@php
    use App\Models\Hki;
    use App\Models\PublikasiPenulis;
    use App\Services\AlurKarya;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Publikasi, HKI, dan Paten</h2>
            <a href="{{ route("karya.{$jenis}.create") }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Tambah {{ AlurKarya::LABEL[$jenis] }}</a>
        </div>
        @include('karya._tab')
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <p class="mb-4 text-sm text-gray-600">Data yang Anda isi atau yang mencantumkan nama Anda. Setiap data diperiksa kaprodi, lalu divalidasi LPPM. Data yang sudah valid bisa ditautkan ke capaian luaran proposal di halaman pelaksanaan.</p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Judul</th><th class="py-2 pe-4 font-medium">Jenis</th><th class="py-2 pe-4 font-medium">Status</th><th class="py-2 pe-4 font-medium">Verifikasi</th><th class="py-2 pe-4 font-medium">Luaran proposal</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($karya as $k)
                                <tr class="border-b border-gray-100 last:border-0 align-top">
                                    <td class="py-2 pe-4">
                                        <p class="font-medium">{{ $k->judul }}</p>
                                        <p class="text-gray-500">{{ $k->kontributor->map(fn ($n) => $n instanceof PublikasiPenulis ? $n->nama_penulis : $n->nama)->join(', ') }}</p>
                                    </td>
                                    <td class="py-2 pe-4">{{ $k->namaJenis() }}{{ $jenis === 'publikasi' ? ' · '.$k->tahun : '' }}</td>
                                    <td class="py-2 pe-4">{{ $k->namaStatus() }}</td>
                                    <td class="py-2 pe-4 {{ $k->status_verifikasi === Hki::VALID ? 'text-green-700' : ($k->status_verifikasi === Hki::DITOLAK ? 'text-red-700' : 'text-gray-600') }}">
                                        {{ $k->namaStatusVerifikasi() }}
                                        @if ($k->status_verifikasi === Hki::DITOLAK && $k->catatan_verifikasi)
                                            <p class="text-red-700">{{ $k->catatan_verifikasi }}</p>
                                        @endif
                                    </td>
                                    <td class="py-2 pe-4">
                                        @foreach ($k->luaran as $l)
                                            <a href="{{ route('proposal.show', $l->proposal) }}" class="block text-indigo-700 hover:underline">{{ $l->proposal->judul }}</a>
                                        @endforeach
                                    </td>
                                    <td class="py-2 text-end whitespace-nowrap">
                                        @if ($k->status_verifikasi !== Hki::VALID)
                                            <a href="{{ route("karya.{$jenis}.edit", $k) }}" class="text-indigo-700 hover:underline">Ubah</a>
                                            @if ($k->luaran->isEmpty())
                                                <form method="POST" action="{{ route("karya.{$jenis}.destroy", $k) }}" class="inline" onsubmit="return confirm('Hapus data ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="ms-3 text-red-700 hover:underline">Hapus</button>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-4 text-gray-600">Belum ada data {{ AlurKarya::LABEL[$jenis] }}.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
