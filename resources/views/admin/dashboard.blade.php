<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin LPPM</h2>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            <a href="{{ route('admin.skema.index') }}" class="flex items-start gap-4 rounded-md bg-white p-5 shadow transition hover:shadow-md">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon nama="layers" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-semibold text-gray-800">Skema hibah</span>
                    <span class="mt-1 block text-sm text-gray-600">Jenis hibah beserta nilai bawaannya.</span>
                </span>
            </a>
            <a href="{{ route('admin.periode.index') }}" class="flex items-start gap-4 rounded-md bg-white p-5 shadow transition hover:shadow-md">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon nama="calendar" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-semibold text-gray-800">Periode hibah</span>
                    <span class="mt-1 block text-sm text-gray-600">Pengumuman tahunan: jadwal, plafon, kuota.</span>
                </span>
            </a>
            <a href="{{ route('admin.template.index') }}" class="flex items-start gap-4 rounded-md bg-white p-5 shadow transition hover:shadow-md">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon nama="file" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-semibold text-gray-800">Templat dokumen</span>
                    <span class="mt-1 block text-sm text-gray-600">Templat proposal dan laporan yang diunduh dosen.</span>
                </span>
            </a>
            <a href="{{ route('kelola.pengumuman.index') }}" class="flex items-start gap-4 rounded-md bg-white p-5 shadow transition hover:shadow-md">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon nama="volume-2" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-semibold text-gray-800">Pengumuman</span>
                    <span class="mt-1 block text-sm text-gray-600">Berita dan pengumuman untuk dosen dan reviewer.</span>
                </span>
            </a>
            <a href="{{ route('admin.pimpinan-unit.index') }}" class="flex items-start gap-4 rounded-md bg-white p-5 shadow transition hover:shadow-md">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon nama="briefcase" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-semibold text-gray-800">Unit pimpinan</span>
                    <span class="mt-1 block text-sm text-gray-600">Prodi atau fakultas yang proposalnya disahkan tiap kaprodi dan dekan.</span>
                </span>
            </a>
            <a href="{{ route('verifikasi-karya.index') }}" class="flex items-start gap-4 rounded-md bg-white p-5 shadow transition hover:shadow-md">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon nama="shield" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-semibold text-gray-800">Verifikasi publikasi, HKI, dan paten</span>
                    <span class="mt-1 block text-sm text-gray-600">Data dosen yang sudah disetujui kaprodi dan menunggu validasi LPPM.</span>
                </span>
            </a>
            <a href="{{ route('admin.seleksi.index') }}" class="flex items-start gap-4 rounded-md bg-white p-5 shadow transition hover:shadow-md">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon nama="filter" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-semibold text-gray-800">Seleksi administrasi</span>
                    <span class="mt-1 block text-sm text-gray-600">Proposal yang sudah disahkan dan menunggu diperiksa.</span>
                </span>
            </a>
            <a href="{{ route('admin.review.index') }}" class="flex items-start gap-4 rounded-md bg-white p-5 shadow transition hover:shadow-md">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon nama="user-check" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-semibold text-gray-800">Penugasan reviewer</span>
                    <span class="mt-1 block text-sm text-gray-600">Dua reviewer untuk setiap proposal yang lolos administrasi, beserta nilainya.</span>
                </span>
            </a>
            <a href="{{ route('admin.penetapan.index') }}" class="flex items-start gap-4 rounded-md bg-white p-5 shadow transition hover:shadow-md">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon nama="award" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-semibold text-gray-800">Penetapan dan SK</span>
                    <span class="mt-1 block text-sm text-gray-600">Peringkat nilai reviewer, proposal yang didanai, dan nomor SK.</span>
                </span>
            </a>
            <a href="{{ route('admin.kontrak.index') }}" class="flex items-start gap-4 rounded-md bg-white p-5 shadow transition hover:shadow-md">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon nama="file-plus" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-semibold text-gray-800">Kontrak</span>
                    <span class="mt-1 block text-sm text-gray-600">Nomor dan tanggal kontrak proposal yang disetujui.</span>
                </span>
            </a>
            <a href="{{ route('pencairan.index') }}" class="flex items-start gap-4 rounded-md bg-white p-5 shadow transition hover:shadow-md">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon nama="dollar-sign" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-semibold text-gray-800">Pencairan dana</span>
                    <span class="mt-1 block text-sm text-gray-600">Termin yang dicatat bagian keuangan (hanya lihat).</span>
                </span>
            </a>
            <a href="{{ route('admin.monev.index') }}" class="flex items-start gap-4 rounded-md bg-white p-5 shadow transition hover:shadow-md">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon nama="trending-up" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-semibold text-gray-800">Monev</span>
                    <span class="mt-1 block text-sm text-gray-600">Laporan kemajuan yang masuk, reviewer monev, dan hasilnya.</span>
                </span>
            </a>
            <a href="{{ route('pemantauan.index') }}" class="flex items-start gap-4 rounded-md bg-white p-5 shadow transition hover:shadow-md">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon nama="monitor" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-semibold text-gray-800">Pemantauan</span>
                    <span class="mt-1 block text-sm text-gray-600">Logbook yang sepi, laporan terlambat, dan monev tanpa hasil.</span>
                </span>
            </a>
            <a href="{{ route('verifikasi.index') }}" class="flex items-start gap-4 rounded-md bg-white p-5 shadow transition hover:shadow-md">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon nama="clipboard" class="h-5 w-5" /></span>
                <span>
                    <span class="block font-semibold text-gray-800">Verifikasi</span>
                    <span class="mt-1 block text-sm text-gray-600">Laporan akhir dan capaian luaran yang menunggu diperiksa.</span>
                </span>
            </a>
        </div>
    </div>
</x-app-layout>
