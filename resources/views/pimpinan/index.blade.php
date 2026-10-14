<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ringkasan Pimpinan</h2>
                <p class="mt-1 text-sm text-gray-600">Usulan, pendanaan, dan luaran penelitian dan PkM per tahun anggaran.</p>
            </div>
            @if ($daftarTahun->isNotEmpty())
                <form method="GET" action="{{ route('pimpinan.index') }}" class="flex items-center gap-2">
                    <x-select-input name="tahun" onchange="this.form.submit()">
                        @foreach ($daftarTahun as $t)
                            <option value="{{ $t }}" @selected((int) $t === $tahun)>{{ $t }}</option>
                        @endforeach
                    </x-select-input>
                    <noscript><x-secondary-button type="submit" ikon="filter">Tampilkan</x-secondary-button></noscript>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-6 sm:grid-cols-4">
                <x-kartu>
                    <p class="text-sm text-gray-600">Usulan masuk</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $jumlahUsulan }}</p>
                </x-kartu>
                <x-kartu>
                    <p class="text-sm text-gray-600">Didanai</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $jumlahDidanai }}</p>
                </x-kartu>
                <x-kartu>
                    <p class="text-sm text-gray-600">Dana disetujui</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900"><x-rupiah :nilai="$danaDisetujui" /></p>
                </x-kartu>
                <x-kartu>
                    <p class="text-sm text-gray-600">Dana dicairkan</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900"><x-rupiah :nilai="$danaDicairkan" /></p>
                </x-kartu>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <x-kartu ikon="pie-chart" judul="Usulan per status">
                    <table class="min-w-full text-sm">
                        <tbody>
                            @foreach ($perStatus as $s)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4">{{ $s['label'] }}</td>
                                    <td class="py-2 text-end">{{ $s['jumlah'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-kartu>

                <x-kartu ikon="home" judul="Usulan per fakultas">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Fakultas</th><th class="py-2 pe-4 font-medium text-end">Usulan</th><th class="py-2 font-medium text-end">Didanai</th></tr></thead>
                        <tbody>
                            @forelse ($perFakultas as $f)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4">{{ $f['nama'] }}</td>
                                    <td class="py-2 pe-4 text-end">{{ $f['usulan'] }}</td>
                                    <td class="py-2 text-end">{{ $f['didanai'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-4 text-gray-600">Belum ada usulan pada tahun ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-kartu>
            </div>

            <x-kartu ikon="calendar" judul="Usulan per periode hibah">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Periode</th><th class="py-2 pe-4 font-medium">Skema</th><th class="py-2 pe-4 font-medium text-end">Usulan</th><th class="py-2 pe-4 font-medium text-end">Didanai</th><th class="py-2 font-medium text-end">Dana disetujui</th></tr></thead>
                        <tbody>
                            @forelse ($perPeriode as $p)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4">{{ $p['nama'] }}</td>
                                    <td class="py-2 pe-4">{{ $p['skema'] }}</td>
                                    <td class="py-2 pe-4 text-end">{{ $p['usulan'] }}</td>
                                    <td class="py-2 pe-4 text-end">{{ $p['didanai'] }}</td>
                                    <td class="py-2 text-end"><x-rupiah :nilai="$p['dana']" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-4 text-gray-600">Belum ada usulan pada tahun ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>

            <x-kartu ikon="target" judul="Luaran kegiatan yang didanai">
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Jenis luaran</th><th class="py-2 pe-4 font-medium text-end">Dijanjikan</th><th class="py-2 font-medium text-end">Terbit atau granted</th></tr></thead>
                    <tbody>
                        @forelse ($perLuaran as $l)
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="py-2 pe-4">{{ $l['label'] }}</td>
                                <td class="py-2 pe-4 text-end">{{ $l['target'] }}</td>
                                <td class="py-2 text-end">{{ $l['tercapai'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-4 text-gray-600">Belum ada luaran dari kegiatan yang didanai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
