@extends('errors.kerangka')

@section('kode', '500')
@section('aksen', '#ff5c5c')
@section('animasi', 'kedip 1.6s ease-in-out .5s infinite')
@section('label', 'Kesalahan server')
@section('judul', 'Terjadi kesalahan di sistem kami')
@section('pesan', 'Ini bukan kesalahan Anda. Permintaan tidak bisa diselesaikan karena ada masalah di server, dan kejadiannya sudah tercatat.')

@section('ikon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3.5" width="18" height="7" rx="1.8"/>
        <rect x="3" y="13.5" width="18" height="7" rx="1.8"/>
        <path d="M7 7h.01M7 17h.01"/>
        <path d="m12.5 15 3 3m0-3-3 3"/>
        <path d="M11 7h6"/>
    </svg>
@endsection

@section('saran')
    Coba muat ulang halaman beberapa saat lagi. Bila masalah berlanjut, hubungi admin sistem dan sebutkan
    waktu kejadian: <strong>{{ now()->format('d-m-Y H:i:s') }}</strong>.
@endsection

@section('tombol')
    <button type="button" class="utama" onclick="location.reload()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.6-6.4M21 4v5h-5"/></svg>
        Muat ulang
    </button>
    <a href="{{ url('/') }}" class="kedua">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
        Ke Beranda
    </a>
@endsection
