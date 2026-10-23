# sisfotabmas
<img width="1366" height="643" alt="LENTERA-WEB" src="https://github.com/user-attachments/assets/1a41d577-c2ee-4553-987f-0f578c7e0eb7" />


Sistem informasi penelitian dan pengabdian kepada masyarakat (PkM) dosen. Aplikasi ini mendata profil dosen, publikasi, HKI, dan paten, serta menjalankan seluruh alur hibah LPPM: pengumuman, pengajuan proposal, pengesahan, seleksi, review, penetapan dan SK, kontrak, pencairan dana, logbook, monitoring dan evaluasi (monev), sampai laporan akhir.

Dibangun dengan Laravel 13, Blade (Breeze), Tailwind CSS, dan Pest.

## Fitur

| Area | Yang bisa dilakukan |
| --- | --- |
| Profil dosen | Dosen melengkapi NIDN atau NUPTK, ID SINTA, prodi, dan data diri lain. |
| Pengumuman | Admin LPPM dan operator menerbitkan pengumuman, bisa dijadwalkan dan ditujukan ke peran tertentu. Pembaca diberi notifikasi saat pengumuman terbit. |
| Templat dokumen | Admin mengunggah templat proposal, laporan kemajuan, laporan keuangan, laporan akhir, dan logbook per skema dan tahun. |
| Skema dan periode hibah | Admin mengatur skema, periode tahunan (jadwal, plafon, kuota, aturan honor, persentase termin), dan rubrik penilaian seleksi serta monev. |
| Pengajuan proposal | Dosen mengisi identitas, tim (dosen, mahasiswa, pihak luar), mitra untuk PkM, RAB, luaran, dan substansi PDF. Anggota dosen mengonfirmasi lewat undangan. Sistem memeriksa kelengkapan, plafon, batas honor, dan kuota ketua atau anggota sebelum proposal dikirim. |
| Pengesahan | Kaprodi atau dekan mengesahkan proposal dari unitnya sendiri. Admin menetapkan prodi atau fakultas tiap pimpinan di menu Unit pimpinan. |
| Seleksi administrasi | Admin meloloskan, mengembalikan dengan batas perbaikan, atau menolak proposal. |
| Review | Admin menugaskan dua reviewer per proposal, dengan peringatan bila reviewer satu prodi dengan ketua. Reviewer menilai tanpa melihat identitas tim, dengan skor berbobot 0 sampai 100. |
| Penetapan dan SK | Admin melihat peringkat (rata-rata dua reviewer), menetapkan proposal yang didanai sesuai kuota, lalu mencatat nomor SK. Ketua bisa membaca komentar reviewer tanpa nama. |
| Kontrak dan pencairan | Admin mencatat kontrak. Bagian keuangan mencatat termin 1 (bawaan 80%) dan termin 2 (sisanya) beserta bukti transfer. Admin LPPM hanya bisa melihat. |
| Pelaksanaan | Ketua dan anggota dosen mengisi logbook. Ketua mengirim laporan kemajuan dengan laporan keuangan termin 1, melaporkan capaian luaran, lalu mengirim laporan akhir dengan laporan keuangan 100%. |
| Monev | Admin menugaskan satu reviewer monev. Reviewer menilai laporan kemajuan dan logbook tanpa identitas tim, dengan hasil Sesuai rencana, Perlu tindak lanjut, atau Bermasalah. |
| Verifikasi | Operator memverifikasi atau mengembalikan laporan akhir, dan memvalidasi atau menolak capaian luaran. |
| Pemantauan | Operator dan admin melihat logbook yang tidak diisi 14 hari, laporan kemajuan atau akhir yang terlambat, dan monev yang belum ada hasilnya. |
| Publikasi, HKI, dan paten | Dosen mencatat publikasi, HKI, dan paten beserta penulis, pencipta, atau inventornya (termasuk pihak luar). Kaprodi atau dekan memeriksa data dosen di unitnya, lalu LPPM memvalidasinya. Ketua proposal bisa menautkan capaian luaran ke data ini di halaman pelaksanaan. |
| Notifikasi | Setiap langkah alur mengirim notifikasi di aplikasi, dan juga lewat email bila `NOTIFIKASI_EMAIL=true`. |
| Ringkasan pimpinan | Pimpinan LPPM atau institusi melihat jumlah usulan dan yang didanai, dana disetujui dan dicairkan, serta luaran per tahun anggaran, dirinci per status, periode, dan fakultas. Hanya lihat. |
| Pengguna dan peran | Admin sistem membuat akun, mengubah nama, email, dan kata sandi, serta mengatur peran tiap akun. |

## Menjalankan secara lokal

Butuh PHP 8.4+, Composer, dan Node 22.

