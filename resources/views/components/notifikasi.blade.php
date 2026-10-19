@php
    // Breeze's profile pages flash short codes instead of sentences.
    $kode = [
        'profile-updated' => 'Profil disimpan.',
        'password-updated' => 'Kata sandi diperbarui.',
        'foto-updated' => 'Foto disimpan.',
        'foto-deleted' => 'Foto dihapus.',
        'verification-link-sent' => 'Tautan verifikasi baru sudah dikirim ke email Anda.',
    ];

    $daftar = [];
    if ($status = session('status')) {
        $daftar[] = ['jenis' => 'sukses', 'judul' => 'Berhasil', 'pesan' => [$kode[$status] ?? $status]];
    }
    if ($peringatan = session('peringatan')) {
        $daftar[] = ['jenis' => 'peringatan', 'judul' => 'Perhatian', 'pesan' => (array) $peringatan];
    }
    if ($gagal = session('gagal')) {
        $daftar[] = ['jenis' => 'gagal', 'judul' => 'Gagal', 'pesan' => (array) $gagal];
    }

    $salah = collect($errors->getBags())->flatMap(fn ($bag) => $bag->messages());
    if ($salah->isNotEmpty()) {
        $jumlah = $salah->flatten()->count();
        $daftar[] = [
            'jenis' => 'gagal',
            'judul' => $jumlah > 1 ? "Ada {$jumlah} isian yang perlu diperbaiki" : 'Ada isian yang perlu diperbaiki',
            'pesan' => $salah->flatten()->unique()->take(5)->values()->all(),
            'kolom' => $salah->keys()->values()->all(),
        ];
    }

    $gaya = [
        'sukses' => ['ikon' => 'check-circle', 'kelas' => 'border-green-200 bg-green-50 text-green-800'],
        'peringatan' => ['ikon' => 'alert-triangle', 'kelas' => 'border-amber-200 bg-amber-50 text-amber-800'],
        'gagal' => ['ikon' => 'x-circle', 'kelas' => 'border-red-200 bg-red-50 text-red-800'],
    ];
@endphp

@if ($daftar)
    <div class="pointer-events-none fixed inset-x-4 top-4 z-50 flex flex-col gap-3 sm:inset-x-auto sm:end-4 sm:w-96" data-notifikasi>
        @foreach ($daftar as $n)
            @php($kolom = $n['kolom'] ?? [])
            <div x-data="{ buka: true }" x-show="buka" x-transition.opacity.duration.300ms
                 @if ($n['jenis'] === 'sukses') x-init="setTimeout(() => buka = false, 6000)" @endif
                 @if ($kolom) x-init="tandaiKolomSalah(@js($kolom))" @endif
                 role="{{ $n['jenis'] === 'sukses' ? 'status' : 'alert' }}"
                 class="pointer-events-auto flex items-start gap-3 rounded-md border p-4 text-sm shadow-lg {{ $gaya[$n['jenis']]['kelas'] }}">
                <x-ikon :nama="$gaya[$n['jenis']]['ikon']" class="mt-0.5 h-5 w-5" />
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ $n['judul'] }}</p>
                    @if (count($n['pesan']) === 1 && ! $kolom)
                        <p class="mt-1">{{ $n['pesan'][0] }}</p>
                    @else
                        <ul class="mt-1 list-disc space-y-1 ps-5">
                            @foreach ($n['pesan'] as $pesan)
                                <li>{{ $pesan }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($kolom)
                        <button type="button" @click="tandaiKolomSalah(@js($kolom))" class="mt-2 font-semibold underline">Tunjukkan kolomnya</button>
                    @endif
                </div>
                <button type="button" @click="buka = false" class="shrink-0 rounded p-0.5 opacity-70 hover:opacity-100" aria-label="Tutup notifikasi">
                    <x-ikon nama="x" />
                </button>
            </div>
        @endforeach
    </div>
@endif
