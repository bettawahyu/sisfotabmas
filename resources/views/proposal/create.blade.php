<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Proposal Baru · {{ $periode->nama }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <p class="text-sm text-gray-600 mb-6">Isi identitas proposal dulu. Anggota, RAB, luaran, dan berkas substansi dilengkapi di halaman berikutnya.</p>
                <form method="POST" action="{{ route('proposal.store', $periode) }}" class="space-y-6">
                    @csrf
                    @include('proposal.partials.identitas-form', ['proposal' => null])
                    <x-primary-button>Simpan draf</x-primary-button>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
