@extends('errors.kerangka')

@section('kode', '403')
@section('aksen', '#f59e0b')
@section('aksen_tua', '#d97706')
@section('latar_1', '#fef3c7')
@section('latar_2', '#ffe4e6')
@section('animasi', 'goyang 2.6s ease-in-out infinite')
@section('angka_kiri', '4')
@section('angka_kanan', '3')
@section('label', 'Akses ditolak')
@section('judul', 'Eits, pintunya terkunci!')
@section('pesan', 'Akun Anda belum punya izin untuk membuka halaman ini. Si gembok sedang berjaga.')

@section('maskot')
    <path d="M38 56V42a22 22 0 0 1 44 0v14" fill="none" stroke="#d97706" stroke-width="10" stroke-linecap="round"/>
    <rect x="20" y="50" width="80" height="64" rx="20" fill="#fbbf24"/>
    <ellipse cx="44" cy="60" rx="14" ry="5" fill="#fff" opacity=".35"/>
    <path d="M38 70l11 4M82 70l-11 4" stroke="#78350f" stroke-width="3.5" stroke-linecap="round"/>
    <circle cx="46" cy="82" r="5" fill="#78350f"/>
    <circle cx="74" cy="82" r="5" fill="#78350f"/>
    <circle cx="47.5" cy="80.5" r="1.5" fill="#fff"/>
    <circle cx="75.5" cy="80.5" r="1.5" fill="#fff"/>
    <ellipse cx="33" cy="94" rx="7" ry="4" fill="#fb923c" opacity=".55"/>
    <ellipse cx="87" cy="94" rx="7" ry="4" fill="#fb923c" opacity=".55"/>
    <path d="M53 100q7-5 14 0" fill="none" stroke="#78350f" stroke-width="3.5" stroke-linecap="round"/>
@endsection

@section('saran')
    Yang bisa Anda lakukan:
    <ul>
        <li>Pastikan Anda masuk dengan akun yang benar.</li>
        <li>Bila Anda merasa seharusnya punya akses, hubungi admin LPPM agar peran akun Anda diperiksa.</li>
    </ul>
@endsection
