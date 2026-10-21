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
        $logo = $identitas->url('logo-gelap');
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
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <style>
        :root { --aksen: @yield('aksen', '#7b68ff'); }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            color: #f1f5f9;
            background: #161c24;
            -webkit-font-smoothing: antialiased;
        }
        .halaman {
            position: relative; display: flex; min-height: 100vh; flex-direction: column; overflow: hidden;
            background: linear-gradient(135deg, #212b36 0%, #2a2f5a 55%, #4533b8 100%);
        }
        /* A soft glow in the page's accent colour behind the illustration. */
        .halaman::before {
            content: ''; position: absolute; top: 18%; left: 50%; width: 36rem; height: 36rem; max-width: 120vw;
            transform: translateX(-50%); border-radius: 9999px;
            background: radial-gradient(circle, color-mix(in srgb, var(--aksen) 35%, transparent) 0%, transparent 65%);
            pointer-events: none;
        }
        .kepala { position: relative; display: flex; align-items: center; gap: .75rem; padding: 1.25rem 1.5rem; }
        .kepala a { display: inline-flex; align-items: center; gap: .75rem; color: inherit; text-decoration: none; }
        .kepala img, .kepala svg { width: 2.25rem; height: 2.25rem; object-fit: contain; fill: #7b68ff; flex-shrink: 0; }
        .kepala span { font-weight: 700; letter-spacing: -.01em; }
        main { position: relative; flex: 1; display: flex; align-items: center; justify-content: center; padding: 1.5rem 1rem 3rem; }
        .isi { width: 100%; max-width: 34rem; text-align: center; }
        .ikon {
            display: inline-flex; align-items: center; justify-content: center; width: 7rem; height: 7rem;
            border-radius: 9999px; color: var(--aksen);
            background: color-mix(in srgb, var(--aksen) 14%, transparent);
            border: 1px solid color-mix(in srgb, var(--aksen) 40%, transparent);
            box-shadow: 0 0 0 10px color-mix(in srgb, var(--aksen) 6%, transparent);
        }
        .ikon svg { width: 3.5rem; height: 3.5rem; }
        .kode {
            margin: 1.5rem 0 0; font-size: clamp(4.5rem, 18vw, 7.5rem); font-weight: 800; line-height: 1; letter-spacing: -.04em;
            background: linear-gradient(180deg, #ffffff 10%, var(--aksen) 120%);
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }
        .label {
            display: inline-block; margin-top: .75rem; padding: .25rem .75rem; border-radius: 9999px;
            font-size: .75rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
            color: var(--aksen); background: color-mix(in srgb, var(--aksen) 14%, transparent);
        }
        h1 { margin: 1rem 0 0; font-size: 1.5rem; font-weight: 700; letter-spacing: -.01em; }
        .pesan { margin: .75rem auto 0; max-width: 28rem; color: #c4cdd5; line-height: 1.6; }
        .saran {
            margin: 1.5rem auto 0; max-width: 28rem; padding: 1rem 1.25rem; text-align: left;
            border-radius: .75rem; border: 1px solid rgba(255, 255, 255, .12); background: rgba(22, 28, 36, .45);
            color: #dfe3e8; font-size: .875rem; line-height: 1.6;
        }
        .saran ul { margin: .25rem 0 0; padding-left: 1.25rem; }
        .tombol { display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; margin-top: 2rem; }
        .tombol a, .tombol button {
            display: inline-flex; align-items: center; gap: .5rem; padding: .625rem 1.25rem; border-radius: .375rem;
            font: inherit; font-size: .875rem; font-weight: 600; text-decoration: none; cursor: pointer;
            transition: background-color .15s, border-color .15s;
        }
        .utama { color: #fff; background: #624bff; border: 1px solid #624bff; }
        .utama:hover { background: #5340d9; border-color: #5340d9; }
        .kedua { color: #fff; background: transparent; border: 1px solid rgba(255, 255, 255, .45); }
        .kedua:hover { background: rgba(255, 255, 255, .1); }
        .tombol svg { width: 1rem; height: 1rem; }
        footer { position: relative; padding: 1.25rem; text-align: center; font-size: .75rem; color: #919eab; }
        @media (prefers-reduced-motion: no-preference) {
            .ikon { animation: muncul .5s ease-out both; }
            .ikon svg { animation: @yield('animasi', 'none'); }
        }
        @keyframes muncul { from { opacity: 0; transform: translateY(8px) scale(.95); } to { opacity: 1; transform: none; } }
        @keyframes goyang { 0%, 100% { transform: rotate(0); } 20% { transform: rotate(-8deg); } 40% { transform: rotate(6deg); } 60% { transform: rotate(-3deg); } 80% { transform: rotate(0); } }
        @keyframes putar { to { transform: rotate(360deg); } }
        @keyframes cari { 0%, 100% { transform: translate(0, 0); } 25% { transform: translate(4px, -3px); } 50% { transform: translate(-3px, 2px); } 75% { transform: translate(2px, 3px); } }
        @keyframes kedip { 0%, 100% { opacity: 1; } 50% { opacity: .55; } }
    </style>
</head>
<body>
<div class="halaman">
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
            <div class="ikon" aria-hidden="true">@yield('ikon')</div>
            <p class="kode">@yield('kode')</p>
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
