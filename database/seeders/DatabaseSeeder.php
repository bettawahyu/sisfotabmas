<?php

namespace Database\Seeders;

use App\Enums\JenisKegiatan;
use App\Enums\StatusPeriode;
use App\Models\BidangFokus;
use App\Models\Fakultas;
use App\Models\Role;
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
        ];

        foreach ($accounts as $email => [$name, $role]) {
            User::factory()->create(['name' => $name, 'email' => $email])->assignRole($role);
        }

        $this->contohDataHibah();
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
}
