<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Penetapan: {{ $periode->nama }}</h2>
        <p class="mt-1 text-sm text-gray-600">Kuota {{ $periode->kuota ?? 'tidak dibatasi' }} · Plafon <x-rupiah :nilai="$periode->dana_maksimal" /></p>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            <x-pesan />

            <x-kartu ikon="bar-chart-2" judul="Peringkat">
                @if ($peringkat->isEmpty())
                    <p class="text-sm text-gray-600">Tidak ada proposal yang menunggu penetapan.</p>
                @else
                    @php($belumLengkap = $peringkat->whereNull('rata_rata')->count())
                    @if ($belumLengkap > 0)
                        <p class="mb-4 rounded-md bg-yellow-50 p-3 text-sm text-yellow-800">{{ $belumLengkap }} proposal belum dinilai lengkap oleh dua reviewer. Penetapan bisa dilakukan setelah semuanya selesai. <a href="{{ route('admin.review.index') }}" class="font-medium underline">Lihat penugasan reviewer</a>.</p>
                    @endif
                    <p class="mb-4 text-sm text-gray-600">Centang proposal yang didanai dan sesuaikan dana disetujui. Proposal yang tidak dicentang ditetapkan tidak didanai, dan semua ketua diberi tahu.</p>
                    <form method="POST" action="{{ route('admin.penetapan.store', $periode) }}" onsubmit="return confirm('Tetapkan hasil untuk semua proposal di periode ini?')">
                        @csrf
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">#</th><th class="py-2 pe-4 font-medium">Didanai</th><th class="py-2 pe-4 font-medium">Proposal</th><th class="py-2 pe-4 font-medium text-end">Nilai R1</th><th class="py-2 pe-4 font-medium text-end">Nilai R2</th><th class="py-2 pe-4 font-medium text-end">Rata-rata</th><th class="py-2 pe-4 font-medium text-end">Diusulkan</th><th class="py-2 font-medium">Dana disetujui</th></tr></thead>
                                <tbody>
                                    @foreach ($peringkat as $i => $p)
                                        @php($slot = $p->penilaian->keyBy('urutan_reviewer'))
                                        <tr class="border-b border-gray-100 last:border-0">
                                            <td class="py-2 pe-4">{{ $i + 1 }}</td>
                                            <td class="py-2 pe-4"><input type="checkbox" name="didanai[]" value="{{ $p->id }}" @checked(in_array($p->id, old('didanai', []))) @disabled($p->rata_rata === null) class="rounded border-gray-300"></td>
                                            <td class="py-2 pe-4"><a href="{{ route('admin.review.show', $p) }}" class="text-indigo-700 hover:underline">{{ $p->judul }}</a><div class="text-xs text-gray-500">{{ $p->ketua->namaLengkap() }}</div></td>
                                            @foreach ([1, 2] as $u)
                                                @php($pen = $slot->get($u))
                                                <td class="py-2 pe-4 text-end">{{ $pen?->terkirim() ? number_format($pen->nilai_total, 2, ',', '.') : '-' }}</td>
                                            @endforeach
                                            <td class="py-2 pe-4 text-end font-medium">{{ $p->rata_rata !== null ? number_format($p->rata_rata, 2, ',', '.') : '-' }}</td>
                                            <td class="py-2 pe-4 text-end whitespace-nowrap"><x-rupiah :nilai="$p->dana_diusulkan" /></td>
                                            <td class="py-2"><x-text-input type="number" min="1" :max="$p->dana_diusulkan" name="dana[{{ $p->id }}]" class="w-40" :value="old('dana.'.$p->id, min($p->rekomendasi_dana_rata ?? $p->dana_diusulkan, $p->dana_diusulkan))" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4"><x-primary-button :disabled="$belumLengkap > 0" class="disabled:opacity-50">Tetapkan hasil</x-primary-button></div>
                    </form>
                @endif
            </x-kartu>

            <x-kartu ikon="award" judul="Proposal disetujui dan SK">
                @if ($disetujui->isEmpty())
                    <p class="text-sm text-gray-600">Belum ada proposal yang disetujui.</p>
                @else
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Peringkat</th><th class="py-2 pe-4 font-medium">Proposal</th><th class="py-2 pe-4 font-medium text-end">Dana disetujui</th><th class="py-2 font-medium">SK</th></tr></thead>
                        <tbody>
                            @foreach ($disetujui as $p)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4">{{ $p->peringkat }}</td>
                                    <td class="py-2 pe-4"><a href="{{ route('proposal.show', $p) }}" class="text-indigo-700 hover:underline">{{ $p->judul }}</a><div class="text-xs text-gray-500">{{ $p->ketua->namaLengkap() }}</div></td>
                                    <td class="py-2 pe-4 text-end"><x-rupiah :nilai="$p->dana_disetujui" /></td>
                                    <td class="py-2">{{ $p->no_sk ? $p->no_sk.' · '.$p->tgl_sk->translatedFormat('d M Y') : 'Belum ada' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if ($disetujui->whereNull('no_sk')->isNotEmpty())
                        <form method="POST" action="{{ route('admin.penetapan.sk', $periode) }}" class="mt-6 grid gap-3 sm:grid-cols-3">
                            @csrf
                            <x-text-input name="no_sk" placeholder="Nomor SK" class="block w-full sm:col-span-2" :value="old('no_sk')" required />
                            <x-text-input name="tgl_sk" type="date" class="block w-full" :value="old('tgl_sk')" required />
                            <div class="sm:col-span-3"><x-secondary-button type="submit" ikon="file-text">Catat SK untuk semua yang belum punya</x-secondary-button></div>
                        </form>
                    @endif
                @endif
            </x-kartu>
        </div>
    </div>
</x-app-layout>
