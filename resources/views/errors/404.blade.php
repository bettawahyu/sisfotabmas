@extends('errors.kerangka')

@section('kode', '404')
@section('aksen', '#7b68ff')
@section('animasi', 'cari 2.4s ease-in-out .5s infinite')
@section('label', 'Tidak ditemukan')
@section('judul', 'Halaman yang Anda cari tidak ada')
@section('pesan', 'Alamatnya mungkin salah ketik, atau halaman dan data yang dituju sudah dipindahkan atau dihapus.')

@section('ikon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="10.5" cy="10.5" r="6.5"/>
        <path d="m15.5 15.5 5 5"/>
        <path d="M8.6 8.9a2 2 0 1 1 2.6 1.9c-.5.2-.7.6-.7 1.1v.4"/>
        <path d="M10.5 14.1h.01"/>
    </svg>
@endsection

@section('saran')
    Yang bisa Anda lakukan:
    <ul>
        <li>Periksa kembali alamat yang Anda ketik.</li>
        <li>Buka halaman lewat menu, bukan dari tautan lama atau markah.</li>
    </ul>
@endsection
