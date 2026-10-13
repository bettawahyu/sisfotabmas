<?php

namespace Database\Seeders;

use App\Enums\JenisKegiatan;
use App\Enums\StatusPeriode;
use App\Models\BidangFokus;
use App\Models\Fakultas;
use App\Models\Pengaturan;
use App\Models\Pengumuman;
use App\Models\Penilaian;
use App\Models\PeriodeHibah;
use App\Models\Publikasi;
use App\Models\Role;
use App\Models\RumpunIlmu;
use App\Models\SkemaHibah;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Local development accounts; every password is "password".
        $accounts = [
            'admin@example.com' => ['Admin LPPM', Role::ADMIN_LPPM],
            'dosen@example.com' => ['Dosen', Role::DOSEN],
            'reviewer@example.com' => ['Reviewer', Role::REVIEWER],
            'kaprodi@example.com' => ['Kaprodi', Role::PIMPINAN_UNIT],
            'dosen2@example.com' => ['Dosen Kedua', Role::DOSEN],
            'operator@example.com' => ['Operator', Role::OPERATOR],
            'keuangan@example.com' => ['Keuangan', Role::KEUANGAN],
            'sistem@example.com' => ['Admin Sistem', Role::ADMIN_SISTEM],
        ];

        foreach ($accounts as $email => [$name, $role]) {
            User::factory()->create(['name' => $name, 'email' => $email])->assignRole($role);
        }

        $this->contohDataHibah();
        $this->contohReferensi();
    }

    /**
     * A faculty, two lecturer profiles, sample schemes and one open call, so the
     * proposal flow can be tried right after seeding.
     */
    private function contohDataHibah(): void
    {
        $prodi = Fakultas::create(['kode' => 'FT', 'nama' => 'Fakultas Teknik'])
            ->programStudi()->create(['kode' => 'TI', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);

        User::where('email', 'dosen@example.com')->first()->dosen()->create([
            'prodi_id' => $prodi->id, 'nama' => 'Dosen', 'nidn' => '0011223344', 'sinta_id' => '6000001',
        ]);
        User::where('email', 'dosen2@example.com')->first()->dosen()->create([
            'prodi_id' => $prodi->id, 'nama' => 'Dosen Kedua', 'nidn' => '0011223355', 'sinta_id' => '6000002',
        ]);

        BidangFokus::create(['jenis_kegiatan' => JenisKegiatan::Penelitian, 'kode' => 'RIP-01', 'nama' => 'Teknologi Informasi dan Komunikasi']);
        BidangFokus::create(['jenis_kegiatan' => JenisKegiatan::Pkm, 'kode' => 'PKM-01', 'nama' => 'Pemberdayaan UMKM']);

        $pemula = SkemaHibah::create([
            'kode' => 'PDP', 'nama' => 'Penelitian Dosen Pemula', 'jenis_kegiatan' => JenisKegiatan::Penelitian,
            'dana_maksimal_default' => 15_000_000, 'honor_diizinkan' => true, 'batas_honor_persen' => 10,
            'luaran_wajib' => 'Artikel di jurnal nasional terakreditasi Sinta',
        ]);
        SkemaHibah::create([
            'kode' => 'PKM-DB', 'nama' => 'PkM Desa Binaan', 'jenis_kegiatan' => JenisKegiatan::Pkm,
            'dana_maksimal_default' => 10_000_000, 'luaran_wajib' => 'Video kegiatan dan publikasi media',
        ]);

        $pemula->periode()->create([
            'tahun_anggaran' => now()->year, 'nama' => 'Penelitian Dosen Pemula '.now()->year,
            'tgl_buka' => today()->subWeek(), 'tgl_tutup' => today()->addMonth(),
            'dana_maksimal' => 15_000_000, 'honor_diizinkan' => true, 'batas_honor_persen' => 10,
            'status' => StatusPeriode::Dibuka,
        ]);
    }

    /**
     * Example science tree, rubric, settings, an announcement and one publication.
     * The rumpun ilmu rows are placeholders; replace them with the campus list.
     */
    private function contohReferensi(): void
    {
        $teknik = RumpunIlmu::create(['kode' => 'CONTOH-R1', 'nama' => 'Ilmu Teknik', 'tingkat' => RumpunIlmu::RUMPUN]);
        $elektro = $teknik->turunan()->create(['kode' => 'CONTOH-P1', 'nama' => 'Teknik Elektro dan Informatika', 'tingkat' => RumpunIlmu::POHON]);
        $elektro->turunan()->create(['kode' => 'CONTOH-C1', 'nama' => 'Teknik Informatika', 'tingkat' => RumpunIlmu::CABANG]);
        $elektro->turunan()->create(['kode' => 'CONTOH-C2', 'nama' => 'Sistem Informasi', 'tingkat' => RumpunIlmu::CABANG]);

        $periode = PeriodeHibah::first();
        foreach ([['Kebaruan dan urgensi', 30], ['Metode', 30], ['Luaran', 25], ['Kelayakan anggaran', 15]] as $i => [$nama, $bobot]) {
            $periode->kriteria()->create(['tahap' => Penilaian::SELEKSI, 'nama' => $nama, 'bobot' => $bobot, 'urutan' => $i + 1]);
        }

        Pengaturan::simpan('nama_lembaga', 'LPPM');
        Pengaturan::simpan('kontak_lppm', 'lppm@example.com');

        $admin = User::where('email', 'admin@example.com')->first();
        Pengumuman::create([
            'judul' => 'Penerimaan proposal '.$periode->nama, 'slug' => 'penerimaan-proposal-'.$periode->id,
            'isi' => 'Proposal diterima sampai '.$periode->tgl_tutup->translatedFormat('j F Y').'.',
            'kategori' => 'hibah', 'periode_id' => $periode->id, 'is_pinned' => true,
            'terbit_at' => now(), 'dibuat_oleh' => $admin->id,
        ]);

        Publikasi::factory()->olehDosen(User::where('email', 'dosen@example.com')->first()->dosen)->create();
    }
}
