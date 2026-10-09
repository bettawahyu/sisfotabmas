<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Seleksi Administrasi</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu judul="Proposal yang sudah disahkan">
                <p class="mb-4 text-sm text-gray-600">Buka proposal untuk memeriksa kelengkapan, lalu loloskan, kembalikan dengan batas perbaikan, atau tolak.</p>
                @include('proposal.partials.tabel', ['daftar' => $menunggu, 'tampilKetua' => true])
            </x-kartu>
        </div>
    </div>
</x-app-layout>
