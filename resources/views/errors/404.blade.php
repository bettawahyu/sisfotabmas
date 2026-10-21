@extends('errors.kerangka')

@section('kode', '404')
@section('aksen', '#7b68ff')
@section('aksen_tua', '#5340d9')
@section('latar_1', '#ede9fe')
@section('latar_2', '#dbeafe')
@section('angka_kiri', '4')
@section('angka_kanan', '4')
@section('label', 'Tidak ditemukan')
@section('judul', 'Ups, halamannya tidak ketemu!')
@section('pesan', 'Kami sudah mencari ke mana-mana, tetapi halaman ini tidak ada. Mungkin alamatnya salah ketik, atau halamannya sudah dipindahkan.')

@section('maskot')
    <circle cx="60" cy="64" r="48" fill="#a78bfa"/>
    <ellipse cx="42" cy="34" rx="14" ry="7" fill="#fff" opacity=".35" transform="rotate(-25 42 34)"/>
    <g class="gerak-mata">
        <circle cx="44" cy="60" r="11" fill="#fff"/>
        <circle cx="76" cy="60" r="11" fill="#fff"/>
        <circle cx="44" cy="61" r="5.5" fill="#161c24"/>
        <circle cx="76" cy="61" r="5.5" fill="#161c24"/>
        <circle cx="46" cy="59" r="1.8" fill="#fff"/>
        <circle cx="78" cy="59" r="1.8" fill="#fff"/>
    </g>
    <ellipse cx="32" cy="80" rx="7" ry="4" fill="#f472b6" opacity=".55"/>
    <ellipse cx="88" cy="80" rx="7" ry="4" fill="#f472b6" opacity=".55"/>
    <ellipse cx="60" cy="88" rx="5" ry="6" fill="#4c1d95"/>
    <text class="gerak-tanya" x="100" y="24" font-family="Fredoka, sans-serif" font-size="36" font-weight="700" fill="#5340d9">?</text>
@endsection

@section('saran')
    Yang bisa Anda lakukan:
    <ul>
        <li>Periksa kembali alamat yang Anda ketik.</li>
        <li>Buka halaman lewat menu, bukan dari tautan lama atau markah.</li>
    </ul>
@endsection
