<?php

use App\Enums\JenisKegiatan;
use App\Enums\StatusProposal;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Mitra;
use App\Models\PeriodeHibah;
use App\Models\Proposal;
use App\Models\RiwayatKegiatan;
use App\Models\Role;
use App\Models\SkemaHibah;
use App\Models\User;

/**
 * A record outside the grants with the given lecturers, the first one leading.
 *
 * @param  list<Dosen>  $tim
 * @param  array<string, mixed>  $isian
 */
function riwayatRekap(array $tim, array $isian = [], string $verifikasi = RiwayatKegiatan::VALID): RiwayatKegiatan
{
    $riwayat = new RiwayatKegiatan($isian + [
        'jenis_kegiatan' => JenisKegiatan::Penelitian,
        'judul' => fake()->sentence(6),
        'tahun_mulai' => 2025,
        'status_kegiatan' => RiwayatKegiatan::SELESAI,
        'sumber_dana' => 'mandiri',
        'nilai_dana' => 2_000_000,
    ]);
    $riwayat->forceFill(['status_verifikasi' => $verifikasi])->save();
    foreach ($tim as $i => $dosen) {
        $riwayat->anggota()->create(['tipe' => 'dosen', 'dosen_id' => $dosen->id, 'nama' => $dosen->nama,
            'peran' => $i === 0 ? RiwayatKegiatan::KETUA : 'anggota', 'urutan' => $i + 1]);
    }

    return $riwayat;
}

/**
 * An LPPM grant in the given state and budget year.
 */
function hibahRekap(Dosen $ketua, StatusProposal $status, int $tahun = 2025, JenisKegiatan $jenis = JenisKegiatan::Penelitian): Proposal
{
    $skema = SkemaHibah::factory()->create(['jenis_kegiatan' => $jenis]);
    $periode = PeriodeHibah::factory()->create(['skema_id' => $skema->id, 'tahun_anggaran' => $tahun]);
    $proposal = Proposal::factory()->create(['periode_id' => $periode->id, 'ketua_id' => $ketua->id]);
    $proposal->forceFill(['status' => $status, 'dana_disetujui' => 10_000_000])->save();

    return $proposal;
}

function kaprodiRekap(int $prodiId): User
{
    $kaprodi = pengguna(Role::PIMPINAN_UNIT);
    $kaprodi->roles()->updateExistingPivot(Role::where('kode', Role::PIMPINAN_UNIT)->value('id'), ['prodi_id' => $prodiId]);

    return $kaprodi->fresh();
}

beforeEach(function () {
    $teknik = Fakultas::create(['kode' => 'FT', 'nama' => 'Fakultas Teknik']);
    $this->informatika = $teknik->programStudi()->create(['kode' => 'TI', 'nama' => 'Informatika', 'jenjang' => 'S1']);
    $this->elektro = $teknik->programStudi()->create(['kode' => 'TE', 'nama' => 'Elektro', 'jenjang' => 'S1']);
    $this->ani = Dosen::factory()->create(['prodi_id' => $this->informatika->id, 'nama' => 'Ani Rahma']);
    $this->budi = Dosen::factory()->create(['prodi_id' => $this->informatika->id, 'nama' => 'Budi Santoso']);
    $this->citra = Dosen::factory()->create(['prodi_id' => $this->elektro->id, 'nama' => 'Citra Dewi']);
});

test('the summary joins LPPM grants with validated records and leaves pending ones out', function () {
    hibahRekap($this->ani, StatusProposal::Selesai);
    hibahRekap($this->citra, StatusProposal::Berjalan);
    hibahRekap($this->citra, StatusProposal::Diajukan);
    riwayatRekap([$this->budi, $this->ani], ['sumber_dana' => 'kemdiktisaintek']);
    riwayatRekap([$this->budi], ['status_kegiatan' => RiwayatKegiatan::BERJALAN, 'tahun_mulai' => 2026]);
    riwayatRekap([$this->citra], [], RiwayatKegiatan::MENUNGGU);
    riwayatRekap([$this->citra], ['tahun_mulai' => 2015]);
    riwayatRekap([$this->citra], ['jenis_kegiatan' => JenisKegiatan::Pkm]);

    $halaman = $this->actingAs(pengguna(Role::ADMIN_LPPM))->get(route('rekap-riwayat.index', ['penelitian', 'tahun_dari' => 2022, 'tahun_sampai' => 2026]))->assertOk();

    expect($halaman->viewData('ringkas'))->toBe([
        'jumlah' => 4, 'selesai' => 2, 'berlangsung' => 2, 'dosen' => 3, 'dana' => 24_000_000, 'menunggu' => 1,
    ]);
    $tahun2025 = collect($halaman->viewData('perTahun')['baris'])->firstWhere('tahun', 2025);
    expect($tahun2025['sumber'])->toBe(['hibah_lppm' => 2, 'mandiri' => 0, 'kemdiktisaintek' => 1])
        ->and(collect($halaman->viewData('perTahun')['baris'])->pluck('tahun')->all())->toBe([2026, 2025, 2024, 2023, 2022]);

    $informatika = $halaman->viewData('perProdi')->firstWhere('nama', 'S1 Informatika');
    expect($informatika)->toMatchArray(['kegiatan' => 3, 'dosen_aktif' => 2, 'dosen_total' => 2, 'persen' => 100, 'dana' => 14_000_000]);

    $budi = $halaman->viewData('perDosen')->first(fn ($d) => $d['dosen']->is($this->budi));
    expect($budi)->toMatchArray(['ketua' => 2, 'anggota' => 0, 'terakhir' => 2026]);
    $halaman->assertSee('1 riwayat pada rentang ini masih menunggu verifikasi');
});

