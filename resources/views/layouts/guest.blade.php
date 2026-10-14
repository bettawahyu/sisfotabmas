<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-800 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 px-4 py-10">
            <div class="w-full sm:max-w-md">
                <div class="rounded-md bg-white p-8 shadow">
                    <a href="/" class="mb-6 flex items-center justify-center gap-3">
                        <x-application-logo class="h-10 w-10 fill-current text-indigo-600" />
                        <span class="text-xl font-bold tracking-tight text-gray-800">{{ config('app.name', 'Sisfotabmas') }}</span>
                    </a>
                    {{ $slot }}
                </div>
                <p class="mt-6 text-center text-xs text-gray-500">Sistem informasi penelitian dan pengabdian kepada masyarakat</p>
            </div>
        </div>
    </body>
</html>
