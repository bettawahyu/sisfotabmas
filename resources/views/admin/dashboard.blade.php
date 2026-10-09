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
            <a href="{{ route('admin.seleksi.index') }}" class="block bg-white shadow-sm sm:rounded-lg p-6 hover:bg-gray-50">
                <p class="font-medium text-gray-900">Seleksi administrasi</p>
                <p class="mt-1 text-sm text-gray-600">Proposal yang sudah disahkan dan menunggu diperiksa.</p>
            </a>
        </div>
    </div>
</x-app-layout>
