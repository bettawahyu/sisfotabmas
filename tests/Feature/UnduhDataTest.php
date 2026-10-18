<?php

use App\Enums\StatusProposal;
use App\Models\Dosen;
use App\Models\Fakultas;
use App\Models\Hki;
use App\Models\Paten;
use App\Models\PeriodeHibah;
use App\Models\Proposal;
use App\Models\Publikasi;
use App\Models\Role;
use App\Models\SkemaHibah;
use App\Models\User;
use App\Services\UnduhData;

function pengunduh(string $peran): User
{
    $user = User::factory()->create();
    $user->assignRole($peran);

    return $user;
}

beforeEach(function () {
    $teknik = Fakultas::create(['kode' => 'FT', 'nama' => 'Fakultas Teknik']);
    $this->informatika = $teknik->programStudi()->create(['kode' => 'TI', 'nama' => 'Informatika', 'jenjang' => 'S1']);
    $this->elektro = $teknik->programStudi()->create(['kode' => 'TE', 'nama' => 'Elektro', 'jenjang' => 'S1']);
    $this->dosenTi = Dosen::factory()->create(['prodi_id' => $this->informatika->id, 'nama' => 'Ani']);
    $this->dosenTe = Dosen::factory()->create(['prodi_id' => $this->elektro->id, 'nama' => 'Budi']);
});

test('the roles that may download see the page and get Excel files', function (string $peran) {
    $user = pengunduh($peran);

    $this->actingAs($user)->get(route('unduh-data.index'))->assertOk()->assertSee('Unduh Excel');
    $this->actingAs($user)->get(route('dashboard'))->assertSee(route('unduh-data.index'), false);

    $proposal = $this->actingAs($user)->get(route('unduh-data.proposal'));
    $proposal->assertOk();
    expect($proposal->headers->get('content-disposition'))->toContain('.xlsx');

    $karya = $this->actingAs($user)->get(route('unduh-data.karya', ['hanya_valid' => 1]));
    $karya->assertOk();
    expect($karya->headers->get('content-disposition'))->toContain('publikasi-ki');
})->with([Role::ADMIN_LPPM, Role::OPERATOR, Role::KEUANGAN, Role::PIMPINAN, Role::PIMPINAN_UNIT]);

test('lecturers and reviewers cannot download', function (string $peran) {
    $user = pengunduh($peran);

    $this->actingAs($user)->get(route('unduh-data.index'))->assertForbidden();
    $this->actingAs($user)->get(route('unduh-data.proposal'))->assertForbidden();
    $this->actingAs($user)->get(route('unduh-data.karya'))->assertForbidden();
})->with([Role::DOSEN, Role::REVIEWER]);

test('proposals are filtered by year, scheme and the lead\'s programme, leaving drafts out', function () {
    $pemula = SkemaHibah::factory()->create(['nama' => 'Penelitian Dosen Pemula']);
    $pkm = SkemaHibah::factory()->pkm()->create();
    $periode2026 = PeriodeHibah::factory()->for($pemula, 'skema')->create(['tahun_anggaran' => 2026]);
    $periode2025 = PeriodeHibah::factory()->for($pemula, 'skema')->create(['tahun_anggaran' => 2025]);
    $periodePkm = PeriodeHibah::factory()->for($pkm, 'skema')->create(['tahun_anggaran' => 2026]);

    $diajukan = ['status' => StatusProposal::Diajukan];
    Proposal::factory()->for($periode2026, 'periode')->for($this->dosenTi, 'ketua')->create(['judul' => 'Sistem pakar', ...$diajukan]);
    Proposal::factory()->for($periode2025, 'periode')->for($this->dosenTi, 'ketua')->create(['judul' => 'Lama', ...$diajukan]);
    Proposal::factory()->for($periodePkm, 'periode')->for($this->dosenTi, 'ketua')->create(['judul' => 'Desa digital', ...$diajukan]);
    Proposal::factory()->for($periode2026, 'periode')->for($this->dosenTe, 'ketua')->create(['judul' => 'Panel surya', ...$diajukan]);
    Proposal::factory()->for($periode2026, 'periode')->for($this->dosenTi, 'ketua')->create(['judul' => 'Masih draf']);

    $unduh = app(UnduhData::class);
    expect($unduh->proposal([])->pluck('Judul')->all())->toHaveCount(4)->not->toContain('Masih draf');

    $baris = $unduh->proposal(['tahun' => 2026, 'skema_id' => $pemula->id, 'prodi_id' => $this->informatika->id]);
    expect($baris)->toHaveCount(1)
        ->and($baris[0]['Judul'])->toBe('Sistem pakar')
        ->and($baris[0]['Skema'])->toBe('Penelitian Dosen Pemula')
        ->and($baris[0]['Program studi'])->toBe('Informatika')
        ->and($baris[0]['Status'])->toBe('Diajukan');
});

