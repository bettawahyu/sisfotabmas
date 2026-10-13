<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kontrak</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-pesan />
            @forelse ($proposal as $p)
                <x-kartu>
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <div>
                            <a href="{{ route('proposal.show', $p) }}" class="font-medium text-indigo-700 hover:underline">{{ $p->judul }}</a>
                            <p class="text-sm text-gray-500">{{ $p->ketua->namaLengkap() }} · {{ $p->periode->nama }} · SK {{ $p->no_sk ?? 'belum ada' }}</p>
                        </div>
                        <p class="text-sm font-medium"><x-rupiah :nilai="$p->dana_disetujui" /></p>
                    </div>
                    @if ($p->no_sk)
                        <form method="POST" action="{{ route('admin.kontrak.store', $p) }}" class="mt-4 grid gap-3 sm:grid-cols-4">
                            @csrf
                            <div class="sm:col-span-4"><x-input-label value="Nomor kontrak" /><x-text-input name="no_kontrak" class="mt-1 block w-full" required /></div>
                            <div><x-input-label value="Tanggal kontrak" /><x-text-input type="date" name="tgl_kontrak" class="mt-1 block w-full" required /></div>
                            <div><x-input-label value="Mulai kegiatan" /><x-text-input type="date" name="tgl_mulai" class="mt-1 block w-full" required /></div>
                            <div><x-input-label value="Selesai kegiatan" /><x-text-input type="date" name="tgl_selesai" class="mt-1 block w-full" required /></div>
                            <div class="flex items-end"><x-primary-button>Catat kontrak</x-primary-button></div>
                        </form>
                    @else
                        <p class="mt-3 text-sm text-amber-700">Catat SK di <a href="{{ route('admin.penetapan.show', $p->periode) }}" class="underline">halaman penetapan</a> terlebih dahulu.</p>
                    @endif
                </x-kartu>
            @empty
                <x-kartu><p class="text-sm text-gray-600">Tidak ada proposal disetujui yang menunggu kontrak.</p></x-kartu>
            @endforelse
        </div>
    </div>
</x-app-layout>
