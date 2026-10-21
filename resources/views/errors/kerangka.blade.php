{{--
    Shared frame for the custom error pages. It must still render when the app itself is broken
    (database down, assets not built), so styles are inline and every lookup falls back quietly.
--}}
@php
    $nama = config('app.name', 'Sisfotabmas');
    $logo = null;
    $favicon = null;
    $masuk = false;
    try {
        $identitas = app(\App\Services\IdentitasAplikasi::class);
        $nama = $identitas->nama();
        $logo = $identitas->url('logo');
        $favicon = $identitas->url('favicon');
    } catch (\Throwable) {
        // Keep the defaults.
    }
    try {
        $masuk = auth()->check();
    } catch (\Throwable) {
        // No session or user table available.
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('kode') · @yield('judul') · {{ $nama }}</title>
    <link rel="icon" href="{{ $favicon ?? asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fredoka:500,600,700|inter:400,500,600&display=swap" rel="stylesheet">
    <style>
        :root {
            --aksen: @yield('aksen', '#7b68ff');
            --aksen-tua: @yield('aksen_tua', '#5340d9');
            --latar-1: @yield('latar_1', '#f3f0ff');
            --latar-2: @yield('latar_2', '#e0f2fe');
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            color: #212b36; background: #ffffff; -webkit-font-smoothing: antialiased;
        }
        .halaman {
            position: relative; display: flex; min-height: 100vh; flex-direction: column; overflow: hidden;
            background:
                radial-gradient(circle at 1px 1px, rgba(33, 43, 54, .07) 1.5px, transparent 0) 0 0 / 22px 22px,
                linear-gradient(160deg, var(--latar-1) 0%, #ffffff 55%, var(--latar-2) 100%);
        }
        /* Soft coloured bubbles floating in the background. */
        .gelembung { position: absolute; border-radius: 9999px; opacity: .55; pointer-events: none; }
        .gelembung.a { width: 14rem; height: 14rem; top: -4rem; right: -3rem; background: var(--latar-2); }
        .gelembung.b { width: 9rem; height: 9rem; bottom: 4rem; left: -2.5rem; background: var(--latar-1); }
        .gelembung.c { width: 1.25rem; height: 1.25rem; top: 30%; left: 12%; background: var(--aksen); opacity: .35; }
        .gelembung.d { width: .75rem; height: .75rem; top: 20%; right: 16%; background: #fbbf24; opacity: .6; }
        .gelembung.e { width: 1rem; height: 1rem; bottom: 22%; right: 10%; background: #f472b6; opacity: .45; }
        .kepala { position: relative; display: flex; align-items: center; padding: 1.25rem 1.5rem; }
        .kepala a { display: inline-flex; align-items: center; gap: .75rem; color: inherit; text-decoration: none; }
        .kepala img, .kepala svg { width: 2.25rem; height: 2.25rem; object-fit: contain; fill: #624bff; flex-shrink: 0; }
        .kepala span { font-weight: 600; letter-spacing: -.01em; }
        main { position: relative; flex: 1; display: flex; align-items: center; justify-content: center; padding: 1rem 1rem 3rem; }
        .isi { width: 100%; max-width: 34rem; text-align: center; }
        .kode {
            display: flex; align-items: center; justify-content: center; gap: .25rem; margin: 0;
            font-family: Fredoka, Inter, ui-rounded, sans-serif; font-weight: 700; line-height: 1;
            font-size: clamp(5.5rem, 24vw, 9rem); color: var(--aksen); text-shadow: 0 .35rem 0 var(--aksen-tua);
        }
        .maskot { width: clamp(6rem, 26vw, 9.5rem); height: clamp(6rem, 26vw, 9.5rem); flex-shrink: 0; overflow: visible; }
        .label {
            display: inline-block; margin-top: 1.5rem; padding: .3rem .9rem; border-radius: 9999px;
            font-size: .75rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase;
            color: var(--aksen-tua); background: color-mix(in srgb, var(--aksen) 16%, #ffffff);
        }
        h1 { margin: .9rem 0 0; font-family: Fredoka, Inter, sans-serif; font-size: 1.75rem; font-weight: 600; color: #161c24; }
        .pesan { margin: .6rem auto 0; max-width: 28rem; color: #454f5b; line-height: 1.6; }
        .saran {
            margin: 1.5rem auto 0; max-width: 28rem; padding: 1rem 1.25rem; text-align: left;
            border-radius: 1rem; border: 2px dashed color-mix(in srgb, var(--aksen) 40%, #ffffff);
            background: rgba(255, 255, 255, .8); color: #454f5b; font-size: .875rem; line-height: 1.6;
        }
        .saran ul { margin: .25rem 0 0; padding-left: 1.25rem; }
        .tombol { display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; margin-top: 2rem; }
        .tombol a, .tombol button {
            display: inline-flex; align-items: center; gap: .5rem; padding: .7rem 1.4rem; border-radius: 9999px;
            font: inherit; font-size: .875rem; font-weight: 600; text-decoration: none; cursor: pointer;
            transition: transform .15s, box-shadow .15s, background-color .15s;
        }
        .tombol a:hover, .tombol button:hover { transform: translateY(-2px); }
        .utama { color: #fff; background: #624bff; border: 2px solid #624bff; box-shadow: 0 4px 0 #4533b8; }
        .utama:hover { box-shadow: 0 6px 0 #4533b8; }
        .kedua { color: #454f5b; background: #fff; border: 2px solid #dfe3e8; box-shadow: 0 4px 0 #dfe3e8; }
        .tombol svg { width: 1rem; height: 1rem; }
        footer { position: relative; padding: 1.25rem; text-align: center; font-size: .75rem; color: #637381; }
        @media (prefers-reduced-motion: no-preference) {
            .maskot { animation: @yield('animasi', 'melayang 3s ease-in-out infinite'); transform-origin: 50% 90%; }
            .gerak-mata { animation: lirik 3s ease-in-out infinite; }
            .gerak-putar { animation: putar 4s linear infinite; transform-origin: 60px 60px; }
            .gerak-zzz { animation: zzz 2.4s ease-in-out infinite; }
            .gerak-zzz.dua { animation-delay: .8s; }
            .gerak-tanya { animation: melayang 1.6s ease-in-out infinite; }
        }
        @keyframes melayang { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        @keyframes goyang { 0%, 60%, 100% { transform: rotate(0); } 70% { transform: rotate(-7deg); } 80% { transform: rotate(6deg); } 90% { transform: rotate(-3deg); } }
        @keyframes pusing { 0%, 100% { transform: rotate(-4deg); } 50% { transform: rotate(4deg); } }
        @keyframes napas { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.04, .97); } }
        @keyframes lirik { 0%, 20%, 100% { transform: translateX(0); } 35%, 55% { transform: translateX(-4px); } 70%, 90% { transform: translateX(4px); } }
        @keyframes putar { to { transform: rotate(360deg); } }
        @keyframes zzz { 0% { opacity: 0; transform: translate(0, 6px); } 30% { opacity: 1; } 100% { opacity: 0; transform: translate(8px, -14px); } }
    </style>
</head>
<body>
<div class="halaman">
    <span class="gelembung a"></span><span class="gelembung b"></span><span class="gelembung c"></span>
    <span class="gelembung d"></span><span class="gelembung e"></span>

    <header class="kepala">
        <a href="{{ url('/') }}">
            @if ($logo)
                <img src="{{ $logo }}" alt="Logo {{ $nama }}">
            @else
                <x-application-logo />
            @endif
            <span>{{ $nama }}</span>
        </a>
    </header>

    <main>
        <div class="isi">
            {{-- The middle digit is drawn as the page's mascot. --}}
            <p class="kode" aria-label="@yield('kode')">
                <span aria-hidden="true">@yield('angka_kiri')</span>
                <svg class="maskot" viewBox="0 0 120 120" aria-hidden="true">@yield('maskot')</svg>
                <span aria-hidden="true">@yield('angka_kanan')</span>
            </p>
            <span class="label">@yield('label')</span>
            <h1>@yield('judul')</h1>
            <p class="pesan">@yield('pesan')</p>

            @hasSection('saran')
                <div class="saran">@yield('saran')</div>
            @endif

            <div class="tombol">
                @hasSection('tombol')
                    @yield('tombol')
                @else
                    <a href="{{ $masuk ? url('/dashboard') : url('/') }}" class="utama">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
                        {{ $masuk ? 'Ke Dashboard' : 'Ke Beranda' }}
                    </a>
                    <button type="button" class="kedua" onclick="history.length > 1 ? history.back() : (location.href = '{{ url('/') }}')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5m7-7-7 7 7 7"/></svg>
                        Kembali
                    </button>
                @endif
            </div>
        </div>
    </main>

    <footer>&copy; {{ date('Y') }} {{ $nama }}</footer>
</div>
</body>
</html>
