<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <x-favicon />
        <x-meta />

        {{-- Collapse the desktop sidebar before the first paint, so it does not jump. --}}
        <script>
            try { if (localStorage.getItem('sidebar-ciut') === '1') document.documentElement.classList.add('sidebar-ciut'); } catch (e) {}
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased" x-data="{ menu: false }" @keydown.escape.window="menu = false">
        @php($pengguna = Auth::user())
        @include('layouts.navigation')

        <div class="konten-utama flex min-h-screen min-w-0 flex-col md:ms-64">
            <!-- Top navbar -->
            <header class="sticky top-0 z-20 flex items-center justify-between bg-white px-4 py-2.5 shadow-sm sm:px-6">
                <button type="button" @click="menu = ! menu" class="rounded-md p-2 text-gray-800 hover:bg-gray-100 md:hidden" aria-label="Buka menu">
                    <x-ikon nama="menu" class="h-6 w-6" />
                </button>
                <div class="hidden items-center gap-3 md:flex">
                    <button type="button" onclick="aturSidebar()" class="rounded-md p-2 text-gray-800 hover:bg-gray-100" title="Ciutkan atau lebarkan menu" aria-label="Ciutkan atau lebarkan menu">
                        <x-ikon nama="sidebar" class="h-5 w-5" />
                    </button>
                    <span class="text-sm text-gray-500">{{ now()->translatedFormat('l, d F Y') }}</span>
                </div>

                <div class="ms-auto flex items-center gap-2">
                    @php($belumDibaca = $pengguna->unreadNotifications()->count())
                    <a href="{{ route('notifikasi.index') }}" class="relative rounded-full p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-800" title="Notifikasi">
                        <x-ikon nama="bell" class="h-5 w-5" />
                        <span class="sr-only">Notifikasi</span>
                        @if ($belumDibaca > 0)
                            <span class="absolute -end-0.5 -top-0.5 min-w-[1.25rem] rounded-full bg-red-600 px-1 text-center text-xs font-semibold leading-5 text-white">{{ $belumDibaca }}</span>
                        @endif
                    </a>

                    <x-dropdown align="right" width="w-64" contentClasses="p-2 bg-white">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 rounded-full p-0.5 hover:ring-2 hover:ring-indigo-600/30 focus:outline-none focus:ring-2 focus:ring-indigo-600/50" aria-label="Menu akun">
                                <span class="relative">
                                    <x-avatar :user="$pengguna" />
                                    <span class="absolute bottom-0 end-0 h-3 w-3 rounded-full border-2 border-white bg-green-600"></span>
                                </span>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="flex items-center gap-3 px-3 pb-3 pt-2">
                                <x-avatar :user="$pengguna" />
                                <div class="min-w-0 leading-tight">
                                    <p class="truncate text-sm font-semibold text-gray-800">{{ $pengguna->name }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ $pengguna->email }}</p>
                                </div>
                            </div>
                            <div class="mb-2 border-b border-gray-300"></div>
                            @if ($pengguna->hasRole(\App\Models\Role::DOSEN))
                                <x-dropdown-link :href="route('profil-dosen.edit')" ikon="award">Profil Dosen</x-dropdown-link>
                            @endif
                            <x-dropdown-link :href="route('profile.edit')" ikon="user">Akun</x-dropdown-link>
                            <x-dropdown-link :href="route('notifikasi.index')" ikon="bell">Notifikasi</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" ikon="power"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </header>

            <!-- Page Heading -->
            @isset($header)
                <div class="px-4 pt-6 sm:px-6 lg:px-8">
                    <div class="border-b border-gray-300 pb-4">
                        {{ $header }}
                    </div>
                </div>
            @endisset

            <!-- Page Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="px-6 py-4 text-center text-xs text-gray-500">
                &copy; {{ now()->year }} {{ config('app.name') }} · LPPM
            </footer>
        </div>

        <x-notifikasi />
        <x-konfirmasi />
    </body>
</html>
