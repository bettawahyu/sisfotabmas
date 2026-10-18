@php
    use App\Models\Role;

    $pengguna = Auth::user();
    // The Pelaksanaan menu appears once one of the lecturer's activities has its first tranche paid.
    $dosenNav = $pengguna->hasRole(Role::DOSEN) ? $pengguna->dosen : null;
    $adaPelaksanaan = $dosenNav !== null && \App\Models\Proposal::dalamPelaksanaan()->timDosen($dosenNav)->exists();
    $adminLppm = $pengguna->hasRole(Role::ADMIN_LPPM);

    // Sidebar sections: heading => [[label, route, active route patterns, feather icon], ...].
    // An entry keyed by a name is a collapsible group: name => [feather icon, [links...]].
    $menu = array_filter([
        'Umum' => [
            ['Dashboard', 'dashboard', ['dashboard'], 'home'],
            ['Pengumuman', 'pengumuman.index', ['pengumuman.*'], 'volume-2'],
            ['Notifikasi', 'notifikasi.index', ['notifikasi.*'], 'bell'],
            $pengguna->can('unduh-data') ? ['Unduh Data', 'unduh-data.index', ['unduh-data.*'], 'download'] : null,
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
            'Persiapan Hibah' => ['layers', [
                ['Skema Hibah', 'admin.skema.index', ['admin.skema.*'], 'layers'],
                ['Periode Hibah', 'admin.periode.index', ['admin.periode.*'], 'calendar'],
                ['Templat Dokumen', 'admin.template.index', ['admin.template.*'], 'file'],
            ]],
            'Seleksi' => ['filter', [
                ['Seleksi Administrasi', 'admin.seleksi.index', ['admin.seleksi.*'], 'filter'],
                ['Penugasan Reviewer', 'admin.review.index', ['admin.review.*'], 'user-check'],
                ['Penetapan & SK', 'admin.penetapan.index', ['admin.penetapan.*'], 'award'],
            ]],
            'Pelaksanaan' => ['activity', [
                ['Kontrak', 'admin.kontrak.index', ['admin.kontrak.*'], 'file-plus'],
                ['Pencairan', 'pencairan.index', ['pencairan.*'], 'dollar-sign'],
                ['Pemantauan', 'pemantauan.index', ['pemantauan.*'], 'monitor'],
                ['Monev', 'admin.monev.index', ['admin.monev.*'], 'trending-up'],
            ]],
            'Laporan & Luaran' => ['clipboard', array_values(array_filter([
                ['Verifikasi Laporan', 'verifikasi.index', ['verifikasi.*'], 'clipboard'],
                $pengguna->hasRole(Role::PIMPINAN_UNIT) ? null : ['Verifikasi Karya', 'verifikasi-karya.index', ['verifikasi-karya.*'], 'shield'],
            ]))],
            'Pengaturan LPPM' => ['sliders', [
                ['Unit Pimpinan', 'admin.pimpinan-unit.index', ['admin.pimpinan-unit.*'], 'briefcase'],
                ['Kelola Pengumuman', 'kelola.pengumuman.index', ['kelola.pengumuman.*'], 'edit'],
            ]],
        ] : [],
        'Pimpinan' => $pengguna->hasRole(Role::PIMPINAN) ? [
            ['Ringkasan', 'pimpinan.index', ['pimpinan.*'], 'bar-chart-2'],
        ] : [],
        'Sistem' => array_filter([
            $pengguna->hasRole(Role::ADMIN_SISTEM) ? ['Pengguna', 'sistem.pengguna.index', ['sistem.pengguna.*'], 'users'] : null,
            'Data Master' => $pengguna->hasRole(Role::ADMIN_SISTEM) ? ['database', [
                ['Unit', 'sistem.fakultas.index', ['sistem.fakultas.*', 'sistem.prodi.*', 'sistem.impor.*'], 'home'],
                ['Rumpun Ilmu', 'sistem.rumpun-ilmu.index', ['sistem.rumpun-ilmu.*'], 'git-branch'],
                ['Bidang Fokus', 'sistem.bidang-fokus.index', ['sistem.bidang-fokus.*'], 'target'],
                ['Jenis Luaran', 'sistem.jenis-luaran.index', ['sistem.jenis-luaran.*'], 'package'],
                ['Peringkat Jurnal', 'sistem.peringkat-jurnal.index', ['sistem.peringkat-jurnal.*'], 'star'],
            ]] : null,
            $pengguna->hasRole(Role::ADMIN_SISTEM) ? ['Identitas Aplikasi', 'sistem.identitas.edit', ['sistem.identitas.*'], 'image'] : null,
            // LPPM admins find it under Pengaturan LPPM.
            $pengguna->can('kelola-pengumuman') && ! $adminLppm ? ['Kelola Pengumuman', 'kelola.pengumuman.index', ['kelola.pengumuman.*'], 'edit'] : null,
        ]),
    ]);
