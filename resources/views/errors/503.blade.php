@extends('errors.kerangka')

@php
    // "php artisan down --retry=600" sends the expected wait in seconds.
    $tunggu = isset($exception) ? ($exception->getHeaders()['Retry-After'] ?? null) : null;
    $menit = is_numeric($tunggu) ? max(1, (int) ceil($tunggu / 60)) : null;
@endphp

@section('kode', '503')
@section('aksen', '#38bdf8')
@section('animasi', 'putar 8s linear infinite')
@section('label', 'Sedang pemeliharaan')
@section('judul', 'Sistem sedang dirawat sebentar')
@section('pesan', 'Kami sedang melakukan pemeliharaan atau pembaruan agar sistem tetap lancar. Data Anda aman.')

@section('ikon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 2.5v2.2M12 19.3v2.2M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6"/>
        <circle cx="12" cy="12" r="5.2"/>
        <circle cx="12" cy="12" r="2"/>
    </svg>
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
