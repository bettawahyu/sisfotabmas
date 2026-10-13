<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin LPPM</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid gap-6 sm:grid-cols-3">
            <a href="{{ route('admin.skema.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                <p class="font-medium text-gray-900">Skema hibah</p>
                <p class="mt-1 text-sm text-gray-600">Jenis hibah beserta nilai bawaannya.</p>
            </a>
            <a href="{{ route('admin.periode.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                <p class="font-medium text-gray-900">Periode hibah</p>
                <p class="mt-1 text-sm text-gray-600">Pengumuman tahunan: jadwal, plafon, kuota.</p>
            </a>
            <a href="{{ route('admin.template.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                <p class="font-medium text-gray-900">Templat dokumen</p>
                <p class="mt-1 text-sm text-gray-600">Templat proposal dan laporan yang diunduh dosen.</p>
            </a>
            <a href="{{ route('kelola.pengumuman.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                <p class="font-medium text-gray-900">Pengumuman</p>
                <p class="mt-1 text-sm text-gray-600">Berita dan pengumuman untuk dosen dan reviewer.</p>
            </a>
            <a href="{{ route('admin.pimpinan-unit.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                <p class="font-medium text-gray-900">Unit pimpinan</p>
                <p class="mt-1 text-sm text-gray-600">Prodi atau fakultas yang proposalnya disahkan tiap kaprodi dan dekan.</p>
            </a>
            <a href="{{ route('admin.seleksi.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                <p class="font-medium text-gray-900">Seleksi administrasi</p>
                <p class="mt-1 text-sm text-gray-600">Proposal yang sudah disahkan dan menunggu diperiksa.</p>
            </a>
            <a href="{{ route('admin.review.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                <p class="font-medium text-gray-900">Penugasan reviewer</p>
                <p class="mt-1 text-sm text-gray-600">Dua reviewer untuk setiap proposal yang lolos administrasi, beserta nilainya.</p>
            </a>
            <a href="{{ route('admin.penetapan.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                <p class="font-medium text-gray-900">Penetapan dan SK</p>
                <p class="mt-1 text-sm text-gray-600">Peringkat nilai reviewer, proposal yang didanai, dan nomor SK.</p>
            </a>
            <a href="{{ route('admin.kontrak.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                <p class="font-medium text-gray-900">Kontrak</p>
                <p class="mt-1 text-sm text-gray-600">Nomor dan tanggal kontrak proposal yang disetujui.</p>
            </a>
            <a href="{{ route('pencairan.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                <p class="font-medium text-gray-900">Pencairan dana</p>
                <p class="mt-1 text-sm text-gray-600">Termin yang dicatat bagian keuangan (hanya lihat).</p>
            </a>
            <a href="{{ route('admin.monev.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                <p class="font-medium text-gray-900">Monev</p>
                <p class="mt-1 text-sm text-gray-600">Laporan kemajuan yang masuk, reviewer monev, dan hasilnya.</p>
            </a>
            <a href="{{ route('pemantauan.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                <p class="font-medium text-gray-900">Pemantauan</p>
                <p class="mt-1 text-sm text-gray-600">Logbook yang sepi, laporan terlambat, dan monev tanpa hasil.</p>
            </a>
        </div>
    </div>
</x-app-layout>