test('a unit head sees only their unit and the filters narrow the summary', function () {
    hibahRekap($this->ani, StatusProposal::Selesai);
    riwayatRekap([$this->citra]);
    riwayatRekap([$this->citra, $this->budi], ['sumber_dana' => 'industri']);

    $kaprodi = kaprodiRekap($this->informatika->id);
    $halaman = $this->actingAs($kaprodi)->get(route('rekap-riwayat.index', ['penelitian', 'tahun_dari' => 2025, 'tahun_sampai' => 2025]))->assertOk();
    expect($halaman->viewData('ringkas')['jumlah'])->toBe(2)
        ->and($halaman->viewData('ringkas')['dosen'])->toBe(2)
        ->and($halaman->viewData('perProdi')->pluck('nama')->all())->toBe(['S1 Informatika'])
        ->and($halaman->viewData('perDosen')->pluck('dosen.nama')->all())->not->toContain('Citra Dewi');

    $admin = pengguna(Role::OPERATOR);
    $saring = fn (array $q) => $this->actingAs($admin)->get(route('rekap-riwayat.index', ['penelitian', 'tahun_dari' => 2025, 'tahun_sampai' => 2025, ...$q]))->viewData('ringkas')['jumlah'];
    expect($saring([]))->toBe(3)
        ->and($saring(['sumber_dana' => 'hibah_lppm']))->toBe(1)
        ->and($saring(['sumber_dana' => 'industri']))->toBe(1)
        ->and($saring(['prodi_id' => $this->elektro->id]))->toBe(2);
});

test('the community service summary counts partners and places', function () {
    $mitra = new Mitra(['nama' => 'Kelompok Tani Makmur', 'bidang' => 'Pertanian', 'is_active' => true, 'kabupaten_kota_kode' => '3471']);
    $mitra->forceFill(['status' => Mitra::MASTER])->save();
    $hibah = hibahRekap($this->ani, StatusProposal::Dimonev, jenis: JenisKegiatan::Pkm);
    $hibah->mitra()->create(['mitra_id' => $mitra->id, 'nama_mitra' => $mitra->nama, 'jenis_mitra' => 'Kelompok masyarakat']);
    riwayatRekap([$this->budi], ['jenis_kegiatan' => JenisKegiatan::Pkm, 'kabupaten_kota_kode' => '3404'])->mitra()->attach($mitra);
    riwayatRekap([$this->citra], ['jenis_kegiatan' => JenisKegiatan::Pkm, 'kabupaten_kota_kode' => '3404']);

    $halaman = $this->actingAs(pengguna(Role::PIMPINAN))->get(route('rekap-riwayat.index', ['pkm', 'tahun_dari' => 2025, 'tahun_sampai' => 2025]))->assertOk();

    expect($halaman->viewData('jumlahMitra'))->toBe(1)
        ->and($halaman->viewData('perWilayah')->sole()['jumlah'])->toBe(3)
        ->and($halaman->viewData('perWilayah')->sole()['kabupaten']->pluck('jumlah', 'nama')->all())->toBe(['Kabupaten Sleman' => 2, 'Kota Yogyakarta' => 1]);
    $halaman->assertSee('Daerah Istimewa Yogyakarta')->assertSee('Mitra dan sebaran lokasi');
});

test('only LPPM, operators and the heads open the summary', function () {
    $this->actingAs($this->ani->user)->get(route('rekap-riwayat.index', 'penelitian'))->assertForbidden();
    $this->actingAs(pengguna(Role::KEUANGAN))->get(route('rekap-riwayat.index', 'penelitian'))->assertForbidden();
    $this->actingAs(pengguna(Role::PIMPINAN))->get(route('rekap-riwayat.index', 'penelitian'))->assertOk()->assertSee('Rekap Penelitian &amp; PkM', false);
    $this->actingAs(kaprodiRekap($this->informatika->id))->get(route('dashboard'))->assertSee(route('rekap-riwayat.index', 'penelitian'));
});
