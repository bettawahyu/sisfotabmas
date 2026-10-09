<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pengesahan Proposal</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu judul="Menunggu pengesahan">
                @include('proposal.partials.tabel', ['daftar' => $menunggu, 'tampilKetua' => true])
            </x-kartu>
        </div>
    </div>
</x-app-layout>
