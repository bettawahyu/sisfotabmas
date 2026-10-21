@extends('errors.kerangka')

@section('kode', '403')
@section('aksen', '#f5a524')
@section('animasi', 'goyang 1.2s ease-in-out .5s 1')
@section('label', 'Akses ditolak')
@section('judul', 'Halaman ini bukan untuk akun Anda')
@section('pesan', 'Anda sudah masuk, tetapi peran akun Anda tidak memiliki izin untuk membuka halaman atau melakukan tindakan ini.')

@section('ikon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <rect x="4" y="10.5" width="16" height="10.5" rx="2.2"/>
        <path d="M7.5 10.5V7.5a4.5 4.5 0 0 1 9 0v3"/>
        <circle cx="12" cy="15" r="1.4"/>
        <path d="M12 16.4v1.8"/>
    </svg>
@endsection

@section('saran')
    Yang bisa Anda lakukan:
    <ul>
        <li>Pastikan Anda masuk dengan akun yang benar.</li>
        <li>Bila Anda merasa seharusnya punya akses, hubungi admin LPPM agar peran akun Anda diperiksa.</li>
    </ul>
@endsection
