# sisfotabmas
Aplikasi berbasis web untuk guna mendata penelitian dan pengabdian kepada masyarakat dosen yang meliputi profil dosen, repository publikasi, hki, paten, pengajuan proposal, pengumuman, monitoring dan evaluasi, ya pokoknya seputar penelitian dan pengabdian kepada masyarakat

## Menjalankan secara lokal

Butuh PHP 8.3+, Composer, dan Node 22.

```sh
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Seeder membuat tiga akun contoh dengan kata sandi `password`: `admin@example.com` (admin_lppm), `dosen@example.com` (dosen), dan `reviewer@example.com` (reviewer).

## Peran

Peran disimpan di tabel `roles` dan `user_roles`, sehingga satu akun bisa punya beberapa peran. Kode peran: `dosen`, `reviewer`, `pimpinan_unit`, `admin_lppm`, `pimpinan`, `keuangan`. Pendaftaran mandiri otomatis mendapat peran `dosen`; peran lain diberikan admin.

Batasi rute dengan middleware `role`, misalnya `->middleware('role:admin_lppm,reviewer')`. Di kode, gunakan `$user->hasRole(Role::REVIEWER)` dan `$user->assignRole(Role::DOSEN)`.

## Tes

```sh
php artisan test
vendor/bin/pint --test
```
