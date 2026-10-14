@php
    $pengguna = Auth::user();
    $peran = $pengguna->roles->map(fn ($r) => \App\Models\Role::ALL[$r->kode] ?? $r->nama);
    $statistik = [
        ['Notifikasi belum dibaca', $pengguna->unreadNotifications()->count(), 'bell', route('notifikasi.index')],
        ['Pengumuman terbaru', $pengumuman->count(), 'volume-2', route('pengumuman.index')],
        ['Peran akun', $peran->count(), 'shield', route('profile.edit')],
    ];
@endphp
<x-app-layout>
    {{-- Dash UI style: indigo band with the stat cards overlapping it --}}
    <div class="bg-indigo-600 px-4 pb-20 pt-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-avatar :user="$pengguna" ukuran="h-14 w-14 text-lg" class="border-2 border-white/60" />
                <div>
                    <h2 class="text-xl font-semibold text-white">{{ __('Dashboard') }}</h2>
                    <p class="text-sm text-indigo-100">Selamat datang, {{ $pengguna->name }}.</p>
                </div>
            </div>
            <x-tombol-tautan :href="route('profile.edit')" varian="kedua" ikon="user">Profil saya</x-tombol-tautan>
        </div>
    </div>

    <div class="-mt-14 px-4 pb-6 sm:px-6 lg:px-8 space-y-6">
        <div class="grid gap-6 sm:grid-cols-3">
            @foreach ($statistik as [$label, $jumlah, $ikon, $tautan])
                <a href="{{ $tautan }}" class="block rounded-md bg-white p-5 shadow transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-gray-800">{{ $label }}</h4>
                        <span class="flex h-10 w-10 items-center justify-center rounded-md bg-indigo-600/10 text-indigo-600"><x-ikon :nama="$ikon" class="h-5 w-5" /></span>
                    </div>
                    <p class="mt-4 text-2xl font-bold text-gray-800">{{ $jumlah }}</p>
                </a>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <x-kartu ikon="volume-2" judul="Pengumuman terbaru" class="lg:col-span-2">
                <ul class="divide-y divide-gray-300">
                    @forelse ($pengumuman as $p)
                        <li class="py-3 flex items-baseline justify-between gap-4">
                            <a href="{{ route('pengumuman.show', $p) }}" class="text-sm font-medium text-indigo-600 hover:underline">{{ $p->judul }}</a>
                            <span class="shrink-0 text-xs text-gray-500">{{ $p->terbit_at->translatedFormat('d M Y') }}</span>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-gray-600">Belum ada pengumuman.</li>
                    @endforelse
                </ul>
                <a href="{{ route('pengumuman.index') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:underline">Semua pengumuman <x-ikon nama="arrow-right" class="h-3.5 w-3.5" /></a>
            </x-kartu>

            <x-kartu ikon="user" judul="Akun Anda">
                <div class="flex items-center gap-3">
                    <x-avatar :user="$pengguna" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-800">{{ $pengguna->name }}</p>
                        <p class="truncate text-xs text-gray-500">{{ $pengguna->email }}</p>
                    </div>
                </div>
                <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Roles') }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @forelse ($peran as $nama)
                        <span class="rounded-full bg-indigo-600/10 px-3 py-1 text-xs font-medium text-indigo-700">{{ $nama }}</span>
                    @empty
                        <span class="text-sm text-gray-600">-</span>
                    @endforelse
                </div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
