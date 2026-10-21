@extends('errors.kerangka')

@php
    // "php artisan down --retry=600" sends the expected wait in seconds.
    $tunggu = isset($exception) ? ($exception->getHeaders()['Retry-After'] ?? null) : null;
    $menit = is_numeric($tunggu) ? max(1, (int) ceil($tunggu / 60)) : null;
@endphp

@section('kode', '503')
@section('aksen', '#0ea5e9')
@section('aksen_tua', '#0369a1')
@section('latar_1', '#e0f2fe')
@section('latar_2', '#dcfce7')
@section('animasi', 'napas 3s ease-in-out infinite')
@section('angka_kiri', '5')
@section('angka_kanan', '3')
@section('label', 'Sedang pemeliharaan')
@section('judul', 'Sistem lagi istirahat sebentar')
@section('pesan', 'Kami sedang merapikan dan memperbarui sistem. Data Anda aman, sebentar lagi kami bangun kembali.')

@section('maskot')
    <circle cx="60" cy="66" r="46" fill="#38bdf8"/>
    <path d="M18 50Q40 8 92 26L110 14Q78 -2 18 50Z" fill="#6366f1"/>
    <path d="M16 52Q48 26 96 34" fill="none" stroke="#fff" stroke-width="7" stroke-linecap="round"/>
    <circle cx="111" cy="13" r="7" fill="#fff"/>
    <path d="M36 66q8 8 16 0M68 66q8 8 16 0" fill="none" stroke="#0c4a6e" stroke-width="3.5" stroke-linecap="round"/>
    <ellipse cx="32" cy="82" rx="7" ry="4" fill="#f472b6" opacity=".5"/>
    <ellipse cx="88" cy="82" rx="7" ry="4" fill="#f472b6" opacity=".5"/>
    <ellipse cx="60" cy="90" rx="4" ry="5" fill="#0c4a6e"/>
    <text class="gerak-zzz" x="102" y="40" font-family="Fredoka, sans-serif" font-size="24" font-weight="700" fill="#0369a1">Z</text>
    <text class="gerak-zzz dua" x="116" y="22" font-family="Fredoka, sans-serif" font-size="17" font-weight="700" fill="#0369a1">z</text>
@endsection

@section('saran')
    @if ($menit)
        Perkiraan selesai dalam sekitar <strong>{{ $menit }} menit</strong>. Silakan coba lagi setelahnya.
    @else
        Silakan coba lagi dalam beberapa menit. Halaman ini tidak perlu ditutup, cukup muat ulang nanti.
    @endif
@endsection

@section('tombol')
    <button type="button" class="utama" onclick="location.reload()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.6-6.4M21 4v5h-5"/></svg>
        Coba lagi
    </button>
@endsection
