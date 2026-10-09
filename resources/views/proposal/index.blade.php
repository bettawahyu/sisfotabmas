<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Proposal Saya</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-pesan />

            @unless ($dosen)
                <x-kartu>
                    <p class="text-sm text-gray-700">Anda belum memiliki profil dosen. <a class="underline text-indigo-600" href="{{ route('profil-dosen.edit') }}">Lengkapi profil dosen</a> untuk mulai mengajukan proposal.</p>
                </x-kartu>
            @endunless

            <x-kartu judul="Hibah yang sedang dibuka">
                @forelse ($periodeDibuka as $periode)
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 py-3 last:border-0">
                        <div>
                            <p class="font-medium text-gray-900">{{ $periode->nama }}</p>
                            <p class="text-sm text-gray-600">{{ $periode->skema->jenis_kegiatan->label() }} · plafon <x-rupiah :nilai="$periode->dana_maksimal" /> · ditutup {{ $periode->tgl_tutup->translatedFormat('d M Y') }}</p>
                        </div>
                        @if ($dosen)
                            <a href="{{ route('proposal.create', $periode) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Buat proposal</a>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Belum ada periode hibah yang dibuka.</p>
                @endforelse
            </x-kartu>

            <x-kartu judul="Sebagai ketua">
                @include('proposal.partials.tabel', ['daftar' => $sebagaiKetua])
            </x-kartu>

            <x-kartu judul="Sebagai anggota">
                @forelse ($sebagaiAnggota as $proposal)
                    @php($saya = $proposal->anggota->first())
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 py-3 last:border-0">
                        <div>
                            <a href="{{ route('proposal.show', $proposal) }}" class="font-medium text-indigo-700 hover:underline">{{ $proposal->judul }}</a>
                            <p class="text-sm text-gray-600">Ketua {{ $proposal->ketua->namaLengkap() }} · {{ $proposal->periode->nama }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <x-status-proposal :proposal="$proposal" />
                            @if ($saya && $saya->statusKonfirmasi() === 'Menunggu' && $proposal->bisaDiubah())
                                <a href="{{ route('undangan.show', $saya->token_undangan) }}" class="text-sm font-medium text-indigo-700 underline">Jawab undangan</a>
                            @elseif ($saya)
                                <span class="text-sm text-gray-600">{{ $saya->statusKonfirmasi() }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-600">Belum ada.</p>
                @endforelse
            </x-kartu>
        </div>
    </div>
</x-app-layout>
