<?php

use App\Models\Dosen;
use App\Models\Hki;
use App\Models\KriteriaPenilaian;
use App\Models\Laporan;
use App\Models\Paten;
use App\Models\Pengaturan;
use App\Models\Pengumuman;
use App\Models\Penilaian;
use App\Models\Proposal;
use App\Models\Publikasi;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('a lecturer reaches their publications, HKI and patents', function () {
    $dosen = Dosen::factory()->create();
    Publikasi::factory()->olehDosen($dosen)->create();
    Hki::factory()->create()->pencipta()->create(['dosen_id' => $dosen->id, 'nama' => $dosen->nama, 'urutan' => 1]);
    Paten::factory()->create()->inventor()->create(['dosen_id' => $dosen->id, 'nama' => $dosen->nama, 'urutan' => 1]);
    $dosen->riwayatPendidikan()->create(['jenjang' => 'S2', 'institusi' => 'ITB', 'tahun_lulus' => 2015]);

    expect($dosen->publikasi()->count())->toBe(1)
        ->and($dosen->hki()->count())->toBe(1)
        ->and($dosen->paten()->count())->toBe(1)
        ->and($dosen->riwayatPendidikan()->count())->toBe(1);
});

test('only entries LPPM marked valid count', function () {
    Publikasi::factory()->create();
    Publikasi::factory()->create(['status_verifikasi' => Publikasi::DISETUJUI_KAPRODI]);
    $valid = Publikasi::factory()->create();
    $valid->forceFill(['status_verifikasi' => Publikasi::VALID, 'diverifikasi_lppm_at' => now()])->save();

    expect(Publikasi::valid()->pluck('id')->all())->toBe([$valid->id])
        ->and($valid->fresh()->diverifikasi_lppm_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('an output links to its publication and records its validation', function () {
    $proposal = Proposal::factory()->create();
    $publikasi = Publikasi::factory()->create();
    $operator = User::factory()->create();
    $luaran = $proposal->luaran()->create(['kategori' => 'wajib', 'jenis' => 'artikel_jurnal', 'publikasi_id' => $publikasi->id]);

    $luaran->validasi($operator, true);

    $luaran->refresh();
    expect($luaran->publikasi->is($publikasi))->toBeTrue()
        ->and($luaran->status_validasi)->toBe('valid')
        ->and($luaran->validator->is($operator))->toBeTrue();
});

test('a review holds scores per rubric line and belongs to its proposal', function () {
    $penilaian = Penilaian::factory()->create();
    $kriteria = $penilaian->proposal->periode->kriteria()->create(['tahap' => Penilaian::SELEKSI, 'nama' => 'Metode', 'bobot' => 100]);
    $penilaian->nilai()->create(['kriteria_id' => $kriteria->id, 'skor' => 6]);
    Laporan::factory()->for($penilaian->proposal)->create();

    expect($penilaian->nilai->sole()->kriteria)->toBeInstanceOf(KriteriaPenilaian::class)
        ->and($penilaian->proposal->penilaian()->count())->toBe(1)
        ->and($penilaian->proposal->laporan()->count())->toBe(1)
        ->and($penilaian->reviewer->hasRole('reviewer'))->toBeTrue();
});

test('only current announcements are shown, pinned first', function () {
    Pengumuman::factory()->create(['judul' => 'Lama', 'berakhir_at' => now()->subHour()]);
    Pengumuman::factory()->create(['judul' => 'Nanti', 'terbit_at' => now()->addDay()]);
    Pengumuman::factory()->create(['judul' => 'Biasa']);
    Pengumuman::factory()->create(['judul' => 'Penting', 'is_pinned' => true, 'terbit_at' => now()->subWeek()]);

    expect(Pengumuman::tampil()->pluck('judul')->all())->toBe(['Penting', 'Biasa']);
});

test('settings fall back to a default until saved', function () {
    expect(Pengaturan::nilai('nama_lembaga', 'LPPM'))->toBe('LPPM');

    Pengaturan::simpan('nama_lembaga', 'LPPM Kampus');

    expect(Pengaturan::nilai('nama_lembaga'))->toBe('LPPM Kampus');
});

test('the seeder fills example data for every module', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::where('email', 'operator@example.com')->first()->hasRole('operator'))->toBeTrue()
        ->and(Publikasi::count())->toBe(1)
        ->and(Pengumuman::tampil()->count())->toBe(1)
        ->and(KriteriaPenilaian::sum('bobot'))->toBe(100);
});
