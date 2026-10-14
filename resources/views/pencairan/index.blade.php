<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pencairan Dana</h2>
        @unless ($bisaCatat)
            <p class="mt-1 text-sm text-gray-600">Hanya lihat. Pencairan dicatat oleh bagian keuangan.</p>
        @endunless
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">
            <x-pesan />

            <x-kartu ikon="clock" judul="Menunggu termin 1">
                @forelse ($menunggu as $p)
                    <div class="border-b border-gray-100 py-4 first:pt-0 last:border-0 last:pb-0">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <div>
                                <p class="font-medium text-gray-900">{{ $p->judul }}</p>
                                <p class="text-sm text-gray-500">{{ $p->ketua->namaLengkap() }} · Kontrak {{ $p->no_kontrak }} ({{ $p->tgl_kontrak->translatedFormat('d M Y') }})</p>
                            </div>
                            <p class="text-sm">Termin 1 ({{ $p->periode->termin_pertama_persen }}%): <span class="font-medium"><x-rupiah :nilai="$p->jumlah_termin_1" /></span> dari <x-rupiah :nilai="$p->dana_disetujui" /></p>
                        </div>
                        @if ($bisaCatat)
                            <form method="POST" action="{{ route('pencairan.store', $p) }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-3">
                                @csrf
                                <div><x-input-label value="Tanggal cair" ikon="calendar" /><x-text-input type="date" name="tgl_cair" class="mt-1" :value="today()->toDateString()" required /></div>
                                <div><x-input-label value="Bukti transfer (PDF atau gambar)" ikon="paperclip" /><input type="file" name="bukti" accept="application/pdf,image/*" required class="mt-1 text-sm"></div>
                                <label class="flex items-center gap-2 text-sm text-gray-700 pb-2">
                                    <input type="hidden" name="pajak_lunas" value="0">
                                    <input type="checkbox" name="pajak_lunas" value="1" class="rounded border-gray-300"> Pajak sudah dipotong
                                </label>
                                <x-primary-button ikon="edit-3">Catat termin 1</x-primary-button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Tidak ada termin 1 yang menunggu.</p>
                @endforelse
            </x-kartu>

            <x-kartu ikon="clock" judul="Menunggu termin 2">
                @forelse ($termin2 as $p)
                    <div class="border-b border-gray-100 py-4 first:pt-0 last:border-0 last:pb-0">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <div>
                                <p class="font-medium text-gray-900">{{ $p->judul }}</p>
                                <p class="text-sm text-gray-500">{{ $p->ketua->namaLengkap() }} · Kontrak {{ $p->no_kontrak }}</p>
                            </div>
                            <p class="text-sm">Termin 2 ({{ 100 - $p->periode->termin_pertama_persen }}%): <span class="font-medium"><x-rupiah :nilai="$p->jumlah_termin_2" /></span></p>
                        </div>
                        @if ($p->syarat_termin_2 !== [])
                            <ul class="mt-2 list-disc ps-5 text-sm text-amber-700">
                                @foreach ($p->syarat_termin_2 as $syarat)
                                    <li>{{ $syarat }}</li>
                                @endforeach
                            </ul>
                        @elseif ($bisaCatat)
                            <form method="POST" action="{{ route('pencairan.termin-2.store', $p) }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-3">
                                @csrf
                                <div><x-input-label value="Tanggal cair" ikon="calendar" /><x-text-input type="date" name="tgl_cair" class="mt-1" :value="today()->toDateString()" required /></div>
                                <div><x-input-label value="Bukti transfer (PDF atau gambar)" ikon="paperclip" /><input type="file" name="bukti" accept="application/pdf,image/*" required class="mt-1 text-sm"></div>
                                <label class="flex items-center gap-2 text-sm text-gray-700 pb-2">
                                    <input type="hidden" name="pajak_lunas" value="0">
                                    <input type="checkbox" name="pajak_lunas" value="1" class="rounded border-gray-300"> Pajak sudah dipotong
                                </label>
                                <x-primary-button ikon="edit-3">Catat termin 2</x-primary-button>
                            </form>
                        @else
                            <p class="mt-2 text-sm text-green-700">Syarat lengkap, menunggu keuangan.</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Tidak ada proposal yang menunggu termin 2.</p>
                @endforelse
            </x-kartu>

            <x-kartu ikon="clock" judul="Riwayat pencairan">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" data-tabel-data>
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Tanggal</th><th class="py-2 pe-4 font-medium">Proposal</th><th class="py-2 pe-4 font-medium">Termin</th><th class="py-2 pe-4 font-medium text-end">Jumlah</th><th class="py-2 pe-4 font-medium">Pajak</th><th class="py-2 pe-4 font-medium">Dicatat oleh</th><th class="py-2 font-medium">Bukti</th></tr></thead>
                        <tbody>
                            @forelse ($riwayat as $r)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4 whitespace-nowrap">{{ $r->tgl_cair?->translatedFormat('d M Y') }}</td>
                                    <td class="py-2 pe-4">{{ $r->proposal->judul }}<div class="text-xs text-gray-500">{{ $r->proposal->ketua->namaLengkap() }}</div></td>
                                    <td class="py-2 pe-4">{{ $r->termin_ke }} ({{ $r->persentase }}%)</td>
                                    <td class="py-2 pe-4 text-end whitespace-nowrap"><x-rupiah :nilai="$r->jumlah" /></td>
                                    <td class="py-2 pe-4">{{ $r->pajak_lunas ? 'Dipotong' : '-' }}</td>
                                    <td class="py-2 pe-4">{{ $r->pencair?->name ?? '-' }}</td>
                                    <td class="py-2">@if ($r->bukti)<a href="{{ route('pencairan.bukti', $r) }}" class="text-indigo-700 hover:underline">Unduh</a>@else - @endif</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-4 text-gray-600">Belum ada pencairan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
