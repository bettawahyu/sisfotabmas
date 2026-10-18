@php($identitas = app(\App\Services\IdentitasAplikasi::class))
@php($latar = $identitas->url('latar'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }}</title>
        <x-favicon />
        <x-meta />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
<body class="font-sans antialiased text-gray-900">
    {{-- Gambar latar (jika ada) berada di bawah lapisan gelap agar teks tetap terbaca. --}}
    <div class="relative flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-gray-800 to-indigo-800 bg-cover bg-center px-4 pb-12 pt-24 sm:py-12"
         @if ($latar) style="background-image: url('{{ $latar }}')" @endif>

        {{-- Lapisan gelap --}}
        <div class="absolute inset-0 bg-gray-900/40" aria-hidden="true"></div>

        {{-- Menu navigasi di pojok kanan atas --}}
        @if (Route::has('login'))
            <nav class="absolute right-4 top-4 z-10 flex flex-wrap items-center justify-end gap-2 sm:right-6 sm:top-6 sm:gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-indigo-700">
                        {{ __('Dashboard') }}
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-red-700">
                            {{ __('Log Out') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-indigo-700">
                        {{ __('Log in') }}
                    </a>
                    @if ($identitas->pendaftaranDibuka())
                        <a href="{{ route('register') }}" class="inline-flex items-center rounded-md border border-white/60 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-white/10">
                            {{ __('Register') }}
                        </a>
                    @endif
                @endauth
            </nav>
        @endif

        {{-- Konten utama --}}
        <div class="relative w-full max-w-2xl text-white">
            <div class="text-center">
                <x-logo-aplikasi gelap class="mx-auto h-30 w-30 fill-current text-indigo-500" />
                <h1 class="mt-6 text-3xl font-bold tracking-tight sm:text-4xl">{{ config('app.name') }}</h1>
            </div>

            {{-- Deskripsi dalam box, rata kiri-kanan (justify) --}}
            <div class="mt-6 rounded-xl border border-white/20 bg-gray-900/60 p-6 shadow-lg backdrop-blur-sm sm:p-8">
                <p class="whitespace-pre-line text-justify text-base leading-normal text-gray-100 hyphens-auto sm:text-lg" lang="{{ str_replace('_', '-', app()->getLocale()) }}">{{ $identitas->deskripsi() }}</p>
            </div>
        </div>
    </div>
</body>
</html>