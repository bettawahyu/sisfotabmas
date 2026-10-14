@php
    use App\Models\Role;

    $pengguna = Auth::user();
    // The Pelaksanaan menu appears once one of the lecturer's activities has its first tranche paid.
    $dosenNav = $pengguna->hasRole(Role::DOSEN) ? $pengguna->dosen : null;
    $adaPelaksanaan = $dosenNav !== null && \App\Models\Proposal::dalamPelaksanaan()->timDosen($dosenNav)->exists();
    $adminLppm = $pengguna->hasRole(Role::ADMIN_LPPM);

    // Sidebar sections: heading => [[label, route, active route patterns, feather icon], ...]
    $menu = array_filter([
        'Umum' => [
            ['Dashboard', 'dashboard', ['dashboard'], 'home'],
            ['Pengumuman', 'pengumuman.index', ['pengumuman.*'], 'volume-2'],
            ['Notifikasi', 'notifikasi.index', ['notifikasi.*'], 'bell'],
        ],
        'Dosen' => ! $pengguna->hasRole(Role::DOSEN) ? [] : array_values(array_filter([
            ['Proposal', 'proposal.index', ['proposal.*', 'undangan.*'], 'file-text'],
            $adaPelaksanaan ? ['Pelaksanaan', 'pelaksanaan.index', ['pelaksanaan.*'], 'activity'] : null,
            ['Publikasi & KI', 'karya.publikasi.index', ['karya.*'], 'book-open'],
            ['Kualifikasi Dosen', 'profil-dosen.edit', ['profil-dosen.*'], 'award'],
        ])),
        'Reviewer' => $pengguna->hasRole(Role::REVIEWER) ? [
            ['Review', 'review.index', ['review.*'], 'check-square'],
        ] : [],
        'Pimpinan Unit' => $pengguna->hasRole(Role::PIMPINAN_UNIT) ? [
            ['Pengesahan', 'pengesahan.index', ['pengesahan.*'], 'pen-tool'],
            ['Verifikasi Karya', 'verifikasi-karya.index', ['verifikasi-karya.*'], 'shield'],
        ] : [],
        'Keuangan' => $pengguna->hasRole(Role::KEUANGAN) && ! $adminLppm ? [
            ['Pencairan', 'pencairan.index', ['pencairan.*'], 'dollar-sign'],
        ] : [],
        'Operator' => $pengguna->hasRole(Role::OPERATOR) && ! $adminLppm ? [
            ['Pemantauan', 'pemantauan.index', ['pemantauan.*'], 'monitor'],
            ['Verifikasi', 'verifikasi.index', ['verifikasi.*'], 'clipboard'],
        ] : [],
        'Admin LPPM' => $adminLppm ? [
            ['Ringkasan Admin', 'admin.dashboard', ['admin.dashboard'], 'grid'],
            ['Skema Hibah', 'admin.skema.index', ['admin.skema.*'], 'layers'],
            ['Periode Hibah', 'admin.periode.index', ['admin.periode.*'], 'calendar'],
            ['Templat Dokumen', 'admin.template.index', ['admin.template.*'], 'file'],
            ['Unit Pimpinan', 'admin.pimpinan-unit.index', ['admin.pimpinan-unit.*'], 'briefcase'],
            ['Seleksi Administrasi', 'admin.seleksi.index', ['admin.seleksi.*'], 'filter'],
            ['Penugasan Reviewer', 'admin.review.index', ['admin.review.*'], 'user-check'],
            ['Penetapan & SK', 'admin.penetapan.index', ['admin.penetapan.*'], 'award'],
            ['Kontrak', 'admin.kontrak.index', ['admin.kontrak.*'], 'file-plus'],
            ['Pencairan', 'pencairan.index', ['pencairan.*'], 'dollar-sign'],
            ['Monev', 'admin.monev.index', ['admin.monev.*'], 'trending-up'],
            ['Pemantauan', 'pemantauan.index', ['pemantauan.*'], 'monitor'],
            ['Verifikasi', 'verifikasi.index', ['verifikasi.*'], 'clipboard'],
            $pengguna->hasRole(Role::PIMPINAN_UNIT) ? null : ['Verifikasi Karya', 'verifikasi-karya.index', ['verifikasi-karya.*'], 'shield'],
        ] : [],
        'Pimpinan' => $pengguna->hasRole(Role::PIMPINAN) ? [
            ['Ringkasan', 'pimpinan.index', ['pimpinan.*'], 'bar-chart-2'],
        ] : [],
        'Sistem' => array_values(array_filter([
            $pengguna->hasRole(Role::ADMIN_SISTEM) ? ['Pengguna', 'sistem.pengguna.index', ['sistem.*'], 'users'] : null,
            $pengguna->can('kelola-pengumuman') ? ['Kelola Pengumuman', 'kelola.pengumuman.index', ['kelola.pengumuman.*'], 'edit'] : null,
        ])),
    ]);
@endphp

{{-- Backdrop for the off-canvas sidebar on small screens --}}
<div x-show="menu" x-transition.opacity @click="menu = false" class="fixed inset-0 z-30 bg-gray-900/50 md:hidden" style="display: none;"></div>

<aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-gray-800 transition-transform duration-200 md:translate-x-0"
       :class="{ '!translate-x-0': menu }" aria-label="Menu utama">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-6 py-5">
        <x-application-logo class="h-8 w-8 fill-current text-indigo-500" />
        <span class="text-lg font-bold tracking-tight text-white">{{ config('app.name', 'Sisfotabmas') }}</span>
    </a>

    <div class="mx-4 mb-2 flex items-center gap-3 rounded-md bg-gray-900/40 p-3">
        <x-avatar :user="$pengguna" />
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-white">{{ $pengguna->name }}</p>
            <p class="truncate text-xs text-gray-500">{{ $pengguna->roles->map(fn ($r) => Role::ALL[$r->kode] ?? $r->nama)->implode(', ') ?: 'Tanpa peran' }}</p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto pb-6">
        @foreach ($menu as $judul => $daftar)
            @continue(empty($daftar))
            <p class="sidebar-heading">{{ $judul }}</p>
            <ul class="space-y-1">
                @foreach (array_filter($daftar) as [$label, $rute, $pola, $ikon])
                    <li>
                        <x-nav-link :href="route($rute)" :active="request()->routeIs(...$pola)" :ikon="$ikon">{{ $label }}</x-nav-link>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>
</aside>