test('publications and IP come as one sheet each, filtered by year, type and programme', function () {
    $valid = function ($karya) {
        $karya->forceFill(['status_verifikasi' => Hki::VALID])->save();

        return $karya;
    };

    $valid(Publikasi::factory()->olehDosen($this->dosenTi)->create(['judul' => 'Artikel TI', 'tahun' => 2026, 'jenis' => 'jurnal_internasional']));
    $valid(Publikasi::factory()->olehDosen($this->dosenTi)->create(['judul' => 'Prosiding TI', 'tahun' => 2026, 'jenis' => 'prosiding_nasional']));
    $valid(Publikasi::factory()->olehDosen($this->dosenTe)->create(['judul' => 'Artikel TE', 'tahun' => 2026]));
    $valid(Publikasi::factory()->olehDosen($this->dosenTi)->create(['judul' => 'Artikel lama', 'tahun' => 2024]));
    Publikasi::factory()->olehDosen($this->dosenTi)->create(['judul' => 'Belum divalidasi', 'tahun' => 2026]);

    $hki = $valid(Hki::factory()->create(['judul' => 'Aplikasi TI', 'tgl_pencatatan' => null, 'tgl_permohonan' => '2026-03-01']));
    $hki->kontributor()->create(['dosen_id' => $this->dosenTi->id, 'nama' => 'Ani', 'urutan' => 1]);
    $paten = $valid(Paten::factory()->create(['judul' => 'Alat TI', 'tgl_pemberian' => '2026-05-01']));
    $paten->kontributor()->create(['dosen_id' => $this->dosenTi->id, 'nama' => 'Ani', 'urutan' => 1]);

    $unduh = app(UnduhData::class);
    $sheet = $unduh->karya(['tahun' => 2026, 'prodi_id' => $this->informatika->id, 'hanya_valid' => true]);
    expect(array_keys($sheet))->toBe(['Publikasi', 'HKI', 'Paten'])
        ->and($sheet['Publikasi']->pluck('Judul')->all())->toBe(['Artikel TI', 'Prosiding TI'])
        ->and($sheet['HKI']->pluck('Judul')->all())->toBe(['Aplikasi TI'])
        ->and($sheet['HKI'][0]['Tahun'])->toBe(2026)
        ->and($sheet['Paten']->pluck('Judul')->all())->toBe(['Alat TI'])
        ->and($sheet['Publikasi'][0]['Program studi'])->toBe('Informatika');

    // Picking one type keeps only its sheet.
    $sheet = $unduh->karya(['tahun' => 2026, 'jenis' => 'publikasi:jurnal_internasional', 'hanya_valid' => true]);
    expect(array_keys($sheet))->toBe(['Publikasi'])
        ->and($sheet['Publikasi']->pluck('Judul')->all())->toBe(['Artikel TI']);

    // Entries still awaiting verification come along when asked for.
    expect($unduh->karya(['tahun' => 2026, 'jenis' => 'publikasi'])['Publikasi']->pluck('Judul'))->toContain('Belum divalidasi');
});

test('an unknown output type is refused', function () {
    $this->actingAs(pengunduh(Role::ADMIN_LPPM))->get(route('unduh-data.karya', ['jenis' => 'buku:apa']))->assertSessionHasErrors('jenis');
});

test('a title starting with "=" is written as text, not as a formula', function () {
    $periode = PeriodeHibah::factory()->create();
    Proposal::factory()->for($periode, 'periode')->for($this->dosenTi, 'ketua')
        ->create(['judul' => '=HYPERLINK("https://contoh.test","klik")', 'status' => StatusProposal::Diajukan]);

    $berkas = tempnam(sys_get_temp_dir(), 'unduh');
    file_put_contents($berkas, $this->actingAs(pengunduh(Role::ADMIN_LPPM))->get(route('unduh-data.proposal'))->streamedContent());
    $zip = new ZipArchive;
    $zip->open($berkas);
    $lembar = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    unlink($berkas);

    expect($lembar)->toContain('HYPERLINK')->not->toContain('<f>');
});
