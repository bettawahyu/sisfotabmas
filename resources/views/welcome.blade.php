@php($identitas = app(\App\Services\IdentitasAplikasi::class))
@php($latar = $identitas->url('latar'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }}</title>
        <x-favicon />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900">
        {{-- The background image, when set, sits under a dark layer so the text stays readable. --}}
        <div class="relative flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-gray-800 to-indigo-800 bg-cover bg-center px-4 py-12"
             @if ($latar) style="background-image: url('{{ $latar }}')" @endif>
            <div class="absolute inset-0 bg-gray-900/60" aria-hidden="true"></div>

            <div class="relative w-full max-w-2xl text-center text-white">
                <x-logo-aplikasi gelap class="mx-auto h-20 w-20 fill-current text-indigo-500" />
                <h1 class="mt-6 text-3xl font-bold tracking-tight sm:text-4xl">{{ config('app.name') }}</h1>
                <p class="mt-4 whitespace-pre-line text-base text-gray-200 sm:text-lg">{{ $identitas->deskripsi() }}</p>

                @if (Route::has('login'))
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                {{ __('Dashboard') }}
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                {{ __('Log in') }}
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center rounded-md border border-white/60 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10">
                                    {{ __('Register') }}
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    </body>
</html>