```sh
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Jalankan `php artisan schedule:work` di terminal lain agar perintah terjadwal ikut berjalan. Untuk mengulang data dari awal, jalankan `php artisan migrate:fresh --seed`.

### Akun contoh

Semua akun memakai kata sandi `password`, dan setiap akun wajib mengganti kata sandi saat pertama kali masuk.

| Email | Peran |
| --- | --- |
| `admin@example.com` | admin_lppm |
| `dosen@example.com`, `dosen2@example.com` | dosen, profil sudah lengkap |
| `reviewer@example.com` | reviewer |
| `kaprodi@example.com` | pimpinan_unit untuk prodi Teknik Informatika |
| `operator@example.com` | operator |
| `keuangan@example.com` | keuangan |
| `pimpinan@example.com` | pimpinan |
| `sistem@example.com` | admin_sistem |

Seeder juga membuat satu periode hibah Penelitian Dosen Pemula yang sedang dibuka. Sebelum menugaskan reviewer, isi dulu rubrik seleksi dan monev di halaman periode hibah (Admin > Periode hibah).

### Email

Notifikasi selalu tampil di aplikasi. Untuk juga mengirim email, isi variabel `MAIL_*` dengan SMTP kampus lalu set `NOTIFIKASI_EMAIL=true` di `.env`. Bawaannya `MAIL_MAILER=log`, jadi email hanya ditulis ke `storage/logs`.

Admin sistem bisa menutup pendaftaran mandiri di halaman Identitas Aplikasi, lalu membuat akun satu per satu atau mengimpornya dari templat Excel di menu Pengguna. Templat impor berisi kolom kata sandi awal, yang wajib diganti pengguna saat pertama kali masuk; hapus file itu setelah diimpor.

Akun yang mendaftar sendiri harus membuka tautan verifikasi di emailnya sebelum bisa memakai aplikasi. Selama `MAIL_MAILER=log`, tautan itu ada di `storage/logs/laravel.log`.

## Menjalankan di server produksi

Ubah `.env` dari contoh lokal:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://alamat-aplikasi-kampus
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
MAIL_MAILER=smtp
```

