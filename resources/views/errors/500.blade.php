@extends('errors.kerangka')

@section('kode', '500')
@section('aksen', '#f43f5e')
@section('aksen_tua', '#be123c')
@section('latar_1', '#ffe4e6')
@section('latar_2', '#fef3c7')
@section('animasi', 'pusing 1.4s ease-in-out infinite')
@section('angka_kiri', '5')
@section('angka_kanan', '0')
@section('label', 'Kesalahan server')
@section('judul', 'Aduh, server kami pusing!')
@section('pesan', 'Ini bukan salah Anda. Ada yang tidak beres di server, dan kejadiannya sudah kami catat.')

@section('maskot')
    <circle cx="60" cy="64" r="48" fill="#fb7185"/>
    <ellipse cx="42" cy="34" rx="14" ry="7" fill="#fff" opacity=".35" transform="rotate(-25 42 34)"/>
    <g transform="rotate(35 86 28)">
        <rect x="70" y="21" width="32" height="14" rx="7" fill="#fde68a"/>
        <rect x="81" y="21" width="10" height="14" fill="#fcd34d"/>
    </g>
    <path d="M37 52l14 14M51 52L37 66M69 52l14 14M83 52L69 66" stroke="#881337" stroke-width="4" stroke-linecap="round"/>
    <ellipse cx="32" cy="80" rx="7" ry="4" fill="#fde68a" opacity=".6"/>
    <ellipse cx="88" cy="80" rx="7" ry="4" fill="#fde68a" opacity=".6"/>
    <path d="M42 90q4.5-6 9 0t9 0t9 0t9 0" fill="none" stroke="#881337" stroke-width="3.5" stroke-linecap="round"/>
    <g class="gerak-putar" fill="#facc15">
        <path d="M60 0l2.5 6 6 2.5-6 2.5-2.5 6-2.5-6-6-2.5 6-2.5z"/>
        <path d="M112 70l2 5 5 2-5 2-2 5-2-5-5-2 5-2z"/>
        <path d="M8 74l2 5 5 2-5 2-2 5-2-5-5-2 5-2z"/>
    </g>
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