@endphp

{{-- Backdrop for the off-canvas sidebar on small screens --}}
<div x-show="menu" x-transition.opacity @click="menu = false" class="fixed inset-0 z-30 bg-gray-900/50 md:hidden" style="display: none;"></div>

<aside class="sidebar fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-gray-800 transition-transform duration-200 md:translate-x-0"
       :class="{ '!translate-x-0': menu }" aria-label="Menu utama">
    <a href="{{ route('dashboard') }}" class="sidebar-merek flex items-center gap-3 px-6 py-5" title="{{ config('app.name') }}">
        <x-logo-aplikasi gelap class="h-8 w-8 fill-current text-indigo-500" />
        <span class="sidebar-teks truncate text-lg font-bold tracking-tight text-white">{{ app(\App\Services\IdentitasAplikasi::class)->namaSingkat() }}</span>
    </a>

    <div class="sidebar-profil mx-4 mb-2 flex items-center gap-3 rounded-md bg-gray-900/40 p-3">
        <x-avatar :user="$pengguna" />
        <div class="sidebar-teks min-w-0">
            <p class="truncate text-sm font-semibold text-white">{{ $pengguna->name }}</p>
            <p class="truncate text-xs text-gray-500">{{ $pengguna->roles->map(fn ($r) => Role::ALL[$r->kode] ?? $r->nama)->implode(', ') ?: 'Tanpa peran' }}</p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto pb-6">
        @foreach ($menu as $judul => $daftar)
            @continue(empty($daftar))
            <p class="sidebar-heading">{{ $judul }}</p>
            <ul class="space-y-1">
                @foreach ($daftar as $kunci => $isi)
                    @continue(empty($isi))
                    @if (is_string($kunci))
                        @php([$ikonGrup, $tautan] = $isi)
                        @php($grupAktif = collect($tautan)->contains(fn ($t) => request()->routeIs(...$t[2])))
                        <li x-data="{ buka: @js($grupAktif) }">
                            {{-- On a collapsed sidebar the group icon widens it again to show the links. --}}
                            <button type="button" @click="if (sidebarCiut()) { aturSidebar(false); buka = true } else { buka = ! buka }"
                                    class="sidebar-link w-[calc(100%-2rem)]" :aria-expanded="buka" title="{{ $kunci }}">
                                <x-ikon :nama="$ikonGrup" />
                                <span class="sidebar-teks flex-1 truncate text-start">{{ $kunci }}</span>
                                <span class="sidebar-teks transition-transform" :class="buka && 'rotate-180'"><x-ikon nama="chevron-down" /></span>
                            </button>
                            <ul x-show="buka" class="sidebar-sub mt-1 space-y-1 ps-4" @unless ($grupAktif) style="display: none;" @endunless>
                                @foreach ($tautan as [$label, $rute, $pola, $ikon])
                                    <li>
                                        <x-nav-link :href="route($rute)" :active="request()->routeIs(...$pola)" :ikon="$ikon">{{ $label }}</x-nav-link>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        @php([$label, $rute, $pola, $ikon] = $isi)
                        <li>
                            <x-nav-link :href="route($rute)" :active="request()->routeIs(...$pola)" :ikon="$ikon">{{ $label }}</x-nav-link>
                        </li>
                    @endif
                @endforeach
            </ul>
        @endforeach
    </nav>
    <p class="sidebar-teks border-t border-gray-700 px-6 py-3 text-xs text-gray-400">&copy; {{ now()->year }} <a href="{{ \App\Support\Tanda::tautan() }}" target="_blank" rel="noopener noreferrer" class="hover:text-white hover:underline">{{ \App\Support\Tanda::penulis() }}</a></p>
</aside>