Isi juga `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, dan `MAIL_FROM_ADDRESS` dengan SMTP kampus, karena pendaftaran butuh email verifikasi. Jalankan aplikasi hanya lewat HTTPS dan arahkan web server ke folder `public/`.

```sh
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan key:generate   # hanya sekali, saat pertama memasang
php artisan migrate --force
php artisan optimize
```

Tambahkan cron `* * * * * cd /path/aplikasi && php artisan schedule:run` agar pengingat terjadwal tetap berjalan.

Jika menjalankan `php artisan db:seed --force` untuk membuat akun contoh, segera masuk dengan setiap akun contoh dan ganti kata sandinya, atau nonaktifkan akun yang tidak dipakai. Sebelum diganti, siapa pun yang tahu kata sandi `password` bisa masuk dan menggantinya lebih dulu.

## Alur hibah

1. **Pengajuan.** Dosen membuat proposal di menu Proposal, lalu mengirimnya untuk pengesahan setelah semua anggota dosen mengonfirmasi.
2. **Pengesahan.** Kaprodi atau dekan dari unit ketua mengesahkan atau mengembalikan di menu Pengesahan. Proposal lalu berstatus Diajukan.
3. **Seleksi administrasi.** Admin meloloskan (status Direview), mengembalikan dengan batas perbaikan (Dikembalikan), atau menolak.
4. **Review.** Admin menugaskan dua reviewer di Admin > Penugasan reviewer. Reviewer mengisi penilaian di menu Review.
5. **Penetapan.** Di Admin > Penetapan dan SK, admin menetapkan proposal yang didanai beserta dananya (Disetujui), sisanya Tidak didanai, lalu mencatat nomor SK.
6. **Kontrak.** Admin mencatat kontrak di Admin > Kontrak (Didanai), dan keuangan diberi tahu.
7. **Termin 1.** Keuangan mencatat pencairan termin 1 di menu Pencairan (Berjalan).
8. **Pelaksanaan.** Tim mengisi logbook di halaman pelaksanaan proposal. Ketua mengirim laporan kemajuan dan laporan keuangan termin 1 (Dimonev).
9. **Monev.** Admin menugaskan reviewer monev di Admin > Monev. Hasilnya dikirim ke ketua tanpa nama reviewer.
10. **Laporan akhir dan luaran.** Setelah hasil monev keluar, ketua melaporkan capaian luaran dan mengirim laporan akhir. Operator memeriksanya di menu Verifikasi.
11. **Termin 2.** Keuangan mencatat termin 2 bila laporan akhir sudah diverifikasi, setiap luaran wajib tervalidasi minimal submitted, dan hasil monev tidak Bermasalah. Proposal lalu Selesai.

Aturan tambahan:

- Ketua yang menunggak laporan kemajuan atau laporan akhir tidak bisa mengirim proposal baru.
- Usulan tahun berikutnya pada skema multitahun butuh hasil monev tahun sebelumnya yang tidak Bermasalah.
- Proposal yang lewat batas perbaikan ditutup otomatis.

Di kode, perpindahan status melewati `App\Services\AlurProposal` dan tercatat di `proposal_status_log`. Tiap tahap punya layanannya sendiri: `AlurReview`, `AlurPenetapan`, `AlurPencairan`, dan `AlurPelaksanaan`. Notifikasi dikirim lewat `App\Services\Pemberitahu`.

## Perintah terjadwal

| Perintah | Jadwal | Fungsi |
| --- | --- | --- |
| `proposal:tutup-lewat-batas` | setiap hari 00.10 | Menandai proposal yang lewat batas perbaikan sebagai Tidak didanai. |
| `pengumuman:kirim-notifikasi` | setiap 5 menit | Mengirim notifikasi pengumuman terjadwal yang sudah terbit. |
| `review:ingatkan` | setiap hari 07.00 | Mengingatkan reviewer tiga hari sebelum batas seleksi. |
| `laporan:ingatkan` | setiap hari 07.05 | Mengingatkan ketua 14, 7, dan 1 hari sebelum batas laporan kemajuan dan laporan akhir. |

Di server, tambahkan satu baris cron `* * * * * php /path/ke/artisan schedule:run` agar semua jadwal di atas berjalan.

## Peran

Peran disimpan di tabel `roles` dan `user_roles`, sehingga satu akun bisa punya beberapa peran. Pendaftaran mandiri otomatis mendapat peran `dosen`; peran lain diberikan admin sistem.

| Kode | Untuk |
| --- | --- |
| `dosen` | Mengajukan proposal, mengisi logbook dan laporan. |
| `reviewer` | Menilai proposal saat seleksi dan laporan saat monev. |
| `pimpinan_unit` | Kaprodi atau dekan yang mengesahkan proposal dari unitnya. |
| `admin_lppm` | Mengelola skema, periode, seleksi, review, penetapan, kontrak, dan monev. |
| `operator` | Mengelola pengumuman, memverifikasi laporan akhir dan luaran, memantau pelaksanaan. |
| `keuangan` | Mencatat pencairan termin 1 dan termin 2. |
| `pimpinan` | Pimpinan LPPM atau institusi; membaca ringkasan di menu Ringkasan. |
| `admin_sistem` | Administrator aplikasi; mengelola akun dan peran di menu Pengguna. |

Batasi rute dengan middleware `role`, misalnya `->middleware('role:admin_lppm,reviewer')`. Di kode, gunakan `$user->hasRole(Role::REVIEWER)` dan `$user->assignRole(Role::DOSEN)`.

## Tampilan

Tata letak meniru [Dash UI TailwindCSS](https://github.com/codescandy/dashui-tailwindcss) dari Codescandy (lisensi MIT): menu samping gelap, bilah atas dengan notifikasi dan foto profil, palet abu-abu dan indigo Dash UI, serta ikon [Feather](https://feathericons.com). Komponen Blade yang dipakai ulang:

- `<x-ikon nama="calendar" />` menampilkan ikon Feather.
- `<x-kartu judul="..." ikon="..." />`, `<x-input-label ... ikon="..." />`, `<x-primary-button ikon="save">`, dan `<x-tombol-tautan ikon="plus">` menerima ikon opsional.
- `<x-avatar :user="$user" />` menampilkan foto profil, atau inisial nama bila belum ada foto. Foto diunggah di halaman Profil dan disimpan di disk bawaan (privat), lalu disajikan lewat rute `foto-profil.show` untuk pengguna yang masuk.
- Tabel daftar diberi atribut `data-tabel-data` agar memakai [DataTables](https://datatables.net) (cari, urut, dan halaman di peramban). Kolom tanpa judul (aksi) tidak bisa diurutkan; baris "belum ada data" dari `@forelse` dipakai sebagai pesan tabel kosong.

## Riwayat penambahan fitur

- **#10** Laporan akhir dengan laporan keuangan 100%, capaian dan validasi luaran, menu Verifikasi, pencairan termin 2, pengingat batas laporan, blokir pengusul yang menunggak laporan, dan syarat monev untuk usulan multitahun.
- **#9** Logbook, laporan kemajuan, monev oleh satu reviewer tanpa identitas tim, dan menu Pemantauan untuk operator.
- **#8** Pencatatan kontrak, pencairan termin 1 oleh keuangan, dan pengesahan yang dibatasi ke unit tiap kaprodi atau dekan.
- **#7** Pengumuman dan templat dokumen, notifikasi di setiap langkah alur, penugasan dua reviewer dengan penilaian tanpa identitas tim, peringkat, penetapan pendanaan, dan SK.
- **Sebelumnya** Rancangan basis data, peran pengguna, profil dosen, pengajuan proposal, pengesahan, dan seleksi administrasi.

## Tes

```sh
php artisan test
vendor/bin/pint --test
```
