<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Undangan Anggota Tim</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-kartu>
                @php($proposal = $anggota->proposal)
                <p class="text-gray-700">{{ $proposal->ketua->namaLengkap() }} mengundang Anda menjadi anggota tim proposal:</p>
                <p class="mt-2 text-lg font-medium text-gray-900">{{ $proposal->judul }}</p>
                <p class="text-sm text-gray-600">{{ $proposal->periode->nama }}</p>
                @if ($anggota->peran || $anggota->uraian_tugas)
                    <p class="mt-4 text-sm text-gray-700">Peran: {{ $anggota->peran ?: '-' }}<br>Tugas: {{ $anggota->uraian_tugas ?: '-' }}</p>
                @endif
                <p class="mt-4 text-sm text-gray-600">Status Anda saat ini: {{ $anggota->statusKonfirmasi() }}</p>

                @if ($proposal->bisaDiubah())
                    <form method="POST" action="{{ route('undangan.jawab', $anggota->token_undangan) }}" class="mt-6 flex gap-3">
                        @csrf
                        <x-primary-button name="jawaban" value="terima">Bersedia</x-primary-button>
                        <x-danger-button name="jawaban" value="tolak">Menolak</x-danger-button>
                    </form>
                @else
                    <p class="mt-6 text-sm text-gray-600">Proposal sudah dikirim, jadi jawaban tidak bisa diubah.</p>
                @endif
            </x-kartu>
        </div>
    </div>
</x-app-layout>
